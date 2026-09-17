<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill istilah status_progress LOP PT2 ke istilah baru yang disamakan
 * dgn flow Reguler (inisiasi/survey/instalasi/finishing/fi_ogp_golive).
 * Lihat ANALISA_REFACTOR_PERSIAPAN.md Section BM.
 *
 * Mapping:
 *   preparation, persiapan          -> inisiasi
 *   progress                        -> instalasi
 *   finish, redaman, dismantle      -> finishing
 *   mancore, done, complete         -> fi_ogp_golive
 * (survey, golive, drop tidak berubah -- istilahnya sudah sama)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pt2_lops')
            ->whereIn('status_progress', ['preparation', 'persiapan'])
            ->update(['status_progress' => 'inisiasi']);

        DB::table('pt2_lops')
            ->where('status_progress', 'progress')
            ->update(['status_progress' => 'instalasi']);

        DB::table('pt2_lops')
            ->whereIn('status_progress', ['finish', 'redaman', 'dismantle'])
            ->update(['status_progress' => 'finishing']);

        DB::table('pt2_lops')
            ->whereIn('status_progress', ['mancore', 'done', 'complete'])
            ->update(['status_progress' => 'fi_ogp_golive']);
    }

    public function down(): void
    {
        // Sengaja no-op: beberapa istilah lama digabung jadi satu istilah baru
        // (mis. finish & dismantle sama-sama jadi finishing), jadi rollback
        // 1:1 tidak bisa merekonstruksi nilai asli secara akurat.
    }
};
