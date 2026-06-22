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
        Schema::create('project_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('lop_id')->nullable();
            $table->unsignedBigInteger('user_id');

            $table->string('issue_type')->nullable();
            $table->text('description');
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->text('resolution_note')->nullable();

            $table->timestamps();

            $table->index('project_id');
            $table->index('lop_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_issues');
    }
};
