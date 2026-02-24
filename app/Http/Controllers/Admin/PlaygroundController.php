<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaygroundRequest;
use App\Models\AuditLog;
use App\Models\BotSetting;
use App\Services\GeminiClient;
use Illuminate\Contracts\View\View;

class PlaygroundController extends Controller
{
    public function index(): View
    {
        $setting = BotSetting::query()->first();

        return view('admin.playground.index', [
            'systemPrompt' => $setting?->system_prompt ?? '',
            'response' => null,
        ]);
    }

    public function run(PlaygroundRequest $request, GeminiClient $geminiClient): View
    {
        $validated = $request->validated();
        $response = $geminiClient->generateJson($validated['system_prompt'], $validated['user_prompt']);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'playground.run',
            'target_type' => 'playground',
            'target_id' => null,
            'before' => ['user_prompt' => $validated['user_prompt']],
            'after' => $response,
            'ip_address' => $request->ip(),
        ]);

        return view('admin.playground.index', [
            'systemPrompt' => $validated['system_prompt'],
            'response' => $response,
        ]);
    }
}
