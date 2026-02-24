@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">管理ダッシュボード</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded p-4 shadow">総実行数: <strong>{{ $executionCount }}</strong></div>
        <div class="bg-white rounded p-4 shadow">失敗数: <strong>{{ $failedCount }}</strong></div>
    </div>

    <h2 class="text-xl font-semibold mb-2">最新実行</h2>
    <div class="bg-white rounded shadow overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="p-2 text-left">ID</th><th class="p-2">Status</th><th class="p-2">Step</th></tr></thead>
            <tbody>
            @foreach($recentExecutions as $execution)
                <tr class="border-t">
                    <td class="p-2"><a class="text-blue-600" href="{{ route('admin.executions.show', $execution) }}">{{ $execution->id }}</a></td>
                    <td class="p-2">{{ $execution->status }}</td>
                    <td class="p-2">{{ $execution->step_count }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="text-xl font-semibold mb-2">監査ログ</h2>
    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="p-2 text-left">日時</th><th class="p-2 text-left">Action</th><th class="p-2 text-left">Target</th></tr></thead>
            <tbody>
            @foreach($recentAuditLogs as $log)
                <tr class="border-t"><td class="p-2">{{ $log->created_at }}</td><td class="p-2">{{ $log->action }}</td><td class="p-2">{{ $log->target_type }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
