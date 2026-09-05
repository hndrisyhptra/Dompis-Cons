<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SCAFFOLD untuk kebutuhan permission granular di masa depan.
 *
 * CATATAN PENTING: tabel ini dibuat sebagai fondasi saja -- TIDAK ADA satu
 * pun logic otorisasi di aplikasi saat ini yang membaca tabel ini. Hasil
 * audit menunjukkan seluruh pengecekan akses saat ini berbentuk "apakah role
 * user termasuk salah satu dari [...]" langsung di controller (lihat
 * GisCadController::ALLOWED_ROLES, SurveyorController::ALLOWED_ROLES, dsb),
 * BUKAN sistem permission per-aksi. Membuat permission granular sungguhan
 * berarti mendefinisikan daftar permission baru dan mengganti seluruh
 * pengecekan role di ~12 controller -- perubahan besar yang sebaiknya jadi
 * keputusan terpisah, bukan bagian implisit dari refactor ENUM->tabel roles
 * ini. Tabel ini hanya menyiapkan tempatnya kalau nanti dibutuhkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('permission');
            $table->timestamps();

            $table->foreign('role_id')
                ->references('id_roles')->on('roles')
                ->cascadeOnDelete();

            $table->unique(['role_id', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
