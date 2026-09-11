<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix bug nyata (lihat ANALISA_REFACTOR_PERSIAPAN.md bag. AB): boq_items.
 * quantity_actual dipakai DOBEL -- diisi Volume BOQ Survey lewat
 * WaspangController::persistSurveyVolumes()/addSurveyBoqItem() (Stage
 * 4e/X) DAN diisi qty aktual Instalasi Step 3 lewat uploadEvidence().
 * Akibatnya Step 3 Instalasi menampilkan seolah2 progress Instalasi sudah
 * terisi, padahal itu cuma sisa Volume Survey yang belum pernah disentuh
 * Waspang di Instalasi.
 *
 * Kolom baru `quantity_survey` jadi rumah KHUSUS Volume BOQ Survey.
 * `quantity_actual` mulai sekarang MURNI utk qty aktual Instalasi saja.
 *
 * Data lama diperbaiki lewat 2 langkah (SEKALI jalan, aman diulang --
 * langkah 1 hanya isi baris yg quantity_survey-nya masih NULL, langkah 2
 * hanya proses baris yg quantity_survey barusan terisi):
 * 1. quantity_survey diisi dari snapshot RONDE TERAKHIR tiap item di
 *    boq_survey_round_items (bukan dari quantity_actual saat ini) --
 *    snapshot itu diambil PERSIS saat Survey/Re Survey selesai jadi tetap
 *    akurat walau quantity_actual sudah/belum sempat ditimpa Instalasi.
 * 2. quantity_actual DIKOSONGKAN (NULL) HANYA utk item yang TIDAK PERNAH
 *    punya histori aktivitas 'update_quantity_actual' di
 *    project_activity_logs (artinya isinya dipastikan masih sisa Survey,
 *    BUKAN progress Instalasi asli) -- item yang SUDAH PERNAH diupdate
 *    progresnya lewat Step 3 TIDAK disentuh sama sekali, supaya data
 *    progres asli aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('boq_items', 'quantity_survey')) {
            DB::statement(
                "ALTER TABLE `boq_items` ADD COLUMN `quantity_survey` INT(11) NULL AFTER `quantity_plan`"
            );
        }

        if (! Schema::hasTable('boq_survey_round_items') || ! Schema::hasTable('boq_survey_rounds')) {
            // Migration Re Survey (140000) belum pernah jalan -- belum ada
            // sumber snapshot utk backfill, cukup tambah kolom saja dulu.
            return;
        }

        // 1. quantity_survey <- snapshot ronde dgn round_number TERBESAR
        // per boq_item_id (baik masih in_progress maupun sudah completed).
        DB::statement(<<<'SQL'
            UPDATE boq_items bi
            INNER JOIN (
                SELECT bsri.boq_item_id, bsri.quantity_survey
                FROM boq_survey_round_items bsri
                INNER JOIN boq_survey_rounds bsr ON bsr.id = bsri.boq_survey_round_id
                INNER JOIN (
                    SELECT bsri2.boq_item_id, MAX(bsr2.round_number) AS max_round
                    FROM boq_survey_round_items bsri2
                    INNER JOIN boq_survey_rounds bsr2 ON bsr2.id = bsri2.boq_survey_round_id
                    WHERE bsri2.boq_item_id IS NOT NULL
                    GROUP BY bsri2.boq_item_id
                ) latest ON latest.boq_item_id = bsri.boq_item_id AND latest.max_round = bsr.round_number
            ) src ON src.boq_item_id = bi.id_boq
            SET bi.quantity_survey = src.quantity_survey
            WHERE bi.quantity_survey IS NULL
        SQL);

        // 2. quantity_actual dikosongkan HANYA utk item yg quantity_survey
        // barusan terisi (pernah lewat Survey) DAN belum pernah punya
        // histori 'update_quantity_actual' asli (progress Instalasi).
        DB::statement(<<<'SQL'
            UPDATE boq_items bi
            SET bi.quantity_actual = NULL
            WHERE bi.quantity_survey IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM project_activity_logs pal
                  WHERE pal.activity_type = 'update_quantity_actual'
                    AND JSON_UNQUOTE(JSON_EXTRACT(pal.meta, '$.boq_item_id')) = bi.id_boq
              )
        SQL);
    }

    public function down(): void
    {
        // Data lama TIDAK direkonstruksi (irreversible by design -- lihat
        // catatan up(), quantity_actual yg dikosongkan tidak bisa ditebak
        // ulang nilai aslinya); cukup drop kolom.
        if (Schema::hasColumn('boq_items', 'quantity_survey')) {
            DB::statement('ALTER TABLE `boq_items` DROP COLUMN `quantity_survey`');
        }
    }
};
