<?php

namespace App\Services;

use App\Models\BotTool;

class ToolRunner
{
    public function __construct(private readonly ChatworkClient $chatworkClient)
    {
    }

    public function run(array $toolCall): array
    {
        $tool = (string) data_get($toolCall, 'tool_name');

        if (! $this->isEnabled($tool)) {
            return [
                'ok' => false,
                'error' => "Tool '{$tool}' is disabled.",
            ];
        }

        if ($tool === 'get_messages') {
            return $this->runGetMessages($toolCall);
        }

        if ($tool === 'get_message_by_id') {
            $roomId = (int) data_get($toolCall, 'args.room_id', 0);
            $messageId = (string) data_get($toolCall, 'args.message_id', '');

            return [
                'ok' => true,
                'tool_name' => 'get_message_by_id',
                'message' => $this->chatworkClient->getMessage($roomId, $messageId),
            ];
        }

        if ($tool === 'list_joined_rooms') {
            return [
                'ok' => true,
                'tool_name' => 'list_joined_rooms',
                'rooms' => $this->chatworkClient->listMyRooms(),
            ];
        }

        return [
            'ok' => false,
            'error' => 'Unsupported tool_name.',
        ];
    }

    private function runGetMessages(array $toolCall): array
    {
        $roomId = (int) data_get($toolCall, 'args.room_id', 0);
        $beforeMessageId = (string) data_get($toolCall, 'args.before_message_id', '');
        $limit = max(1, min(20, (int) data_get($toolCall, 'args.limit', 5)));

        $messages = $this->chatworkClient->listMessages($roomId);
        $older = array_values(array_filter($messages, function (array $message) use ($beforeMessageId): bool {
            return strcmp((string) ($message['message_id'] ?? ''), $beforeMessageId) < 0;
        }));

        usort($older, fn (array $a, array $b): int => strcmp((string) $a['message_id'], (string) $b['message_id']));
        $slice = array_slice($older, -$limit);

        return [
            'ok' => true,
            'tool_name' => 'get_messages',
            'messages' => $slice,
            'has_more' => count($older) > count($slice),
            'note' => count($slice) === 0
                ? 'Chatwork APIの制約により追加取得できませんでした（最新100件外の可能性）。'
                : null,
        ];
    }

    private function isEnabled(string $toolName): bool
    {
        $tool = BotTool::query()->where('name', $toolName)->first();

        if ($tool === null) {
            return $toolName === 'get_messages';
        }

        return $tool->is_enabled;
    }
}
