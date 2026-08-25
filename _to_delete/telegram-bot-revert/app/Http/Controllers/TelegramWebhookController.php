<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Menerima update dari Telegram (webhook). Endpoint ini publik (tidak lewat
 * middleware auth) karena yang memanggilnya adalah server Telegram, bukan
 * user yang login -- keamanannya dijaga lewat kombinasi:
 *   1. Secret di dalam URL sendiri (lihat routes/web.php: /webhook/telegram/{secret})
 *   2. Header X-Telegram-Bot-Api-Secret-Token (dikirim Telegram jika di-set saat setWebhook,
 *      lihat TelegramService::setWebhook / php artisan telegram:webhook)
 *
 * Saat ini bot HANYA menangani satu perintah: /start <kode> -- dipakai untuk
 * menghubungkan (link) chat Telegram user dengan akunnya di aplikasi. Bot ini
 * murni untuk notifikasi satu arah (bukan bot interaktif penuh), jadi pesan
 * lain selain /start diabaikan.
 */
class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $secret)
    {
        $expected = config('services.telegram.webhook_secret');

        if (! $expected || ! hash_equals((string) $expected, $secret)) {
            abort(404);
        }

        $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($headerSecret !== null && ! hash_equals((string) $expected, $headerSecret)) {
            abort(404);
        }

        $update = $request->all();
        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if ($message) {
            $this->handleMessage($message);
        }

        // Telegram hanya butuh respon cepat 200 OK, isi body tidak dipakai.
        return response()->json(['ok' => true]);
    }

    protected function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');

        if (! $chatId || $text === '') {
            return;
        }

        if (! Str::startsWith($text, '/start')) {
            // Bot ini murni notifikasi satu arah, pesan lain sengaja diabaikan.
            return;
        }

        $code = trim(Str::after($text, '/start'));

        if ($code === '') {
            TelegramService::send($chatId,
                "Halo! 👋\n\nUntuk menghubungkan akun Telegram ini dengan akun Anda di aplikasi Dompis Cons, buka menu <b>Profil</b> di aplikasi, lalu tekan tombol <b>Hubungkan Telegram</b> dan ikuti tautannya.");

            return;
        }

        $user = User::where('telegram_link_code', $code)->first();

        if (! $user) {
            TelegramService::send($chatId,
                "Kode tidak valid atau sudah kedaluwarsa. Silakan buat kode baru dari halaman <b>Profil</b> di aplikasi, lalu coba lagi.");

            return;
        }

        // Kalau chat_id ini sebelumnya sudah terhubung ke akun lain, lepaskan dulu
        // supaya kolom unique telegram_chat_id tidak bentrok (satu chat = satu akun aktif).
        User::where('telegram_chat_id', $chatId)
            ->where('id_user', '!=', $user->id_user)
            ->update(['telegram_chat_id' => null, 'telegram_linked_at' => null]);

        $user->telegram_chat_id = $chatId;
        $user->telegram_linked_at = now();
        $user->telegram_link_code = null; // kode sekali pakai
        $user->save();

        TelegramService::send($chatId,
            "✅ Berhasil! Akun Telegram ini sekarang terhubung ke akun <b>{$user->name}</b> ({$user->role}).\n\nAnda akan menerima notifikasi terkait project di sini.");
    }
}
