<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('evidence_revision_histories', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('evidence_id');
        $table->unsignedBigInteger('project_id');
        $table->unsignedBigInteger('reviewed_by')->nullable();

        $table->string('stage')->nullable();
        $table->string('evidence_type')->nullable();
        $table->text('review_note')->nullable();
        $table->string('status')->default('rejected');

        $table->timestamps();

        $table->foreign('evidence_id')
            ->references('id_evidence')
            ->on('evidences')
            ->onDelete('cascade');

        $table->foreign('project_id')
            ->references('id_project')
            ->on('projects')
            ->onDelete('cascade');
    });
}
};
