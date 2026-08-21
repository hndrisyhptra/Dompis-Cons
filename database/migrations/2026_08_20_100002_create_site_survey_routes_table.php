<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Catatan: tabel ini sudah dibuat manual lewat phpMyAdmin, jadi migration ini
     * di-skip otomatis jika tabel sudah ada. Definisi tetap dijaga akurat agar
     * instalasi baru (fresh database) tetap bisa membuat tabel yang sama persis.
     */
    public function up(): void
    {
        if (Schema::hasTable('site_survey_routes')) {
            return;
        }

        Schema::create('site_survey_routes', function (Blueprint $table) {
            $table->id('id_site_survey_routes');

            $table->foreignId('site_survey_id')
                ->constrained('site_surveys', 'id_site_surveys')
                ->cascadeOnDelete();

            $table->string('name')->default('Rute Kabel');

            // Array koordinat berurutan: [[lat, lng], [lat, lng], ...]
            $table->json('path');

            $table->decimal('distance_meters', 12, 2)->nullable();
            $table->integer('order_index')->default(0);

            $table->timestamps();

            $table->index('site_survey_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_survey_routes');
    }
};
