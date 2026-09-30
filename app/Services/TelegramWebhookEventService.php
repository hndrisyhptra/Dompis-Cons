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
        $payload = array_merge([
            'event_version' => 1,
        ], $data['payload'] ?? []);

        $event = TelegramWebhookEvent::create([
            'event_type' => $data['event_type'],
            'recipient_type' => $data['recipient_type'],
            'recipient_user_id' => $data['recipient_user_id'] ?? null,
            'recipient_role' => $data['recipient_role'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'lop_id' => $data['lop_id'] ?? null,
            'title' => $data['title'],
            'message' => $data['message'],
            'payload' => $payload,
            'status' => 'pending',
        ]);

        PushTelegramWebhookEventJob::dispatch($event->id_tele_webhook)->onQueue('webhooks');

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

    /**
     * Event standar saat satu tahap pekerjaan sudah siap direview oleh Admin
     * yang melakukan assignment. PT3 dan PT2 memakai kontrak payload yang sama
     * agar receiver tidak perlu menebak sumber ID project/LOP.
     */
    public static function publishStageReviewRequested(
        User $admin,
        string $flow,
        string $stageCode,
        string $stageLabel,
        int $projectId,
        ?int $lopId,
        string $projectName,
        ?string $lopName,
        ?User $actor = null,
        array $payload = [],
    ): TelegramWebhookEvent {
        $actorName = $actor?->name ?? 'Pelaksana';

        return self::publishToUser(
            $admin,
            'stage_review_requested',
            "Review {$stageLabel} {$flow}",
            "{$actorName} telah menyelesaikan {$stageLabel} untuk {$projectName}"
                .($lopName ? " — LOP {$lopName}" : '')
                .'. Mohon lakukan review.',
            array_merge([
                'flow' => $flow,
                'stage_code' => $stageCode,
                'stage_label' => $stageLabel,
                'project_name' => $projectName,
                'lop_name' => $lopName,
                'actor_id' => $actor?->id_user,
                'actor_name' => $actor?->name,
                'actor_role' => $actor?->role,
            ], $payload),
            ['project_id' => $projectId, 'lop_id' => $lopId],
        );
    }

    /** Event standar ke role SDI setelah dokumen/data Golive dikunci oleh Admin. */
    public static function publishSdiVerificationRequested(
        string $flow,
        int $projectId,
        ?int $lopId,
        string $projectName,
        ?string $lopName,
        ?User $actor = null,
        array $payload = [],
    ): TelegramWebhookEvent {
        return self::publishToRole(
            'sdi',
            'sdi_verification_requested',
            "Verifikasi Golive {$flow}",
            "{$projectName}".($lopName ? " — LOP {$lopName}" : '').' sudah siap diverifikasi. Mohon upload eviden UIM agar project segera Golive.',
            array_merge([
                'flow' => $flow,
                'stage_code' => 'fi_ogp_golive',
                'stage_label' => 'FI OGP Golive',
                'project_name' => $projectName,
                'lop_name' => $lopName,
                'requested_by_id' => $actor?->id_user,
                'requested_by_name' => $actor?->name,
            ], $payload),
            ['project_id' => $projectId, 'lop_id' => $lopId],
        );
    }

    /** Event pribadi kepada Waspang/Teknisi setelah SDI meresmikan Golive. */
    public static function publishProjectGolive(
        User $recipient,
        string $flow,
        int $projectId,
        ?int $lopId,
        string $projectName,
        ?string $lopName,
        array $payload = [],
    ): TelegramWebhookEvent {
        return self::publishToUser(
            $recipient,
            'project_golive',
            "Project {$flow} Sudah Golive",
            "{$projectName}".($lopName ? " — LOP {$lopName}" : '').' yang Anda kerjakan sudah berhasil Golive.',
            array_merge([
                'flow' => $flow,
                'stage_code' => 'golive',
                'stage_label' => 'Golive',
                'project_name' => $projectName,
                'lop_name' => $lopName,
                'golive_at' => now()->toIso8601String(),
            ], $payload),
            ['project_id' => $projectId, 'lop_id' => $lopId],
        );
    }
}
