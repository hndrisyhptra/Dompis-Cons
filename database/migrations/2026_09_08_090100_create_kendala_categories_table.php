<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master daftar flagging kendala -- dipakai di SEMUA step (bukan cuma
 * Persiapan), dikelola admin lewat UI (tambah/nonaktifkan tanpa perlu ubah
 * kode). project_issues.kendala_category_id akan FK ke tabel ini.
 *
 * Daftar awal (2026-09-08) sesuai permintaan pemilik project -- ini daftar
 * SEMENTARA, admin bisa menambah lewat UI kelola master nantinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kendala_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        $names = [
            'SEWA RECURRING',
            'ODC FULL',
            'TERCOVER ALPRO EKSISTING',
            'CLUSTER BELUM SIAP',
            'MATERIAL',
            'PERIZINAN',
            'CORE FEEDER HABIS',
            'CORE DISTRIBUSI HABIS',
            'MINI OLT PENUH',
            'MENUNGGU PEKERJAAN LAIN',
            'WAITING MINI OLT CSF',
            'BENCANA ALAM',
            'PELANGGAN BATAL',
            'DUPLIKAT ORDER',
            'DEMAND RENDAH',
            'NEED REPAIR CORE EXISTING',
            'REDESIGN >10%',
            'PENOLAKAN PERIZINAN',
            'PERMIT SITE',
            'KENDALA BUDGET',
            'CRQ / CRA',
            'READINESS SITE',
            'COMMCASE',
            'BUTUH MINI OLT',
            'KENDALA TANAM TIANG',
            'OVER CPP',
            'PROVIDER LAIN/LOKAL',
            'CROSSING KAI',
            'KOMPENSASI TINGGI',
            'NEED INSERT MODUL OLT',
            'REDAMAN TINGGI',
            'BELUM ADA PKS DEVELOPER',
        ];

        foreach ($names as $index => $name) {
            DB::table('kendala_categories')->insert([
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
        Schema::dropIfExists('kendala_categories');
    }
};
