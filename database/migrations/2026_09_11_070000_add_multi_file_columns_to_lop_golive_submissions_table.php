<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi (permintaan user): upload eviden FI-OGP Golive sekarang boleh
 * MULTIPLE file per kategori (Capture Valins / PDF ABD & Valid4 / KML /
 * Mancore), bukan cuma 1 file. Ditambah kolom *_paths (JSON, array path)
 * BARU di samping kolom lama *_path (string, 1 path) -- kolom lama TIDAK
 * dihapus/diubah tipenya (hindari ->change(), doctrine/dbal belum
 * terpasang di project ini) supaya data lama & kode lain yang masih baca
 * kolom lama tetap jalan. Kolom lama otomatis ikut disinkronkan ke file
 * TERAKHIR yang diupload (lihat ProjectController::submitGoliveDocuments())
 * -- murni utk kompatibilitas mundur, bukan sumber data utama lagi.
 *
 * Revisi lanjutan (permintaan user): kolom `fi_completed_at` -- momen
 * admin SELESAI upload ke-4 kategori dokumen (isComplete() jadi true
 * PERTAMA KALINYA). Dipakai sbg "Tanggal FI" di tabel Approval Golive
 * SDI (PT 2 & PT 3), TIDAK sama dgn `submitted_at` (yg berubah tiap
 * kali save meskipun cuma partial/re-upload).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lop_golive_submissions', function (Blueprint $table) {
            $table->json('capture_valins_paths')->nullable()->after('capture_valins_path');
            $table->json('abd_valid4_paths')->nullable()->after('abd_valid4_path');
            $table->json('kml_paths')->nullable()->after('kml_path');
            $table->json('mancore_paths')->nullable()->after('mancore_path');
            $table->timestamp('fi_completed_at')->nullable()->after('mancore_input_type');
        });

        // Backfill: data lama (1 path per kolom) dibungkus jadi array 1
        // elemen di kolom baru, supaya submission yang sudah pernah
        // diupload sebelum revisi ini tetap terhitung lengkap/tidak hilang.
        DB::table('lop_golive_submissions')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $update = [];

                if (! empty($row->capture_valins_path)) {
                    $update['capture_valins_paths'] = json_encode([$row->capture_valins_path]);
                }
                if (! empty($row->abd_valid4_path)) {
                    $update['abd_valid4_paths'] = json_encode([$row->abd_valid4_path]);
                }
                if (! empty($row->kml_path)) {
                    $update['kml_paths'] = json_encode([$row->kml_path]);
                }
                if (! empty($row->mancore_path)) {
                    $update['mancore_paths'] = json_encode([$row->mancore_path]);
                }

                if (! empty($update)) {
                    DB::table('lop_golive_submissions')->where('id', $row->id)->update($update);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('lop_golive_submissions', function (Blueprint $table) {
            $table->dropColumn(['capture_valins_paths', 'abd_valid4_paths', 'kml_paths', 'mancore_paths', 'fi_completed_at']);
        });
    }
};
