@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Playground</h1>

    <form method="POST" action="{{ route('admin.playground.run') }}" class="space-y-4">
        @csrf
        <div class="bg-white p-4 rounded shadow">
            <label class="block text-sm mb-1">System Prompt</label>
            <textarea name="system_prompt" rows="8" class="w-full border rounded p-2">{{ old('system_prompt', $systemPrompt) }}</textarea>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <label class="block text-sm mb-1">User Prompt</label>
            <textarea name="user_prompt" rows="8" class="w-full border rounded p-2">{{ old('user_prompt') }}</textarea>
        </div>
        <button class="px-4 py-2 bg-blue-600 text-white rounded">実行</button>
    </form>

    @if($response)
        <div class="bg-white p-4 rounded shadow mt-6">
            <h2 class="font-semibold mb-2">Response</h2>
            <pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto">{{ json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
@endsection
