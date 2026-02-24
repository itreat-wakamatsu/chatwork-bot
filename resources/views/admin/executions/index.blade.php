@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-4">実行履歴</h1>
    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
            <tr><th class="p-2 text-left">ID</th><th class="p-2">Status</th><th class="p-2">Error Type</th><th class="p-2">Steps</th><th class="p-2">Retry</th><th class="p-2">日時</th></tr>
            </thead>
            <tbody>
            @foreach($executions as $execution)
                <tr class="border-t">
                    <td class="p-2"><a class="text-blue-600" href="{{ route('admin.executions.show', $execution) }}">{{ $execution->id }}</a></td>
                    <td class="p-2">{{ $execution->status }}</td>
                    <td class="p-2">{{ $execution->error_type }}</td>
                    <td class="p-2">{{ $execution->step_count }}</td>
                    <td class="p-2">{{ $execution->retry_count }}</td>
                    <td class="p-2">{{ $execution->created_at }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $executions->links() }}</div>
@endsection
