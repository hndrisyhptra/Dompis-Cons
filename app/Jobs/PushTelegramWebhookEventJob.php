<?php

namespace App\Jobs;

use App\Models\TelegramWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PUSH satu event dari tabel telegram_webhook_events sebagai POST JSON ke
 * webhook receiver eksternal (config('services.telegram.webhook_push_url'),
 * saat ini di-set ke http://192.168.88.5:9093 - lihat .env).
 *
 * Ini TAMBAHAN, bukan pengganti, dari mekanisme pull-API yang sudah ada
 * (routes/api.php: /api/webhook/telegram-events) - kolom status/delivered_at
 * tetap dipakai untuk pull-API, sedangkan pushed_at/push_attempts/push_error
 * di sini khusus melacak hasil push job ini.
 *
 * Dijalankan lewat queue (QUEUE_CONNECTION=database di .env), jadi butuh
 * worker aktif: `php artisan queue:work` (atau queue:listen via Supervisor).
 * Tanpa worker jalan, job ini cuma akan mengantre di tabel `jobs` dan TIDAK
 * pernah benar-benar terkirim.
 */
class PushTelegramWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $eventId)
    {
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        if (!config('services.telegram.webhook_push_enabled')) {
            return;
        }

        $url = config('services.telegram.webhook_push_url');

        if (!$url) {
            return;
        }

        $event = TelegramWebhookEvent::find($this->eventId);

        if (!$event) {
            return;
        }

        $timeout = (int) config('services.telegram.webhook_push_timeout', 5);

        // Catat percobaan SEKALI per eksekusi job, baik sukses maupun gagal
        // (queue akan memanggil handle() lagi dari awal untuk tiap retry).
        $event->increment('push_attempts');

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->post($url, [
                    'id' => $event->id_tele_webhook,
                    'event_type' => $event->event_type,
                    'recipient_type' => $event->recipient_type,
                    'recipient_user_id' => $event->recipient_user_id,
                    'recipient_role' => $event->recipient_role,
                    'project_id' => $event->project_id,
                    'lop_id' => $event->lop_id,
                    'title' => $event->title,
                    'message' => $event->message,
                    'payload' => $event->payload,
                    'created_at' => optional($event->created_at)->toIso8601String(),
                ]);

            if ($response->successful()) {
                $event->forceFill([
                    'pushed_at' => now(),
                    'push_error' => null,
                ])->save();

                return;
            }

            $error = 'HTTP ' . $response->status() . ': ' . str($response->body())->limit(500);
            $event->forceFill(['push_error' => $error])->save();

            Log::warning('PushTelegramWebhookEventJob: receiver returned non-2xx', [
                'event_id' => $event->id_tele_webhook,
                'url' => $url,
                'status' => $response->status(),
            ]);

            // Biarkan job gagal (throw) supaya mekanisme retry bawaan
            // queue (tries/backoff di atas) yang menangani percobaan ulang.
            $response->throw();
        } catch (Throwable $e) {
            $event->forceFill(['push_error' => str($e->getMessage())->limit(500)->toString()])->save();

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('PushTelegramWebhookEventJob: gagal setelah semua percobaan', [
            'event_id' => $this->eventId,
            'url' => config('services.telegram.webhook_push_url'),
            'error' => $exception->getMessage(),
        ]);
    }
}
