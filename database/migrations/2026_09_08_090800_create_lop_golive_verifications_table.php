<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step "Golive" (role SDI): verifikasi atas lop_golive_submissions yang
 * sudah lengkap, upload capture UIM, lalu toggle status LOP jadi 'golive'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lop_golive_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lop_id')->unique();
            $table->string('capture_uim_path')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('lop_id')->references('id_lop')->on('lops')->onDelete('cascade');
            $table->foreign('verified_by')->references('id_user')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lop_golive_verifications');
    }
};
