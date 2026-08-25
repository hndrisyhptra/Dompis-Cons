<?php

use App\Http\Controllers\Api\TelegramWebhookEventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEBHOOK API -- diambil (pull) oleh tim eksternal untuk diintegrasikan ke
| Bot Telegram mereka sendiri. Dompis Cons hanya menyediakan data event,
| tidak mengirim pesan Telegram apa pun. Diamankan lewat token statis
| (lihat VerifyWebhookApiToken, config/services.php: telegram.webhook_api_token).
|--------------------------------------------------------------------------
*/

Route::middleware('verify.webhook.token')->prefix('webhook')->group(function () {
    Route::get('/telegram-events', [TelegramWebhookEventController::class, 'index']);
    Route::get('/telegram-events/{id}', [TelegramWebhookEventController::class, 'show']);
    Route::post('/telegram-events/{id}/ack', [TelegramWebhookEventController::class, 'ack']);
    Route::post('/telegram-events/ack-bulk', [TelegramWebhookEventController::class, 'ackBulk']);
});
