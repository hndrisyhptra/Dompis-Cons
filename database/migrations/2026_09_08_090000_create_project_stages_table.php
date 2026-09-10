<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master untuk daftar status_progress LOP reguler (PT3).
 *
 * Ini menggantikan enum hardcode 'preparation'/'instalasi'/'finishing' di
 * kolom lops.status_progress dengan daftar yang bisa dikelola admin lewat
 * UI, tanpa perlu migration setiap kali ada status baru.
 *
 * `code` adalah nilai yang sungguhan disimpan di lops.status_progress
 * (dan di-FK-kan ke sini, lihat migration lops.status_progress berikutnya).
 *
 * `is_pause_type` menandai status yang sifatnya jeda (HOLD) -- bukan bagian
 * dari urutan alur normal, harus bisa "resume" ke status sebelumnya.
 * `is_terminal` menandai status yang keluar dari alur normal (DROP) --
 * bukan "final tidak bisa dibatalkan", tapi "berhenti dari urutan alur
 * sampai admin memutuskan lain": dikonfirmasi pemilik project bahwa sebuah
 * LOP yang di-DROP tetap bisa di-reset kembali ke status sebelumnya di
 * kemudian hari (pakai kolom `lops.status_progress_before_hold`, kolom yang
 * sama dipakai baik untuk resume dari HOLD maupun dari DROP).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_stages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->string('phase_group', 50)->nullable();
            $table->unsignedInteger('sequence')->nullable();
            $table->string('color', 30)->nullable();
            $table->boolean('is_pause_type')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();

        $rows = [
            ['code' => 'inisiasi', 'label' => 'Inisiasi', 'phase_group' => 'persiapan', 'sequence' => 1, 'color' => 'slate'],
            ['code' => 'survey', 'label' => 'Survey', 'phase_group' => 'persiapan', 'sequence' => 2, 'color' => 'slate'],
            ['code' => 'drm', 'label' => 'Proses DRM', 'phase_group' => 'persiapan', 'sequence' => 3, 'color' => 'slate'],
            ['code' => 'perizinan', 'label' => 'Perizinan', 'phase_group' => 'persiapan', 'sequence' => 4, 'color' => 'amber'],
            ['code' => 'material_delivery', 'label' => 'Material Delivery', 'phase_group' => 'persiapan', 'sequence' => 5, 'color' => 'slate'],
            ['code' => 'persiapan_instalasi', 'label' => 'Persiapan Instalasi', 'phase_group' => 'persiapan_instalasi', 'sequence' => 6, 'color' => 'blue'],
            ['code' => 'instalasi', 'label' => 'Instalasi', 'phase_group' => 'instalasi', 'sequence' => 7, 'color' => 'blue'],
            ['code' => 'pengukuran', 'label' => 'Pengukuran', 'phase_group' => 'pengukuran', 'sequence' => 8, 'color' => 'indigo'],
            ['code' => 'finishing', 'label' => 'Finishing', 'phase_group' => 'finishing', 'sequence' => 9, 'color' => 'emerald'],
            ['code' => 'fi_ogp_golive', 'label' => 'FI-OGP Golive', 'phase_group' => 'golive', 'sequence' => 10, 'color' => 'purple'],
            ['code' => 'golive', 'label' => 'Golive', 'phase_group' => 'golive', 'sequence' => 11, 'color' => 'green'],
            ['code' => 'hold', 'label' => 'Hold', 'phase_group' => 'pause', 'sequence' => null, 'color' => 'orange', 'is_pause_type' => true],
            ['code' => 'drop', 'label' => 'Drop', 'phase_group' => 'pause', 'sequence' => null, 'color' => 'red', 'is_terminal' => true],
        ];

        foreach ($rows as $row) {
            DB::table('project_stages')->insert(array_merge([
                'is_pause_type' => false,
                'is_terminal' => false,
                'is_active' => true,
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $row));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_stages');
    }
};
