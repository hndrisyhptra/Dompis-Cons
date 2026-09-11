<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4e -- Deviasi nominal BOQ Survey vs BOQ Plan (>10% -> Approval
 * Redesign). Lihat ANALISA_REFACTOR_PERSIAPAN.md bagian U untuk spec asal.
 *
 * `survey_deviation_percent`: hasil hitung terakhir |survey-plan|/plan (%),
 * disimpan tiap kali WaspangController::finishSurvey() dijalankan (murni
 * catatan/histori, tidak dipakai sebagai gate).
 * `survey_redesign_required`: gate SEBENARNYA -- true selama deviasi >10%
 * dan waspang belum upload bukti persetujuan Redesign. Selama true, LOP
 * TETAP di status_progress='survey' (tidak lanjut ke Perizinan) walau
 * volume Survey sudah final.
 *
 * Gaya sama dgn migration 090300/090400/091100 (raw ALTER + guard
 * Schema::hasColumn, aman dijalankan ulang).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lops', 'survey_deviation_percent')) {
            DB::statement(
                "ALTER TABLE `lops` ADD COLUMN `survey_deviation_percent` DECIMAL(8,2) NULL AFTER `permit_category_id`"
            );
        }

        if (! Schema::hasColumn('lops', 'survey_redesign_required')) {
            DB::statement(
                "ALTER TABLE `lops` ADD COLUMN `survey_redesign_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `survey_deviation_percent`"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lops', 'survey_redesign_required')) {
            DB::statement('ALTER TABLE `lops` DROP COLUMN `survey_redesign_required`');
        }

        if (Schema::hasColumn('lops', 'survey_deviation_percent')) {
            DB::statement('ALTER TABLE `lops` DROP COLUMN `survey_deviation_percent`');
        }
    }
};
