<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * project_issues diperluas (bukan diganti) supaya bisa jadi sumber timeline
 * kendala per project/LOP:
 * - kendala_category_id: FK ke kendala_categories (menggantikan issue_type
 *   bebas teks yang sekarang tidak benar-benar tervalidasi). issue_type LAMA
 *   tetap dipertahankan apa adanya untuk kompatibilitas data lama, tidak
 *   dihapus.
 * - photo_paths: kolom JSON baru. Ini juga jadi PERBAIKAN BUG -- kode yang
 *   sudah berjalan (WaspangController::storeIssue()) menulis
 *   'photo_paths' => $photoPaths ke ProjectIssue::create(), tapi kolom ini
 *   belum pernah ada sama sekali sehingga selama ini foto eviden kendala
 *   diam-diam TIDAK tersimpan di tabel ini (lihat ANALISA_REFACTOR_PERSIAPAN.md
 *   bagian E). Model ProjectIssue akan diupdate di stage backend supaya
 *   kolom ini benar-benar dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `project_issues` ADD COLUMN `kendala_category_id` BIGINT UNSIGNED NULL AFTER `issue_type`");
        DB::statement("ALTER TABLE `project_issues` ADD COLUMN `photo_paths` LONGTEXT NULL CHECK (json_valid(`photo_paths`)) AFTER `photo_path`");
        DB::statement("ALTER TABLE `project_issues` ADD CONSTRAINT `project_issues_kendala_category_id_foreign` FOREIGN KEY (`kendala_category_id`) REFERENCES `kendala_categories` (`id`) ON DELETE SET NULL");
        DB::statement("ALTER TABLE `project_issues` ADD KEY `project_issues_kendala_category_id_index` (`kendala_category_id`)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `project_issues` DROP FOREIGN KEY `project_issues_kendala_category_id_foreign`");
        DB::statement("ALTER TABLE `project_issues` DROP COLUMN `kendala_category_id`");
        DB::statement("ALTER TABLE `project_issues` DROP COLUMN `photo_paths`");
    }
};
