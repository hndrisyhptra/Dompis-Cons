<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel tracking untuk fitur "GIS to CAD Generator" (KML/KMZ -> DXF
     * AutoCAD). Mengikuti pola yang sama seperti `import_processes`:
     * 1 baris = 1 kali proses generate, dengan status queued/processing/
     * completed/failed supaya bisa dipantau & di-log kalau gagal.
     *
     * TIDAK menyimpan ulang data titik/rute survey (itu tetap di
     * site_survey_points / site_survey_routes yang sudah ada) - hanya
     * menyimpan path dataset ternormalisasi (JSON) + hasil akhirnya.
     */
    public function up(): void
    {
        if (Schema::hasTable('gis_cad_exports')) {
            return;
        }

        Schema::create('gis_cad_exports', function (Blueprint $table) {
            $table->id('id_gis_cad_export');

            $table->uuid('uuid')->unique();

            // kml_upload = user upload file KML/KMZ, site_survey = pakai data survey existing
            $table->string('source_type');

            // Diisi kalau source_type = site_survey
            $table->unsignedBigInteger('site_survey_id')->nullable();

            // Boleh dikaitkan ke project (opsional, untuk labeling & filter riwayat)
            $table->unsignedBigInteger('project_id')->nullable();

            // standard_fttx (default) | custom
            $table->string('template')->default('standard_fttx');

            // Hanya diisi kalau source_type = kml_upload
            $table->string('original_file_name')->nullable();
            $table->string('uploaded_file_path')->nullable();

            // Dataset hasil parsing/klasifikasi (JSON) sebelum di-generate ke DXF.
            // Dipakai untuk layar review manual (user cek/koreksi tipe titik)
            // sebelum tombol "Generate DXF" final ditekan.
            $table->string('dataset_path')->nullable();

            // Hasil akhir
            $table->string('dxf_path')->nullable();
            $table->string('bom_path')->nullable();

            $table->string('disk')->default('public');

            // draft (dataset sudah di-parse, nunggu user review/pilih template) |
            // queued -> processing -> completed | failed
            $table->string('status')->default('draft');
            $table->string('current_stage')->nullable();

            $table->unsignedInteger('points_count')->nullable();
            $table->unsignedInteger('polylines_count')->nullable();
            $table->string('utm_zone')->nullable();

            $table->text('error_message')->nullable();

            $table->unsignedBigInteger('requested_by');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index('source_type');
            $table->index('site_survey_id');
            $table->index('project_id');
            $table->index('status');
            $table->index('requested_by');

            $table->foreign('site_survey_id')
                ->references('id_site_surveys')->on('site_surveys')
                ->nullOnDelete();

            $table->foreign('project_id')
                ->references('id_project')->on('projects')
                ->nullOnDelete();

            $table->foreign('requested_by')
                ->references('id_user')->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gis_cad_exports');
    }
};
