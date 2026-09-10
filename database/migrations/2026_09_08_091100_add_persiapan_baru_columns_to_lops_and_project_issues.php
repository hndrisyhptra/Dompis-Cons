<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stage 4d (refactor Persiapan 5 sub-step -- lihat ANALISA_REFACTOR_PERSIAPAN.md
 * bag. Q.2): dua kolom tambahan untuk tabel LAMA (`lops`, `project_issues`),
 * pakai raw ALTER (bukan Schema Blueprint) -- ikuti gaya migration
 * 2026_09_08_090300 (convert_lops_status_progress...) karena tabel-tabel ini
 * sudah ada dari SQL import lama & pernah bentrok charset/collation kalau
 * pakai Schema::table() Blueprint biasa.
 *
 * - lops.perizinan_completed_at: ditandai TERISI saat waspang menekan radio
 *   "Perizinan Selesai" (lihat WaspangController::togglePerizinanSelesai()) --
 *   dipakai utk syarat upload BA KP muncul & sebagai jejak audit kapan
 *   perizinan resmi dianggap tuntas.
 * - project_issues.stage_code: menandai kendala ini dilaporkan dari sub-step
 *   mana (survey/drm/perizinan/material_delivery/dst) -- SEBELUM ini kendala
 *   cuma satu timeline datar per project tanpa konteks step. Nullable supaya
 *   baris kendala lama (sebelum kolom ini ada) tetap valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `lops` ADD COLUMN `perizinan_completed_at` TIMESTAMP NULL AFTER `permit_category_id`");

        DB::statement("ALTER TABLE `project_issues` ADD COLUMN `stage_code` VARCHAR(50) NULL AFTER `lop_id`");
        DB::statement("ALTER TABLE `project_issues` ADD KEY `project_issues_stage_code_index` (`stage_code`)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `lops` DROP COLUMN `perizinan_completed_at`");

        DB::statement("ALTER TABLE `project_issues` DROP KEY `project_issues_stage_code_index`");
        DB::statement("ALTER TABLE `project_issues` DROP COLUMN `stage_code`");
    }
};
