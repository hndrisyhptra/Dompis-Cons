<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Redesign accordion Perizinan -- "Add Perizinan" (lihat
 * ANALISA_REFACTOR_PERSIAPAN.md bag. Z utk spec asal). Satu form Add
 * Perizinan = 1 baris `lop_kronologis` (stage_code='perizinan') + kategori
 * yang dipilih SAAT ITU (kolom baru `permit_category_id` di sini, terpisah
 * dari `lops.permit_category_id` yang selalu ikut kategori TERBARU) +
 * eviden opsional (foto/PDF) yang ditautkan ke entri kronologi tsb lewat
 * kolom baru `evidences.lop_kronologi_id`.
 *
 * Gaya sama dgn migration 090300/090400/140000 (raw ALTER + guard
 * Schema::hasColumn, aman dijalankan ulang).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lop_kronologis', 'permit_category_id')) {
            DB::statement(
                "ALTER TABLE `lop_kronologis` ADD COLUMN `permit_category_id` BIGINT UNSIGNED NULL AFTER `stage_code`"
            );
            DB::statement(
                "ALTER TABLE `lop_kronologis` ADD CONSTRAINT `lop_kronologis_permit_category_id_foreign` FOREIGN KEY (`permit_category_id`) REFERENCES `permit_categories` (`id`) ON DELETE SET NULL"
            );
        }

        if (! Schema::hasColumn('evidences', 'lop_kronologi_id')) {
            DB::statement(
                "ALTER TABLE `evidences` ADD COLUMN `lop_kronologi_id` BIGINT UNSIGNED NULL AFTER `boq_item_id`"
            );
            DB::statement(
                "ALTER TABLE `evidences` ADD CONSTRAINT `evidences_lop_kronologi_id_foreign` FOREIGN KEY (`lop_kronologi_id`) REFERENCES `lop_kronologis` (`id`) ON DELETE SET NULL"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('evidences', 'lop_kronologi_id')) {
            DB::statement('ALTER TABLE `evidences` DROP FOREIGN KEY `evidences_lop_kronologi_id_foreign`');
            DB::statement('ALTER TABLE `evidences` DROP COLUMN `lop_kronologi_id`');
        }

        if (Schema::hasColumn('lop_kronologis', 'permit_category_id')) {
            DB::statement('ALTER TABLE `lop_kronologis` DROP FOREIGN KEY `lop_kronologis_permit_category_id_foreign`');
            DB::statement('ALTER TABLE `lop_kronologis` DROP COLUMN `permit_category_id`');
        }
    }
};
