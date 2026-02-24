@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">実行 #{{ $execution->id }}</h1>
        <form method="POST" action="{{ route('admin.executions.retry', $execution) }}">
            @csrf
            <button class="px-4 py-2 bg-amber-600 text-white rounded">再実行</button>
        </form>
    </div>

    <div class="bg-white rounded p-4 shadow mb-6">
        <p>Status: {{ $execution->status }}</p>
        <p>Error Type: {{ $execution->error_type }}</p>
        <p>Last Error: {{ $execution->last_error }}</p>
        <p>Reply: {{ $execution->reply_body }}</p>
    </div>

    <h2 class="text-xl font-semibold mb-3">ラリー詳細</h2>
    <div class="space-y-4">
        @foreach($execution->turns as $turn)
            <div class="bg-white rounded p-4 shadow">
                <h3 class="font-semibold mb-2">Step {{ $turn->step_index }}</h3>
                <p class="text-sm font-medium mb-1">User Prompt</p>
                <pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto">{{ $turn->user_prompt }}</pre>
                <p class="text-sm font-medium mt-3 mb-1">Model Response</p>
                <pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto">{{ json_encode($turn->model_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                <p class="text-sm font-medium mt-3 mb-1">Tool Result</p>
                <pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto">{{ json_encode($turn->tool_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endforeach
    </div>
@endsection
