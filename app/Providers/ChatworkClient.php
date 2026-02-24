<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class ChatworkClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function listMessages(int $roomId): array
    {
        $response = $this->http->withHeaders([
            'X-ChatWorkToken' => (string) config('services.chatwork.api_token'),
        ])->get("https://api.chatwork.com/v2/rooms/{$roomId}/messages", [
            'force' => 1,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to list Chatwork messages: '.$response->status());
        }

        return $response->json();
    }

    public function postMessage(int $roomId, string $body): array
    {
        $response = $this->http->asForm()->withHeaders([
            'X-ChatWorkToken' => (string) config('services.chatwork.api_token'),
        ])->post("https://api.chatwork.com/v2/rooms/{$roomId}/messages", [
            'body' => $body,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to post Chatwork message: '.$response->status());
        }

        return $response->json();
    }
}
