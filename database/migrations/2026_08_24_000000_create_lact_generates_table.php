<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel penyimpanan hasil generate dokumen LACT (Laporan Commissioning
     * Test) per LOP PT2. Struktur identik dengan baut_generates (lihat
     * 2026_08_22_000000_create_baut_generates_table.php) -- 1 baris = 1
     * dokumen (draft atau final) untuk 1 LOP. LACT adalah dokumen LANJUTAN
     * dari BAUT (baru bisa digenerate setelah baut_generates LOP ybs
     * berstatus final), tapi disimpan di tabel terpisah supaya riwayat
     * draft/final-nya independen dari BAUT.
     */
    public function up(): void
    {
        if (Schema::hasTable('lact_generates')) {
            return;
        }

        Schema::create('lact_generates', function (Blueprint $table) {
            $table->id('id_lact_generate');

            $table->unsignedBigInteger('pt2_lop_id');
            $table->unsignedBigInteger('pt2_project_id')->nullable();

            // draft = masih diedit admin di editor, final = sudah di-generate jadi .docx
            $table->string('status')->default('draft');

            // Field teks LACT (proyek, kontrak, surat_pesanan, witel, lokasi,
            // tempat_tanggal, nama_penandatangan, nik_penandatangan,
            // jabatan_penandatangan, sehubungan_dengan, status_pelaksanaan,
            // status_diterima, status_kelayakan)
            $table->json('field_values')->nullable();

            // Snapshot item BOQ Commissioning Test saat digenerate
            $table->json('boq_snapshot')->nullable();

            // Jumlah slot foto OPM (mengikuti jumlah eviden redaman_port
            // approved LOP ybs -- sama seperti BAUT)
            $table->unsignedInteger('opm_slot_count')->default(1);

            // Mapping slot foto -> id eviden PT2 / caption. Termasuk 6 slot
            // tetap eviden_pekerjaan_a..f (Lampiran Evident Pekerjaan),
            // opm_1..N, distribusi_odc, mancore.
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
        Schema::dropIfExists('lact_generates');
    }
};
