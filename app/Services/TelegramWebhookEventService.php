<?php

namespace App\Services;

use App\Jobs\PushTelegramWebhookEventJob;
use App\Models\TelegramWebhookEvent;
use App\Models\User;

/**
 * Menyimpan event notifikasi ke tabel telegram_webhook_events. Selain
 * disediakan lewat webhook (API pull -- lihat TelegramWebhookEventController,
 * dipakai tim lain untuk integrasi ke Bot Telegram mereka sendiri), setiap
 * event yang dipublish di sini juga langsung di-dispatch ke queue job
 * PushTelegramWebhookEventJob, yang akan POST (JSON) event tsb ke webhook
 * receiver eksternal (config('services.telegram.webhook_push_url')). Push
 * ini murni tambahan -- kalau gagal/nonaktif, pull-API tetap jalan normal.
 */
class TelegramWebhookEventService
{
    public static function publish(array $data): TelegramWebhookEvent
    {
        $event = TelegramWebhookEvent::create([
            'event_type' => $data['event_type'],
            'recipient_type' => $data['recipient_type'],
            'recipient_user_id' => $data['recipient_user_id'] ?? null,
            'recipient_role' => $data['recipient_role'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'lop_id' => $data['lop_id'] ?? null,
            'title' => $data['title'],
            'message' => $data['message'],
            'payload' => $data['payload'] ?? null,
            'status' => 'pending',
        ]);

        PushTelegramWebhookEventJob::dispatch($event->id_tele_webhook);

        return $event;
    }

    /**
     * Event bertarget SATU user (dipakai untuk trigger: assign project, upload
     * eviden step selesai, eviden direject). $extra bisa dipakai untuk mengisi
     * kolom project_id/lop_id langsung di tabel (di luar payload JSON).
     */
    public static function publishToUser(User $user, string $eventType, string $title, string $message, array $payload = [], array $extra = []): TelegramWebhookEvent
    {
        return self::publish(array_merge([
            'event_type' => $eventType,
            'recipient_type' => 'user',
            'recipient_user_id' => $user->id_user,
            'title' => $title,
            'message' => $message,
            'payload' => array_merge([
                'recipient_name' => $user->name,
                'recipient_username' => $user->username,
                'recipient_nik' => $user->nik,
                'recipient_role' => $user->role,
            ], $payload),
        ], $extra));
    }

    /**
     * Event bertarget SATU role (dipakai untuk trigger: reminder ke PM).
     */
    public static function publishToRole(string $role, string $eventType, string $title, string $message, array $payload = [], array $extra = []): TelegramWebhookEvent
    {
        return self::publish(array_merge([
            'event_type' => $eventType,
            'recipient_type' => 'role',
            'recipient_role' => $role,
            'title' => $title,
            'message' => $message,
            'payload' => $payload,
        ], $extra));
    }
}
