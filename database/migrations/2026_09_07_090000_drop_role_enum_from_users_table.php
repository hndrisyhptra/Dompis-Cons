<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration LANJUTAN (opsional, jalankan kapan saja Dom sudah yakin) --
 * menghapus permanen kolom `role` ENUM lama dari tabel users, setelah
 * dipastikan seluruh aplikasi berjalan normal memakai role_id (lihat
 * add_role_id_to_users_table).
 *
 * Sengaja dipisah dari migration sebelumnya supaya bisa dites dulu sebelum
 * datanya hilang permanen. Migration ini PUNYA GUARD: akan MENOLAK jalan
 * (throw exception, tidak menghapus apa pun) kalau masih ada user yang
 * role_id-nya NULL -- supaya tidak ada user yang kehilangan info role-nya
 * kalau ternyata proses backfill belum lengkap.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            return; // sudah pernah dihapus sebelumnya, aman di-skip
        }

        $missing = DB::table('users')->whereNull('role_id')->count();

        if ($missing > 0) {
            throw new \RuntimeException(
                "Migration dibatalkan: masih ada {$missing} user yang role_id-nya NULL. " .
                'Cek dengan: SELECT id_user, nik, name, role FROM users WHERE role_id IS NULL; ' .
                'lalu perbaiki dulu (pastikan role-nya cocok dengan salah satu code di tabel roles) ' .
                'sebelum menjalankan migration ini lagi.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('role_id');
        });

        // Catatan: kolom dikembalikan sebagai VARCHAR nullable (bukan ENUM
        // asli) -- down() ini untuk emergency rollback saja, bukan replika
        // 100% skema lama. Isinya tetap dikembalikan lewat relasi role_id.
        DB::statement(
            'UPDATE users u
             INNER JOIN roles r ON r.id_roles = u.role_id
             SET u.role = r.code'
        );
    }
};
