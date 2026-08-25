<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper tipis di atas Telegram Bot API (https://core.telegram.org/bots/api).
 *
 * Didesain supaya AMAN dipanggil dari mana saja di aplikasi walau bot belum
 * dikonfigurasi (TELEGRAM_BOT_TOKEN kosong di .env) -- setiap method kirim
 * pesan akan no-op + log info, bukan melempar error, supaya alur utama
 * (assign project, approve/reject eviden, dst) tidak pernah gagal gara-gara
 * integrasi Telegram belum aktif atau sedang down.
 */
class TelegramService
{
    protected static function botToken(): ?string
    {
        return config('services.telegram.bot_token');
    }

    public static function isConfigured(): bool
    {
        return filled(self::botToken());
    }

    protected static function apiUrl(string $method): string
    {
        return 'https://api.telegram.org/bot' . self::botToken() . '/' . $method;
    }

    /**
     * Kirim pesan mentah ke satu chat_id Telegram.
     */
    public static function send(string $chatId, string $text, array $extra = []): bool
    {
        if (! self::isConfigured()) {
            Log::info('[Telegram] Bot token belum dikonfigurasi, pesan dilewati.', ['chat_id' => $chatId]);

            return false;
        }

        try {
            $response = Http::timeout(5)->asForm()->post(self::apiUrl('sendMessage'), array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $extra));

            if (! $response->successful()) {
                Log::warning('[Telegram] Gagal mengirim pesan.', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('[Telegram] Exception saat mengirim pesan: ' . $e->getMessage(), ['chat_id' => $chatId]);

            return false;
        }
    }

    /**
     * Kirim pesan pribadi ke satu user (butuh telegram_chat_id sudah terhubung).
     * Aman dipanggil dengan $user null / belum terhubung -- otomatis diabaikan.
     */
    public static function sendToUser(?User $user, string $text): bool
    {
        if (! $user || empty($user->telegram_chat_id)) {
            return false;
        }

        return self::send($user->telegram_chat_id, $text);
    }

    /**
     * Kirim pesan ke SEMUA user dengan satu role tertentu yang sudah terhubung Telegram.
     * Mengembalikan jumlah user yang berhasil dikirimi pesan.
     */
    public static function sendToRole(string $role, string $text): int
    {
        return self::sendToRoles([$role], $text);
    }

    /**
     * Sama seperti sendToRole tapi untuk beberapa role sekaligus.
     */
    public static function sendToRoles(array $roles, string $text): int
    {
        $users = User::whereIn('role', $roles)
            ->whereNotNull('telegram_chat_id')
            ->get();

        $sent = 0;

        foreach ($users as $user) {
            if (self::send($user->telegram_chat_id, $text)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Daftarkan URL webhook ke Telegram. Dipakai oleh `php artisan telegram:webhook`.
     */
    public static function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = ['url' => $url];

        if ($secretToken) {
            $payload['secret_token'] = $secretToken;
        }

        $response = Http::timeout(10)->asForm()->post(self::apiUrl('setWebhook'), $payload);

        return $response->json() ?? [];
    }

    public static function deleteWebhook(): array
    {
        $response = Http::timeout(10)->post(self::apiUrl('deleteWebhook'));

        return $response->json() ?? [];
    }

    public static function getWebhookInfo(): array
    {
        $response = Http::timeout(10)->get(self::apiUrl('getWebhookInfo'));

        return $response->json() ?? [];
    }
}
