<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4e (lanjutan) -- Re Survey. Lihat ANALISA_REFACTOR_PERSIAPAN.md
 * bagian X untuk spec asal.
 *
 * boq_items TETAP satu baris per designator per LOP (quantity_actual selalu
 * berisi angka HASIL SURVEY TERBARU) -- tidak diduplikasi per ronde, supaya
 * Dashboard PM, BAUT/LACT, evidence (boq_item_id), dan kalkulasi harga yang
 * sudah bergantung padanya tidak terganggu.
 *
 * Histori tiap ronde Survey (round 1 = Survey pertama, round 2+ = tiap kali
 * waspang menekan tombol "Re Survey") disimpan TERPISAH di 2 tabel baru ini:
 * - boq_survey_rounds: 1 baris per ronde per LOP (header: status, total
 *   nominal plan/survey, deviasi, siapa & kapan mulai/selesai).
 * - boq_survey_round_items: snapshot tiap baris boq_items PADA SAAT ronde
 *   itu selesai (quantity_survey = quantity_actual boq_items saat itu),
 *   supaya nilai lama tidak ikut tertimpa saat ronde berikutnya berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('boq_survey_rounds')) {
            Schema::create('boq_survey_rounds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lop_id');
                $table->unsignedInteger('round_number');
                // in_progress -> waspang sedang input volume ronde ini.
                // completed  -> volume final (langsung/sesudah Approval
                // Redesign) & snapshot item sudah dibuat.
                $table->string('status', 20)->default('in_progress');
                $table->decimal('plan_total', 18, 2)->nullable();
                $table->decimal('survey_total', 18, 2)->nullable();
                $table->decimal('deviation_percent', 8, 2)->nullable();
                $table->boolean('redesign_required')->default(false);
                $table->unsignedBigInteger('started_by')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->unsignedBigInteger('finished_by')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->unique(['lop_id', 'round_number']);
                $table->foreign('lop_id')->references('id_lop')->on('lops')->onDelete('cascade');
                $table->foreign('started_by')->references('id_user')->on('users')->onDelete('set null');
                $table->foreign('finished_by')->references('id_user')->on('users')->onDelete('set null');
            });
        }

        if (! Schema::hasTable('boq_survey_round_items')) {
            Schema::create('boq_survey_round_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('boq_survey_round_id');
                $table->unsignedBigInteger('boq_item_id')->nullable();
                $table->unsignedBigInteger('designator_id')->nullable();
                $table->string('designator')->nullable();
                $table->string('item_name')->nullable();
                $table->string('unit', 30)->nullable();
                $table->decimal('quantity_plan', 18, 2)->nullable();
                $table->decimal('quantity_survey', 18, 2)->nullable();
                $table->timestamps();

                $table->index('boq_survey_round_id');
                $table->foreign('boq_survey_round_id')->references('id')->on('boq_survey_rounds')->onDelete('cascade');
                // boq_item_id sengaja TANPA foreign key -- baris boq_items
                // bisa saja dihapus (mis. item tambahan Survey lama), tapi
                // snapshot histori harus tetap utuh.
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('boq_survey_round_items');
        Schema::dropIfExists('boq_survey_rounds');
    }
};
