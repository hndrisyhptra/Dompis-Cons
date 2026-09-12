<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Revisi (permintaan user): "Tanggal FI" masih kosong di tabel Approval
 * Golive PT 3 -- penyebabnya, kolom `fi_completed_at` HANYA diisi lewat
 * jalur baru (ProjectController::submitGoliveDocuments()) SAAT dokumen
 * PERTAMA KALI jadi lengkap SETELAH kode itu di-deploy. LOP yang dokumen
 * FI-OGP-nya sudah lengkap SEBELUM revisi ini tidak pernah lewat jalur
 * itu lagi -- jadi `fi_completed_at`-nya tetap NULL selamanya kalau tidak
 * di-backfill.
 *
 * Migration ini backfill SEKALI SAJA (bukan berjalan tiap saat) utk
 * submission/LOP yang SUDAH lengkap tapi `fi_completed_at` masih NULL:
 * - `lop_golive_submissions` (PT 3): lengkap = ke-4 kolom *_path (legacy,
 *   selalu disinkron ke file TERAKHIR oleh appendFiles()) sudah terisi.
 *   Dipakai `submitted_at` sbg proxy -- itu timestamp save TERAKHIR, jadi
 *   kalau submission-nya belum disentuh lagi setelah lengkap, nilainya
 *   sama persis dgn momen jadi lengkap. Kalau sempat di-update lagi
 *   setelah lengkap (mis. tambah file lain), maka ini cuma perkiraan
 *   (lebih baik drpd kosong).
 * - `pt2_lops` (PT 2, sekalian dibereskan drpd nanti ada laporan sama):
 *   proxy "selesai upload FI" = LOP sudah pernah dikirim ke SDI
 *   (`sdi_approval_status` terisi). Dipakai `updated_at` sbg perkiraan
 *   krn tidak ada log historis kapan tepatnya `sendToSdi()` dipanggil
 *   utk row lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('lop_golive_submissions', 'fi_completed_at')) {
            DB::table('lop_golive_submissions')
                ->whereNull('fi_completed_at')
                ->whereNotNull('capture_valins_path')
                ->whereNotNull('abd_valid4_path')
                ->whereNotNull('kml_path')
                ->whereNotNull('mancore_path')
                ->update(['fi_completed_at' => DB::raw('submitted_at')]);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('pt2_lops', 'fi_completed_at')) {
            DB::table('pt2_lops')
                ->whereNull('fi_completed_at')
                ->whereNotNull('sdi_approval_status')
                ->update(['fi_completed_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        // Backfill tidak reversible dgn aman (tidak tahu mana yg asli
        // NULL vs hasil backfill) -- sengaja no-op.
    }
};
