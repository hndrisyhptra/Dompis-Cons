<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel antrian event notifikasi. Dompis Cons TIDAK mengirim pesan Telegram
 * sendiri -- hanya menuliskan event ke tabel ini. Tim lain akan MENGAMBIL
 * (pull) event dari sini lewat endpoint API (lihat routes/api.php) dan
 * mengintegrasikannya ke Bot Telegram mereka sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_webhook_events', function (Blueprint $table) {
            $table->id('id_tele_webhook');

            // project_assigned | evidence_step_uploaded | evidence_rejected | project_stale_reminder
            $table->string('event_type');

            // 'user' = pesan pribadi ke satu user (recipient_user_id terisi)
            // 'role' = broadcast ke semua user dengan role tsb (recipient_role terisi)
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_user_id')->nullable();
            $table->string('recipient_role')->nullable();

            // Referensi project/lop terkait (boleh project biasa ATAU pt2, lihat payload utk detail).
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('lop_id')->nullable();

            $table->string('title');
            $table->text('message'); // teks siap-pakai (Bahasa Indonesia) kalau mau langsung di-relay apa adanya
            $table->json('payload')->nullable(); // data terstruktur (nama project, stage, review_note, dst)

            // pending -> menunggu diambil tim eksternal; delivered -> sudah di-ack
            $table->string('status')->default('pending');
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('event_type');
            $table->index('recipient_user_id');
            $table->index('recipient_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_webhook_events');
    }
};
