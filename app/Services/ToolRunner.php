<?php

namespace App\Services;

class ToolRunner
{
    public function __construct(private readonly ChatworkClient $chatworkClient)
    {
    }

    public function run(array $toolCall): array
    {
        $tool = data_get($toolCall, 'tool_name');

        if ($tool !== 'get_messages') {
            return [
                'ok' => false,
                'error' => 'Unsupported tool_name. In MVP only get_messages is available.',
            ];
        }

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
}
