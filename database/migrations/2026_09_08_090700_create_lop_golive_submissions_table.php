<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step "FI-OGP Golive" (role admin): 4 hal yang diupload untuk pendukung
 * Golive -- capture valins, PDF ABD & Valid4, KML, Mancore.
 *
 * Mancore di sini SENGAJA cuma path 1 file (foto capture ATAU excel) --
 * BUKAN pakai tabel relasional pt2_mancores/mancores_pt2 (itu tabel PT2,
 * dan pemilik project sudah konfirmasi pt2_mancores + pt2_surveys akan
 * dihapus, lihat migration drop_pt2_mancores_and_pt2_surveys_tables).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lop_golive_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lop_id')->unique();
            $table->string('capture_valins_path')->nullable();
            $table->string('abd_valid4_path')->nullable();
            $table->string('kml_path')->nullable();
            $table->string('mancore_path')->nullable();
            $table->enum('mancore_input_type', ['photo', 'excel'])->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('lop_id')->references('id_lop')->on('lops')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id_user')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lop_golive_submissions');
    }
};
