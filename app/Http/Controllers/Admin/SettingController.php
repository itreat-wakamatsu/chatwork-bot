<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBotSettingRequest;
use App\Models\AuditLog;
use App\Models\BotSetting;
use App\Models\BotTool;
use App\Models\SettingRevision;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index(): View
    {
        $this->ensureDefaultTools();

        $setting = BotSetting::query()->first();
        $tools = BotTool::query()->orderBy('name')->get();
        $revisions = SettingRevision::query()->latest()->limit(20)->get();

        return view('admin.settings.index', [
            'setting' => $setting,
            'tools' => $tools,
            'revisions' => $revisions,
        ]);
    }

    public function update(UpdateBotSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $setting = BotSetting::query()->firstOrCreate([], [
                'system_prompt' => '',
            ]);

            $before = $setting->toArray();

            $setting->fill([
                'system_prompt' => $validated['system_prompt'],
                'chatwork_api_token' => $validated['chatwork_api_token'] ?? null,
                'chatwork_webhook_token' => $validated['chatwork_webhook_token'] ?? null,
                'chatwork_bot_account_id' => $validated['chatwork_bot_account_id'] ?? null,
                'alert_window_minutes' => (int) ($validated['alert_window_minutes'] ?? 15),
                'alert_failure_threshold' => (int) ($validated['alert_failure_threshold'] ?? 5),
                'alert_room_id' => isset($validated['alert_room_id']) ? (int) $validated['alert_room_id'] : null,
            ]);
            $setting->save();

            $enabledTools = collect($validated['enabled_tools'] ?? [])->values()->all();
            BotTool::query()->each(function (BotTool $tool) use ($enabledTools): void {
                $tool->update(['is_enabled' => in_array($tool->name, $enabledTools, true)]);
            });

            SettingRevision::query()->create([
                'user_id' => $request->user()?->id,
                'target_type' => 'bot_setting',
                'snapshot' => [
                    'setting' => $setting->fresh()?->toArray(),
                    'tools' => BotTool::query()->orderBy('name')->get()->toArray(),
                ],
                'change_reason' => $validated['change_reason'] ?? null,
            ]);

            AuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => 'settings.updated',
                'target_type' => 'bot_setting',
                'target_id' => (string) $setting->id,
                'before' => $before,
                'after' => $setting->fresh()?->toArray(),
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.settings.index')->with('status', '設定を更新しました。');
    }

    public function rollback(Request $request, SettingRevision $revision): RedirectResponse
    {
        DB::transaction(function () use ($request, $revision): void {
            $snapshot = $revision->snapshot;
            $settingData = data_get($snapshot, 'setting', []);
            $toolData = data_get($snapshot, 'tools', []);

            $setting = BotSetting::query()->firstOrCreate([], ['system_prompt' => '']);
            $before = $setting->toArray();

            $setting->fill([
                'system_prompt' => (string) data_get($settingData, 'system_prompt', ''),
                'chatwork_api_token' => data_get($settingData, 'chatwork_api_token'),
                'chatwork_webhook_token' => data_get($settingData, 'chatwork_webhook_token'),
                'chatwork_bot_account_id' => data_get($settingData, 'chatwork_bot_account_id'),
                'alert_window_minutes' => (int) data_get($settingData, 'alert_window_minutes', 15),
                'alert_failure_threshold' => (int) data_get($settingData, 'alert_failure_threshold', 5),
                'alert_room_id' => data_get($settingData, 'alert_room_id'),
            ]);
            $setting->save();

            foreach ($toolData as $tool) {
                BotTool::query()->where('name', data_get($tool, 'name'))->update([
                    'is_enabled' => (bool) data_get($tool, 'is_enabled', false),
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => 'settings.rollback',
                'target_type' => 'setting_revision',
                'target_id' => (string) $revision->id,
                'before' => $before,
                'after' => $setting->fresh()?->toArray(),
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('admin.settings.index')->with('status', '設定をロールバックしました。');
    }

    private function ensureDefaultTools(): void
    {
        $defaults = [
            'get_messages' => '過去メッセージ取得',
            'get_message_by_id' => 'ルームID+メッセージIDでメッセージ取得',
            'list_joined_rooms' => 'Bot参加ルーム一覧取得',
        ];

        foreach ($defaults as $name => $label) {
            BotTool::query()->firstOrCreate(['name' => $name], [
                'label' => $label,
                'is_enabled' => true,
            ]);
        }
    }
}
