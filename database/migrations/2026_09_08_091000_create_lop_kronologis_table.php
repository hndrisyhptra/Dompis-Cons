<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4d (refactor Persiapan 5 sub-step): tabel log kronologi generik --
 * SATU baris per entri catatan tanggal + deskripsi yang diinput manual oleh
 * Waspang. Dipakai di SEMUA step (bukan cuma Perizinan), lewat tombol
 * "Update Kronologi" universal (lihat WaspangController::storeKronologi()).
 *
 * BEDA dengan `lop_stage_histories` (tabel lama, 1 baris per PERPINDAHAN
 * status_progress, auto-generated sistem, sampai saat ini belum dipakai kode
 * manapun) -- `lop_kronologis` ini sebaliknya: banyak baris per stage,
 * ditulis manual oleh waspang, murni naratif (bukan representasi
 * perpindahan status).
 *
 * `stage_code` diisi bebas dgn code dari `project_stages` (survey/drm/
 * perizinan/material_delivery/dst) ATAU nama step lama (persiapan/instalasi/
 * pengukuran/finishing) tergantung dari halaman mana tombol kronologi
 * ditekan -- TIDAK di-FK-kan ke project_stages supaya tetap fleksibel dipakai
 * di halaman step lama yang code-nya beda konvensi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lop_kronologis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->unsignedInteger('project_id');
            $table->string('stage_code', 50);
            $table->date('event_date');
            $table->text('note');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['lop_id', 'stage_code']);
            $table->index('project_id');

            $table->foreign('lop_id')->references('id_lop')->on('lops')->cascadeOnDelete();
            $table->foreign('created_by')->references('id_user')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lop_kronologis');
    }
};
