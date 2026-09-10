<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus 2 tabel legacy yang dikonfirmasi mati (2026-09-08):
 * - pt2_mancores (model Pt2Mancore) -- FK-nya ke `projects` biasa, BUKAN ke
 *   pt2_projects, walau namanya "pt2_*". Dari audit besar sebelumnya,
 *   satu-satunya pemakainya cuma relasi Project::pt2Mancore() yang sendiri
 *   tidak pernah dipanggil di manapun -- kode mati murni.
 * - pt2_surveys (model Pt2Survey) -- sama persis, FK ke `projects`, cuma
 *   dipakai relasi mati Project::pt2Survey().
 *
 * Tabel yang SUNGGUHAN dipakai untuk PT2 tetap ada dan TIDAK disentuh:
 * mancores_pt2 (model MancorePt2) dan surveys_pt2 (model SurveyPt2).
 *
 * Relasi mati Project::pt2Mancore()/pt2Survey() dihapus juga di
 * app/Models/Project.php pada commit yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pt2_mancores');
        Schema::dropIfExists('pt2_surveys');
    }

    public function down(): void
    {
        // Sengaja tidak direkonstruksi -- ini penghapusan tabel legacy yang
        // disengaja, bukan perubahan yang perlu bisa di-rollback penuh.
        // Kalau perlu dikembalikan, restore dari backup dompis_cons.sql.
    }
};
