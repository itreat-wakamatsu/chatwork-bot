<?php

namespace App\Console\Commands;

use App\Models\AiExecution;
use App\Models\BotSetting;
use App\Services\ChatworkClient;
use Illuminate\Console\Command;

class CheckExecutionAlerts extends Command
{
    protected $signature = 'bot:check-execution-alerts';

    protected $description = 'Notify when execution failures exceed threshold';

    public function handle(ChatworkClient $chatworkClient): int
    {
        $setting = BotSetting::query()->first();
        if ($setting === null || ! $setting->alert_room_id) {
            return self::SUCCESS;
        }

        $failedCount = AiExecution::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes($setting->alert_window_minutes))
            ->count();

        if ($failedCount < $setting->alert_failure_threshold) {
            return self::SUCCESS;
        }

        $chatworkClient->postMessage((int) $setting->alert_room_id, 'Botアラート: 実行失敗数が閾値を超えました。');

        return self::SUCCESS;
    }
}
