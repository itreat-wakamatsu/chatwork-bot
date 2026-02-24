@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">設定</h1>
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="bg-white rounded p-4 shadow">
            <label class="block text-sm mb-1">システムプロンプト</label>
            <textarea name="system_prompt" rows="10" class="w-full border rounded p-2">{{ old('system_prompt', $setting?->system_prompt) }}</textarea>
        </div>
        <div class="bg-white rounded p-4 shadow grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div><label class="block text-sm mb-1">Chatwork API Token</label><input name="chatwork_api_token" value="{{ old('chatwork_api_token', $setting?->chatwork_api_token) }}" class="w-full border rounded p-2"></div>
            <div><label class="block text-sm mb-1">Webhook Token</label><input name="chatwork_webhook_token" value="{{ old('chatwork_webhook_token', $setting?->chatwork_webhook_token) }}" class="w-full border rounded p-2"></div>
            <div><label class="block text-sm mb-1">Bot Account ID</label><input name="chatwork_bot_account_id" value="{{ old('chatwork_bot_account_id', $setting?->chatwork_bot_account_id) }}" class="w-full border rounded p-2"></div>
            <div><label class="block text-sm mb-1">Gemini API Key</label><input name="gemini_api_key" value="{{ old('gemini_api_key', $setting?->gemini_api_key) }}" class="w-full border rounded p-2"></div>
            <div>
                <label class="block text-sm mb-1">Gemini Model</label>
                <select name="gemini_model" class="w-full border rounded p-2">
                    @php($selectedModel = old('gemini_model', $setting?->gemini_model ?? config('services.gemini.model')))
                    @foreach($geminiModels as $model)
                        <option value="{{ $model['value'] }}" @selected($selectedModel === $model['value'])>{{ $model['label'] }} ({{ $model['value'] }})</option>
                    @endforeach
                </select>
                @if($modelLoadError)
                    <p class="mt-1 text-xs text-amber-700">モデル一覧の取得に失敗しました: {{ $modelLoadError }}</p>
                @endif
            </div>
            <div><label class="block text-sm mb-1">Alert Room ID</label><input name="alert_room_id" value="{{ old('alert_room_id', $setting?->alert_room_id) }}" class="w-full border rounded p-2"></div>
            <div><label class="block text-sm mb-1">Alert Window Minutes</label><input name="alert_window_minutes" value="{{ old('alert_window_minutes', $setting?->alert_window_minutes ?? 15) }}" class="w-full border rounded p-2"></div>
            <div><label class="block text-sm mb-1">Alert Failure Threshold</label><input name="alert_failure_threshold" value="{{ old('alert_failure_threshold', $setting?->alert_failure_threshold ?? 5) }}" class="w-full border rounded p-2"></div>
        </div>
        <div class="bg-white rounded p-4 shadow">
            <p class="font-semibold mb-2">有効化ツール</p>
            @foreach($tools as $tool)
                <label class="block"><input type="checkbox" name="enabled_tools[]" value="{{ $tool->name }}" @checked($tool->is_enabled)> {{ $tool->label }} ({{ $tool->name }})</label>
            @endforeach
        </div>
        <div class="bg-white rounded p-4 shadow">
            <label class="block text-sm mb-1">変更理由 (任意)</label>
            <input name="change_reason" class="w-full border rounded p-2">
        </div>
        <button class="px-4 py-2 bg-blue-600 text-white rounded">保存</button>
    </form>

    <h2 class="text-xl font-semibold mt-8 mb-3">設定履歴</h2>
    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="p-2 text-left">ID</th><th class="p-2 text-left">日時</th><th class="p-2 text-left">理由</th><th class="p-2"></th></tr></thead>
            <tbody>
            @foreach($revisions as $revision)
                <tr class="border-t">
                    <td class="p-2">{{ $revision->id }}</td>
                    <td class="p-2">{{ $revision->created_at }}</td>
                    <td class="p-2">{{ $revision->change_reason }}</td>
                    <td class="p-2">
                        <form method="POST" action="{{ route('admin.settings.rollback', $revision) }}">
                            @csrf
                            <button class="px-3 py-1 rounded bg-amber-500 text-white">ロールバック</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
