<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master role — pengganti kolom users.role yang sebelumnya ENUM.
 *
 * `code` menyimpan nilai yang PERSIS SAMA dengan value ENUM lama (mis. 'waspang',
 * 'super_tif') supaya proses migrasi data (lihat migration berikutnya,
 * add_role_id_to_users_table) dan seluruh kode aplikasi yang masih membaca
 * $user->role sebagai string tetap kompatibel tanpa perlu redesain ulang.
 *
 * `name` adalah label tampilan (menggantikan 2 array duplikat $roleNames /
 * $__roleLabels yang sebelumnya di-hardcode terpisah di
 * admin/users/index.blade.php dan components/profile-account-menu.blade.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_roles');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
