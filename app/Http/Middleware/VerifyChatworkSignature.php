<?php

namespace App\Http\Middleware;

use App\Models\BotSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyChatworkSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $headerSignature = (string) $request->header('x-chatworkwebhooksignature', '');
        $setting = BotSetting::query()->first();
        $token = (string) ($setting?->chatwork_webhook_token ?: config('services.chatwork.webhook_token'));

        if ($headerSignature === '' || $token === '') {
            return response('Unauthorized', 401);
        }

        $decodedSecret = base64_decode($token, true);
        if ($decodedSecret === false) {
            return response('Unauthorized', 401);
        }

        $rawBody = $request->getContent();
        $digest = hash_hmac('sha256', $rawBody, $decodedSecret, true);
        $expected = base64_encode($digest);

        if (! hash_equals($expected, $headerSignature)) {
            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}
