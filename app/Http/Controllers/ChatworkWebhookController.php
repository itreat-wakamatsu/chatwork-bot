<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessChatworkMentionJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatworkWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        // Chatworkは失敗時再送しないため、常に200を返す。
        // MVPではsync実行も可能だが、10秒制約があるため本番ではqueue推奨。
        ProcessChatworkMentionJob::dispatch($payload);

        return response()->json(['ok' => true], 200);
    }
}
