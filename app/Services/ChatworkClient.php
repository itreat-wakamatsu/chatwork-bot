<?php

namespace App\Services;

use App\Models\BotSetting;
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
            'X-ChatWorkToken' => $this->resolveApiToken(),
        ])->get("https://api.chatwork.com/v2/rooms/{$roomId}/messages", [
            'force' => 1,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to list Chatwork messages: '.$response->status());
        }

        return $response->json();
    }

    public function getMessage(int $roomId, string $messageId): array
    {
        $response = $this->http->withHeaders([
            'X-ChatWorkToken' => $this->resolveApiToken(),
        ])->get("https://api.chatwork.com/v2/rooms/{$roomId}/messages/{$messageId}");

        if (! $response->successful()) {
            throw new RuntimeException('Failed to get Chatwork message: '.$response->status());
        }

        return (array) $response->json();
    }

    public function listMyRooms(): array
    {
        $response = $this->http->withHeaders([
            'X-ChatWorkToken' => $this->resolveApiToken(),
        ])->get('https://api.chatwork.com/v2/rooms');

        if (! $response->successful()) {
            throw new RuntimeException('Failed to list Chatwork rooms: '.$response->status());
        }

        return $response->json();
    }

    public function postMessage(int $roomId, string $body): array
    {
        $response = $this->http->asForm()->withHeaders([
            'X-ChatWorkToken' => $this->resolveApiToken(),
        ])->post("https://api.chatwork.com/v2/rooms/{$roomId}/messages", [
            'body' => $body,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to post Chatwork message: '.$response->status());
        }

        return $response->json();
    }

    private function resolveApiToken(): string
    {
        $setting = BotSetting::query()->first();
        $token = $setting?->chatwork_api_token ?: config('services.chatwork.api_token');

        return (string) $token;
    }
}
