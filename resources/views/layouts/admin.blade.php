<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chatwork Bot Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900">
<div class="min-h-screen">
    <nav class="bg-white shadow">
        <div class="mx-auto max-w-6xl px-4 py-3 flex gap-6">
            <a class="font-semibold" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.settings.index') }}">設定</a>
            <a href="{{ route('admin.executions.index') }}">実行履歴</a>
            <a href="{{ route('admin.playground.index') }}">Playground</a>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
