<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mengelola koneksi Telegram milik user yang sedang login, dipanggil dari
 * partial resources/views/partials/telegram-link.blade.php yang di-include
 * di halaman Profil (admin/pm/sdi, waspang, teknisi).
 */
class TelegramLinkController extends Controller
{
    /**
     * Buat/perbarui kode penghubung Telegram untuk user yang sedang login,
     * lalu kembalikan deep-link t.me nya (kalau TELEGRAM_BOT_USERNAME sudah diisi)
     * agar bisa langsung dibuka pengguna dari HP.
     */
    public function generate(Request $request)
    {
        $user = $request->user();

        do {
            $code = Str::upper(Str::random(8));
        } while (User::where('telegram_link_code', $code)->exists());

        $user->telegram_link_code = $code;
        $user->save();

        $botUsername = config('services.telegram.bot_username');
        $deepLink = $botUsername
            ? "https://t.me/{$botUsername}?start={$code}"
            : null;

        return response()->json([
            'code' => $code,
            'deep_link' => $deepLink,
            'bot_configured' => TelegramService::isConfigured(),
        ]);
    }

    /**
     * Putuskan koneksi Telegram dari akun yang sedang login.
     */
    public function unlink(Request $request)
    {
        $user = $request->user();
        $user->telegram_chat_id = null;
        $user->telegram_linked_at = null;
        $user->telegram_link_code = null;
        $user->save();

        return back()->with('success', 'Telegram berhasil diputuskan dari akun Anda.');
    }
}
