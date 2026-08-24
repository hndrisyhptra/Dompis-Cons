<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penyimpanan hasil generate dokumen BAUT (Berita Acara Uji Terima)
     * per LOP PT2. Setiap baris merepresentasikan 1 dokumen (draft atau final)
     * untuk 1 LOP (id_pt2_lop) -- sesuai keputusan scope: generate per LOP,
     * bukan gabungan banyak LOP dalam 1 dokumen.
     */
    public function up(): void
    {
        if (Schema::hasTable('baut_generates')) {
            return;
        }

        Schema::create('baut_generates', function (Blueprint $table) {
            $table->id('id_baut_generate');

            $table->unsignedBigInteger('pt2_lop_id');
            $table->unsignedBigInteger('pt2_project_id')->nullable();

            // draft = masih diedit admin di editor, final = sudah di-generate jadi .docx
            $table->string('status')->default('draft');

            // Field teks BAUT (proyek, kontrak, surat_pesanan, district, lokasi,
            // tempat_tanggal, nama_tii, nik_tii, nama_akses, nik_akses,
            // tanggal_uji_terima, uraian_pekerjaan, keputusan_uji_terima)
            $table->json('field_values')->nullable();

            // Snapshot item BOQ Uji Terima saat digenerate (agar dokumen lama
            // tidak berubah walau data BOQ project diedit belakangan)
            $table->json('boq_snapshot')->nullable();

            // Jumlah slot foto OPM (mengikuti rasio expand splitter LOP ybs)
            $table->unsignedInteger('opm_slot_count')->default(1);

            // Mapping slot foto -> id eviden PT2 / path custom + caption.
            // Contoh: {"eviden_a": {"evidence_id": 12, "caption": "..."}, "opm_1": {...}, ...}
            $table->json('photo_slots')->nullable();

            // Path file .docx hasil generate final (disk 'public')
            $table->string('generated_file_path')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->unsignedBigInteger('generated_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index('pt2_lop_id');
            $table->index('status');

            $table->foreign('pt2_lop_id')
                ->references('id_pt2_lop')->on('pt2_lops')
                ->cascadeOnDelete();

            $table->foreign('pt2_project_id')
                ->references('id_pt2_project')->on('pt2_projects')
                ->nullOnDelete();

            $table->foreign('generated_by')
                ->references('id_user')->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id_user')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baut_generates');
    }
};
