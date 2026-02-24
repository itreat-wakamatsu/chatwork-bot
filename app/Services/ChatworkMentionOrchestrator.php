<?php

namespace App\Services;

use App\Models\AiExecution;
use App\Models\AiExecutionTurn;
use App\Models\BotSetting;
use App\Models\BotTool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ChatworkMentionOrchestrator
{
    private const MAX_STEPS = 6;

    private const MAX_CHATWORK_CALLS = 10;

    private const ERROR_REPLY = '現在自動処理中にエラーが発生しました。お手数ですが、少し時間を置いて再度お試しください。';

    private const DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
あなたはChatwork上で動作するAIボットです。
必要な操作は、指定された形式のJSONでtool_callを要求してください。
サーバーが代理で実行します。

出力形式（厳守）
1) tool_call
2) final

reply_bodyにはToメンション（[To:xxx]）を含めない。
PROMPT;

    public function __construct(
        private readonly ChatworkClient $chatworkClient,
        private readonly GeminiClient $geminiClient,
        private readonly ToolRunner $toolRunner,
    ) {
    }

    public function handle(array $payload): void
    {
        $eventType = data_get($payload, 'webhook_event_type');
        if ($eventType !== 'mention_to_me') {
            return;
        }

        $event = data_get($payload, 'webhook_event', []);
        $roomId = (int) data_get($event, 'room_id', 0);
        $messageId = (string) data_get($event, 'message_id', '');
        $senderId = (int) data_get($event, 'from_account_id', 0);
        $body = (string) data_get($event, 'body', '');
        $forceRetry = (bool) data_get($payload, 'force_retry', false);
        $botId = $this->resolveBotAccountId();

        if ($roomId === 0 || $messageId === '' || $senderId === 0 || $botId === '') {
            return;
        }

        if (! $forceRetry && ! Str::contains($body, "[To:{$botId}]")) {
            return;
        }

        $execution = DB::transaction(function () use ($roomId, $messageId, $senderId) {
            $existing = AiExecution::query()->where('room_id', $roomId)
                ->where('trigger_message_id', $messageId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return AiExecution::query()->create([
                'room_id' => $roomId,
                'trigger_message_id' => $messageId,
                'sender_account_id' => $senderId,
                'status' => 'processing',
            ]);
        });

        if ($execution->status === 'completed') {
            return;
        }

        try {
            $reply = $this->buildReply($event, $execution);
            $this->chatworkClient->postMessage($roomId, "[To:{$senderId}]\n{$reply}");

            $execution->update([
                'status' => 'completed',
                'reply_body' => $reply,
                'error_type' => null,
                'last_error' => null,
            ]);
        } catch (Throwable $e) {
            $safeError = Str::limit($e->getMessage(), 300);
            $errorType = $this->classifyError($e);

            $this->chatworkClient->postMessage($roomId, "[To:{$senderId}]\n".self::ERROR_REPLY);

            $execution->update([
                'status' => 'failed',
                'last_error' => $safeError,
                'error_type' => $errorType,
            ]);
        }
    }

    private function buildReply(array $event, AiExecution $execution): string
    {
        $roomId = (int) $event['room_id'];
        $messageId = (string) $event['message_id'];

        $apiCalls = 0;
        $latest = $this->chatworkClient->listMessages($roomId);
        $apiCalls++;

        $messagesBeforeTrigger = array_values(array_filter($latest, fn (array $m): bool => strcmp((string) ($m['message_id'] ?? ''), $messageId) < 0));
        usort($messagesBeforeTrigger, fn (array $a, array $b): int => strcmp((string) $a['message_id'], (string) $b['message_id']));
        $recent = array_slice($messagesBeforeTrigger, -5);

        $toolResults = [];

        for ($step = 1; $step <= self::MAX_STEPS; $step++) {
            $execution->update(['step_count' => $step]);

            $userPrompt = $this->buildUserPrompt($event, $recent, $toolResults);
            $ai = $this->geminiClient->generateJson($this->resolveSystemPrompt(), $userPrompt);

            AiExecutionTurn::query()->updateOrCreate([
                'ai_execution_id' => $execution->id,
                'step_index' => $step,
            ], [
                'user_prompt' => $userPrompt,
                'model_response' => $ai,
                'tool_result' => null,
            ]);

            if (($ai['type'] ?? '') === 'final') {
                $reply = trim((string) ($ai['reply_body'] ?? ''));
                if ($reply === '') {
                    throw new RuntimeException('AI returned empty reply_body.');
                }

                return Str::limit($reply, 4000);
            }

            if (($ai['type'] ?? '') !== 'tool_call') {
                throw new RuntimeException('AI returned unsupported type.');
            }

            if ($apiCalls >= self::MAX_CHATWORK_CALLS) {
                return '必要な追加情報の取得上限に達したため、現時点で回答します。情報不足があれば補足をご共有ください。';
            }

            $result = $this->toolRunner->run($ai);
            $apiCalls++;
            $toolResults[] = $result;

            AiExecutionTurn::query()->where('ai_execution_id', $execution->id)
                ->where('step_index', $step)
                ->update(['tool_result' => $result]);

            if (! empty($result['messages']) && is_array($result['messages'])) {
                $recent = array_slice($result['messages'], -5);
            }
        }

        return '確認ありがとうございます。現時点で取得できた情報をもとに回答しましたが、必要であれば追加情報をご共有ください。';
    }

    private function buildUserPrompt(array $event, array $recent, array $toolResults): string
    {
        $triggerTime = date('Y-m-d H:i:s', (int) ($event['send_time'] ?? time()));

        $lines = [];
        $lines[] = 'room_id: '.$event['room_id'];
        $lines[] = 'trigger_message_id: '.$event['message_id'];
        $lines[] = 'sender_account_id: '.$event['from_account_id'];
        $lines[] = '';
        $lines[] = '【トリガーメッセージ】';
        $lines[] = '['.$triggerTime.']';
        $lines[] = (string) $event['from_account_id'].':';
        $lines[] = (string) $event['body'];
        $lines[] = '';
        $lines[] = '【直近メッセージ（古い順・最大5件）】';

        foreach ($recent as $i => $message) {
            $time = date('Y-m-d H:i:s', (int) ($message['send_time'] ?? time()));
            $nameOrId = (string) data_get($message, 'account.account_id', 'unknown');
            $lines[] = sprintf('%d) [%s] %s: %s', $i + 1, $time, $nameOrId, (string) ($message['body'] ?? ''));
        }

        if ($toolResults !== []) {
            $lines[] = '';
            $lines[] = '【直近ツール実行結果】';
            $lines[] = json_encode($toolResults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $lines[] = '';
        $lines[] = '【使用可能ツール】';
        foreach ($this->enabledToolNames() as $name) {
            $lines[] = '- '.$name;
        }

        return implode("\n", $lines);
    }

    private function enabledToolNames(): array
    {
        $names = BotTool::query()->where('is_enabled', true)->orderBy('name')->pluck('name')->all();
        if ($names === []) {
            return ['get_messages'];
        }

        return $names;
    }

    private function resolveSystemPrompt(): string
    {
        $setting = BotSetting::query()->first();

        return $setting?->system_prompt ?: self::DEFAULT_SYSTEM_PROMPT;
    }

    private function resolveBotAccountId(): string
    {
        $setting = BotSetting::query()->first();

        return (string) ($setting?->chatwork_bot_account_id ?: config('services.chatwork.bot_account_id'));
    }

    private function classifyError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (Str::contains($message, ['JSON', 'unsupported type', 'reply_body'])) {
            return 'ai_response';
        }

        if (Str::contains($message, ['Chatwork', 'Gemini API'])) {
            return 'external_api';
        }

        return 'application';
    }
}
