<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat perpindahan status_progress per LOP -- kapan masuk suatu tahap,
 * kapan selesai, siapa yang menyelesaikan. lops.status_progress sendiri
 * cuma menyimpan posisi SAAT INI; tabel ini yang menjaga jejaknya supaya
 * bisa dianalisa (berapa lama macet di suatu tahap, dst) dan dipakai
 * sebagai salah satu sumber timeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lop_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->string('stage_code', 50);
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['lop_id', 'stage_code']);
            $table->foreign('lop_id')->references('id_lop')->on('lops')->onDelete('cascade');
            $table->foreign('stage_code')->references('code')->on('project_stages');
            $table->foreign('completed_by')->references('id_user')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lop_stage_histories');
    }
};
