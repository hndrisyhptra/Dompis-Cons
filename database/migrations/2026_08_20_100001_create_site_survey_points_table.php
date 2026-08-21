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
        if (Schema::hasTable('site_survey_points')) {
            return;
        }

        Schema::create('site_survey_points', function (Blueprint $table) {
            $table->id('id_site_survey_points');

            $table->foreignId('site_survey_id')
                ->constrained('site_surveys', 'id_site_surveys')
                ->cascadeOnDelete();

            // tiang_eksisting | catuan | ending_site
            $table->string('type');

            // Hanya diisi jika type = catuan -> ODC | ODP | JC
            $table->string('catuan_type')->nullable();

            $table->string('name')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();

            $table->integer('order_index')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['site_survey_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_survey_points');
    }
};
