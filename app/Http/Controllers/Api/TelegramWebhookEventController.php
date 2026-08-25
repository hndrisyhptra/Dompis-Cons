<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramWebhookEvent;
use Illuminate\Http\Request;

/**
 * API untuk tim eksternal MENGAMBIL (pull) event notifikasi dari Dompis Cons,
 * lalu diintegrasikan ke Bot Telegram mereka sendiri. Dompis Cons hanya
 * menyediakan data event -- TIDAK mengirim pesan Telegram apa pun.
 *
 * Auth: header `Authorization: Bearer <TELEGRAM_WEBHOOK_API_TOKEN>` atau
 * `X-Webhook-Token: <token>` (lihat VerifyWebhookApiToken, routes/api.php).
 */
class TelegramWebhookEventController extends Controller
{
    /**
     * GET /api/webhook/telegram-events
     *
     * Query params:
     *   status   = pending (default) | delivered | all
     *   type     = filter event_type tertentu (opsional):
     *              project_assigned | evidence_step_uploaded | evidence_rejected | project_stale_reminder
     *   per_page = default 50, maksimum 200
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 200) : 50;

        $query = TelegramWebhookEvent::query()->orderBy('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('type')) {
            $query->where('event_type', $request->query('type'));
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * GET /api/webhook/telegram-events/{id}
     */
    public function show($id)
    {
        return response()->json(TelegramWebhookEvent::findOrFail($id));
    }

    /**
     * POST /api/webhook/telegram-events/{id}/ack
     * Menandai satu event sudah berhasil dikirim, supaya tidak muncul lagi
     * di listing status=pending berikutnya.
     */
    public function ack($id)
    {
        $event = TelegramWebhookEvent::findOrFail($id);
        $event->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return response()->json($event);
    }

    /**
     * POST /api/webhook/telegram-events/ack-bulk
     * Body: { "ids": [1, 2, 3] }
     */
    public function ackBulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $updated = TelegramWebhookEvent::whereIn('id_tele_webhook', $request->ids)->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return response()->json(['updated' => $updated]);
    }
}
