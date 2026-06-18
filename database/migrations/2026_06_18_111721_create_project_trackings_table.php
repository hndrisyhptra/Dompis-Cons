<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_trackings', function (Blueprint $table) {
             $table->id('id_tracking');

    $table->bigInteger('project_id');
    $table->bigInteger('user_id')->nullable();

    $table->string('activity_type')->nullable();
    $table->string('title');
    $table->text('description')->nullable();

    $table->timestamps();

    $table->index('project_id');
    $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_trackings');
    }
};