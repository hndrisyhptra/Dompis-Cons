<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master jenis/kategori perizinan -- dipilih waspang saat LOP berada
 * di status_progress 'perizinan'. Dikelola admin lewat UI (tambah/nonaktifkan
 * tanpa ubah kode), sama seperti kendala_categories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        $names = [
            'NO ISSUE',
            'PERIZINAN PU NASIONAL',
            'PERIZINAN PU PROVINSI',
            'PERIZINAN PU KABUPATEN',
            'PERIZINAN PU KOTA',
            'PERIZINAN WARGA/RT/RW',
            'PERIZINAN KOMPLEK/CLUSTER',
            'PERIZINAN KELURAHAN / KECAMATAN',
            'PERIZINAN ADAT SETEMPAT',
            'PERIZINAN GEDUNG/HRB',
            'PERIZINAN PRIVATE AREA/KAWASAN KHUSUS',
            'PERIZINAN INSTANSI',
        ];

        foreach ($names as $index => $name) {
            DB::table('permit_categories')->insert([
                'name' => $name,
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_categories');
    }
};
