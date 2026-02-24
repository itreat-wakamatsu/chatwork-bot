<?php

namespace App\Services;

use App\Models\BotSetting;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class GeminiClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function generateJson(string $systemPrompt, string $userPrompt): array
    {
        $model = $this->resolveModel();
        $apiKey = $this->resolveApiKey();
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

    public function listAvailableModels(): array
    {
        $apiKey = $this->resolveApiKey();
        $response = $this->http->get('https://generativelanguage.googleapis.com/v1beta/models', [
            'key' => $apiKey,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini model list API failed: '.$response->status());
        }

        $models = data_get($response->json(), 'models', []);
        if (! is_array($models)) {
            return [];
        }

        $options = [];

        foreach ($models as $model) {
            $name = (string) data_get($model, 'name', '');
            if ($name === '') {
                continue;
            }

            $supportedMethods = data_get($model, 'supportedGenerationMethods', []);
            if (! is_array($supportedMethods) || ! in_array('generateContent', $supportedMethods, true)) {
                continue;
            }

            $options[] = [
                'value' => str_replace('models/', '', $name),
                'label' => (string) data_get($model, 'displayName', $name),
            ];
        }

        usort($options, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $options;
    }

    public function resolveModel(): string
    {
        $setting = BotSetting::query()->first();
        $model = $setting?->gemini_model ?: config('services.gemini.model');

        return (string) $model;
    }

    private function resolveApiKey(): string
    {
        $setting = BotSetting::query()->first();
        $apiKey = $setting?->gemini_api_key ?: config('services.gemini.api_key');
        $value = (string) $apiKey;

        if ($value === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        return $value;
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
