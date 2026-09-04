<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan untuk melacak hasil PUSH otomatis (POST JSON) event ke
 * webhook receiver eksternal (lihat App\Jobs\PushTelegramWebhookEventJob),
 * terpisah dari status pull-API yang sudah ada ('status'/'delivered_at').
 *
 * - pushed_at    : diisi begitu POST ke receiver eksternal SUKSES (2xx).
 * - push_attempts: jumlah percobaan kirim (berguna untuk lihat retry job).
 * - push_error   : pesan error terakhir kalau push gagal (null kalau sukses/
 *                  belum pernah dicoba).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_webhook_events', function (Blueprint $table) {
            $table->timestamp('pushed_at')->nullable()->after('delivered_at');
            $table->unsignedTinyInteger('push_attempts')->default(0)->after('pushed_at');
            $table->text('push_error')->nullable()->after('push_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_webhook_events', function (Blueprint $table) {
            $table->dropColumn(['pushed_at', 'push_attempts', 'push_error']);
        });
    }
};
