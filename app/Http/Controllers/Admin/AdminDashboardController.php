<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiExecution;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'executionCount' => AiExecution::query()->count(),
            'failedCount' => AiExecution::query()->where('status', 'failed')->count(),
            'recentExecutions' => AiExecution::query()->latest()->limit(10)->get(),
            'recentAuditLogs' => AuditLog::query()->latest()->limit(10)->get(),
        ]);
    }
}
