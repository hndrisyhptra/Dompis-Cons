<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BUG DITEMUKAN SAAT TESTING Stage 4d: `evidences.stage` ternyata kolom
 * ENUM legacy (tabel `evidences` diimport manual dari SQL lama, tidak ada
 * migration Schema::create()-nya -- sama seperti `boq_items`) yang cuma
 * mengizinkan 4 nilai lama ('persiapan','instalasi','pengukuran','finishing').
 *
 * Waktu waspang upload eviden di sub-step BARU (mis. stage='drm'), insert
 * GAGAL dgn "SQLSTATE[01000]: Warning: 1265 Data truncated for column
 * 'stage'" (MySQL strict mode menolak nilai ENUM yg tak dikenal) --
 * validasi Laravel di WaspangController::uploadEvidence() LOLOS (sudah
 * dilebarkan sebelumnya) tapi DB-nya sendiri belum ikut dilebarkan.
 *
 * Perbaikan: ganti kolom dari ENUM sempit jadi VARCHAR(50) bebas -- supaya
 * TIDAK perlu migration lagi setiap kali ada stage/step baru di masa depan
 * (drpd melebarkan daftar ENUM satu-satu berulang kali).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `evidences` MODIFY COLUMN `stage` VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        // Balik ke ENUM lama HANYA aman kalau tidak ada baris dgn stage
        // baru (survey/drm/perizinan/material_delivery) -- migration ini
        // tidak dirancang utk rollback produksi, cuma dev/local.
        DB::statement("ALTER TABLE `evidences` MODIFY COLUMN `stage` ENUM('persiapan','instalasi','pengukuran','finishing') NOT NULL");
    }
};
