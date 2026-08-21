<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Catatan: tabel ini sudah dibuat manual lewat phpMyAdmin (lihat komentar di
     * masing-masing kolom), jadi migration ini di-skip otomatis jika tabel sudah
     * ada. Definisi di bawah tetap dijaga akurat agar instalasi baru (fresh
     * database) tetap bisa membuat tabel yang sama persis lewat `php artisan migrate`.
     */
    public function up(): void
    {
        if (Schema::hasTable('site_surveys')) {
            return;
        }

        Schema::create('site_surveys', function (Blueprint $table) {
            $table->id('id_site_surveys');

            // Boleh dikaitkan ke project yang sudah ada, atau diisi manual (free text)
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('project_name')->nullable();

            $table->string('title');
            $table->unsignedBigInteger('surveyor_id');

            // draft = masih berjalan di lapangan, completed = sudah selesai & KML final
            $table->string('status')->default('draft');

            $table->text('notes')->nullable();

            // Titik akhir (ending site) survey
            $table->decimal('ending_site_lat', 10, 7)->nullable();
            $table->decimal('ending_site_lng', 10, 7)->nullable();
            $table->string('ending_site_name')->nullable();

            $table->string('kml_path')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('project_id');
            $table->index('surveyor_id');
            $table->index('status');

            $table->foreign('project_id')
                ->references('id_project')->on('projects')
                ->nullOnDelete();

            $table->foreign('surveyor_id')
                ->references('id_user')->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_surveys');
    }
};
