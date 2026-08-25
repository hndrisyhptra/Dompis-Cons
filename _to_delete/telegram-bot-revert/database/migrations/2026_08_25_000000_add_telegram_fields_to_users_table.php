<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom untuk integrasi Bot Telegram:
     * - telegram_chat_id  : chat id Telegram user setelah berhasil terhubung (dipakai untuk kirim pesan pribadi)
     * - telegram_link_code: kode sekali-pakai yang ditampilkan di halaman Profil untuk proses /start <kode> di bot
     * - telegram_linked_at: kapan akun ini terakhir kali berhasil dihubungkan ke Telegram
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->unique()->after('status');
            $table->string('telegram_link_code')->nullable()->unique()->after('telegram_chat_id');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_link_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'telegram_link_code', 'telegram_linked_at']);
        });
    }
};
