<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah lops.status_progress dari ENUM('preparation','instalasi','finishing')
 * jadi VARCHAR yang di-FK-kan ke project_stages.code.
 *
 * Mapping data lama -> baru:
 * - 'preparation' -> 'persiapan_instalasi'
 *   (asumsi: LOP yang sudah berjalan sebelum refactor ini SELALU melalui
 *   alur lama -- upload barang_tiba + perizinan -- yang sekarang namanya
 *   diganti jadi "Persiapan Instalasi". LOP lama TIDAK pernah melalui 5
 *   sub-step "Persiapan" yang baru karena itu belum ada saat itu, jadi
 *   dianggap sudah "melewati" step itu.)
 * - 'instalasi'  -> 'instalasi'   (arti sama, tidak berubah)
 * - 'finishing'  -> 'finishing'   (arti sama, tidak berubah)
 *
 * `status_progress_before_hold` dipakai untuk resume BAIK dari HOLD (jeda
 * sementara) MAUPUN dari DROP (batal, tapi dikonfirmasi pemilik project bisa
 * di-reset lagi di kemudian hari) -- kolom yang sama dipakai untuk keduanya
 * karena sebuah LOP cuma bisa berada di salah satu status jeda ini dalam satu
 * waktu. Logic "resume dari HOLD/DROP" sendiri baru diimplementasikan di
 * Stage 2 (backend logic), migration ini baru menyiapkan tempat simpannya.
 *
 * CATATAN PENTING (fix setelah percobaan migrate pertama gagal):
 * Percobaan migrate pertama gagal di langkah pembuatan FOREIGN KEY
 * `lops_status_progress_foreign` dengan error 1005/errno 150 "Foreign key
 * constraint is incorrectly formed" -- penyebabnya charset/collation kolom
 * `lops.status_progress` (tabel `lops` dibuat manual lewat SQL, bukan
 * migration) TIDAK SAMA dengan `project_stages.code` (dibuat lewat Laravel
 * Schema Builder, ikut charset/collation default koneksi). MySQL/MariaDB
 * menolak FK antar kolom string dengan charset/collation yang berbeda.
 * Perbaikan: baca charset & collation `project_stages.code` yang sebenarnya
 * lalu paksa kolom di `lops` memakai persis charset/collation yang sama.
 *
 * Migration ini juga dibuat aman dijalankan ulang (idempotent) walau
 * sebagian langkahnya sempat berhasil sebelum gagal di tengah jalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 0. Ambil charset & collation project_stages.code apa adanya di DB
        //    ini, supaya kolom lops.status_progress bisa dipaksa sama persis
        //    (bukan menebak-nebak nilai defaultnya).
        $meta = DB::selectOne(
            "SELECT CHARACTER_SET_NAME, COLLATION_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_stages' AND COLUMN_NAME = 'code'"
        );
        $charset   = $meta->CHARACTER_SET_NAME ?? 'utf8mb4';
        $collation = $meta->COLLATION_NAME ?? 'utf8mb4_unicode_ci';

        // 1. Longgarkan dulu ke VARCHAR (charset/collation SAMA PERSIS dengan
        //    project_stages.code) supaya nilai lama tidak ditolak MySQL dan
        //    supaya FK di langkah 6 bisa terbentuk.
        DB::statement("ALTER TABLE `lops` MODIFY `status_progress` VARCHAR(50) CHARACTER SET {$charset} COLLATE {$collation} NULL DEFAULT 'preparation'");

        // 2. Migrasi nilai data lama ke kode baru.
        DB::table('lops')->where('status_progress', 'preparation')->update(['status_progress' => 'persiapan_instalasi']);
        // 'instalasi' dan 'finishing' sudah cocok apa adanya, tidak perlu diubah.

        // 3. Kolom penyimpan "tahap sebelum di-HOLD/DROP" supaya bisa resume.
        //    Kalau kolomnya BELUM ada, tambahkan. Kalau SUDAH ada (misalnya
        //    dari percobaan migrate sebelumnya yang sempat berhasil sampai
        //    sini sebelum gagal di FK, dengan charset/collation default
        //    tabel `lops` yang salah), paksa MODIFY supaya collation-nya
        //    pasti sama dengan project_stages.code -- kalau cuma di-skip,
        //    FK di langkah 6 akan tetap gagal karena collation lama masih
        //    nyangkut di kolom itu.
        if (!Schema::hasColumn('lops', 'status_progress_before_hold')) {
            DB::statement("ALTER TABLE `lops` ADD COLUMN `status_progress_before_hold` VARCHAR(50) CHARACTER SET {$charset} COLLATE {$collation} NULL AFTER `status_progress`");
        } else {
            DB::statement("ALTER TABLE `lops` MODIFY `status_progress_before_hold` VARCHAR(50) CHARACTER SET {$charset} COLLATE {$collation} NULL");
        }

        // 4. Kolom kategori perizinan terpilih (diisi saat LOP di tahap Perizinan).
        if (!Schema::hasColumn('lops', 'permit_category_id')) {
            DB::statement("ALTER TABLE `lops` ADD COLUMN `permit_category_id` BIGINT UNSIGNED NULL AFTER `status_progress_before_hold`");
        }

        // 5. Default baru untuk LOP yang belum ada status_progress-nya sama sekali.
        DB::statement("ALTER TABLE `lops` ALTER COLUMN `status_progress` SET DEFAULT 'inisiasi'");

        // 6. Tegakkan integritas: status_progress & status_progress_before_hold
        //    harus salah satu kode yang terdaftar di project_stages.
        //    Guard: skip kalau constraint-nya sudah ada dari percobaan sebelumnya.
        $this->addForeignKeyIfMissing(
            'lops',
            'lops_status_progress_foreign',
            "ALTER TABLE `lops` ADD CONSTRAINT `lops_status_progress_foreign` FOREIGN KEY (`status_progress`) REFERENCES `project_stages` (`code`)"
        );
        $this->addForeignKeyIfMissing(
            'lops',
            'lops_status_progress_before_hold_foreign',
            "ALTER TABLE `lops` ADD CONSTRAINT `lops_status_progress_before_hold_foreign` FOREIGN KEY (`status_progress_before_hold`) REFERENCES `project_stages` (`code`) ON DELETE SET NULL"
        );
        $this->addForeignKeyIfMissing(
            'lops',
            'lops_permit_category_id_foreign',
            "ALTER TABLE `lops` ADD CONSTRAINT `lops_permit_category_id_foreign` FOREIGN KEY (`permit_category_id`) REFERENCES `permit_categories` (`id`) ON DELETE SET NULL"
        );
    }

    public function down(): void
    {
        $this->dropForeignKeyIfExists('lops', 'lops_permit_category_id_foreign');
        $this->dropForeignKeyIfExists('lops', 'lops_status_progress_before_hold_foreign');
        $this->dropForeignKeyIfExists('lops', 'lops_status_progress_foreign');

        if (Schema::hasColumn('lops', 'permit_category_id')) {
            DB::statement("ALTER TABLE `lops` DROP COLUMN `permit_category_id`");
        }
        if (Schema::hasColumn('lops', 'status_progress_before_hold')) {
            DB::statement("ALTER TABLE `lops` DROP COLUMN `status_progress_before_hold`");
        }

        // Kembalikan data ke nilai lama sebelum enum di-restore.
        DB::table('lops')->where('status_progress', 'persiapan_instalasi')->update(['status_progress' => 'preparation']);
        DB::table('lops')->whereNotIn('status_progress', ['preparation', 'instalasi', 'finishing'])->update(['status_progress' => 'preparation']);

        DB::statement("ALTER TABLE `lops` MODIFY `status_progress` ENUM('preparation','instalasi','finishing') DEFAULT 'preparation'");
    }

    /**
     * Tambahkan FK hanya jika constraint dengan nama itu belum ada --
     * membuat migration ini aman dijalankan ulang setelah kegagalan parsial.
     */
    private function addForeignKeyIfMissing(string $table, string $constraintName, string $sql): void
    {
        $exists = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$table, $constraintName]
        );

        if ((int) ($exists->cnt ?? 0) === 0) {
            DB::statement($sql);
        }
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        $exists = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$table, $constraintName]
        );

        if ((int) ($exists->cnt ?? 0) > 0) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`");
        }
    }
};
