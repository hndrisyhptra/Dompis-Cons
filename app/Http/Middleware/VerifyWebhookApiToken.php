<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengamankan endpoint API webhook (routes/api.php) dengan token statis.
 * Tim eksternal yang mengambil event mengirim salah satu dari:
 *   Authorization: Bearer <TELEGRAM_WEBHOOK_API_TOKEN>
 *   X-Webhook-Token: <TELEGRAM_WEBHOOK_API_TOKEN>
 */
class VerifyWebhookApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.telegram.webhook_api_token');
        $provided = $request->bearerToken() ?: $request->header('X-Webhook-Token');

        if (! $expected || ! $provided || ! hash_equals((string) $expected, (string) $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
