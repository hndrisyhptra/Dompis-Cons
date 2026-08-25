<?php

namespace App\Services;

use App\Models\TelegramWebhookEvent;
use App\Models\User;

/**
 * Menyimpan event notifikasi ke tabel telegram_webhook_events. Dompis Cons
 * HANYA menyediakan data event lewat webhook (API pull) -- TIDAK mengirim
 * pesan Telegram apa pun sendiri. Tim lain yang mengambil event dari sini
 * (lihat TelegramWebhookEventController) akan mengintegrasikannya ke Bot
 * Telegram mereka sendiri.
 */
class TelegramWebhookEventService
{
    public static function publish(array $data): TelegramWebhookEvent
    {
        return TelegramWebhookEvent::create([
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
