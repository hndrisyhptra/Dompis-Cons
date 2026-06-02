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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('id_notification');

    $table->unsignedBigInteger('user_id');

    $table->unsignedBigInteger('project_id')->nullable();

    $table->enum('type', [
        'reject',
        'approved',
        'reminder',
        'ready_ut'
    ]);

    $table->string('title');

    $table->text('message');

    $table->boolean('is_read')->default(false);

    $table->timestamp('read_at')->nullable();

    $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
