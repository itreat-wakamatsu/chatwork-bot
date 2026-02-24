<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessChatworkMentionJob;
use App\Models\AiExecution;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;

class RetryExecutionController extends Controller
{
    public function __invoke(AiExecution $execution): RedirectResponse
    {
        $payload = [
            'webhook_event_type' => 'mention_to_me',
            'webhook_event' => [
                'room_id' => $execution->room_id,
                'message_id' => $execution->trigger_message_id,
                'from_account_id' => $execution->sender_account_id,
                'body' => '',
                'send_time' => now()->timestamp,
            ],
            'force_retry' => true,
        ];

        $execution->increment('retry_count');
        $execution->update(['status' => 'processing']);

        ProcessChatworkMentionJob::dispatch($payload);

        AuditLog::query()->create([
            'user_id' => request()->user()?->id,
            'action' => 'execution.retry',
            'target_type' => 'ai_execution',
            'target_id' => (string) $execution->id,
            'before' => ['status' => $execution->getOriginal('status')],
            'after' => ['status' => 'processing'],
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.executions.show', $execution)->with('status', '再実行ジョブを投入しました。');
    }
}
