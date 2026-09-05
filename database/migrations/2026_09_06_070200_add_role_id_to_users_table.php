<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah role_id (FK ke roles.id_roles) di tabel users, backfill dari kolom
 * role ENUM lama, lalu longgarkan constraint NOT NULL kolom role lama.
 *
 * PENTING — kolom `role` ENUM LAMA SENGAJA TIDAK DIHAPUS di migration ini:
 * - Supaya proses ini reversible/aman diperiksa dulu sebelum benar-benar
 *   menghapus kolom (lihat App\Models\User::role() -- accessor baru akan
 *   fallback ke kolom lama ini kalau role_id ternyata null untuk suatu baris).
 * - Menghapus kolom sebaiknya jadi migration TERPISAH, dijalankan manual
 *   setelah tim memverifikasi seluruh aplikasi berjalan normal memakai
 *   role_id (lihat catatan di laporan audit).
 *
 * Kolom `role` diubah jadi NULLABLE (tetap ENUM dengan value yang sama, tidak
 * ada value yang dihapus/diubah) karena aplikasi TIDAK AKAN MENULIS ke kolom
 * ini lagi mulai sekarang (User::role() accessor menulis ke role_id, bukan ke
 * kolom role fisik) -- kalau kolom ini masih NOT NULL tanpa default, insert
 * user baru akan gagal di level database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->nullable()->after('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->foreign('role_id')
                    ->references('id_roles')->on('roles')
                    ->nullOnDelete();
            });
        }

        // Backfill: cocokkan value ENUM lama (users.role) dengan roles.code.
        DB::statement(
            'UPDATE users u
             INNER JOIN roles r ON r.code = u.role
             SET u.role_id = r.id_roles
             WHERE u.role_id IS NULL'
        );

        // Longgarkan NOT NULL di kolom role lama (value & tipe ENUM tidak diubah).
        $column = DB::selectOne(
            "SELECT IS_NULLABLE, COLUMN_TYPE
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = 'users' AND column_name = 'role'",
            [DB::getDatabaseName()]
        );

        if ($column && strtoupper($column->IS_NULLABLE) === 'NO') {
            DB::statement("ALTER TABLE users MODIFY role {$column->COLUMN_TYPE} NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }

        // Sengaja tidak mengembalikan kolom role ke NOT NULL -- berisiko
        // gagal kalau ada baris yang role-nya sudah kosong (created setelah
        // migration ini jalan). Kembalikan manual setelah memastikan semua
        // baris punya value.
    }
};
