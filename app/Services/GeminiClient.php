<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class GeminiClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function generateJson(string $systemPrompt, string $userPrompt): array
    {
        $model = (string) config('services.gemini.model');
        $apiKey = (string) config('services.gemini.api_key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $payload = [
            'systemInstruction' => [
                'parts' => [[
                    'text' => $systemPrompt,
                ]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [[
                    'text' => $userPrompt,
                ]],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.2,
            ],
        ];

        $json = $this->call($url, $apiKey, $payload);
        if ($json !== null) {
            return $json;
        }

        $payload['contents'][0]['parts'][0]['text'] .= "\n\n重要: JSONのみを出力してください。";
        $json = $this->call($url, $apiKey, $payload);
        if ($json !== null) {
            return $json;
        }

        throw new RuntimeException('Gemini response is not valid JSON after retry.');
    }

    private function call(string $url, string $apiKey, array $payload): ?array
    {
        $response = $this->http->post($url.'?key='.urlencode($apiKey), $payload);
        if (! $response->successful()) {
            throw new RuntimeException('Gemini API failed: '.$response->status());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if (! is_string($text)) {
            return null;
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }
}
