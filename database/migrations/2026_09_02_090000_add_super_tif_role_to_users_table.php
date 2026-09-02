<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan role baru "super_tif" ke kolom users.role.
 *
 * Sama seperti migration 2026_09_01_090000_add_superadmin_and_tif_roles_to_users_table.php
 * — kolom `role` kemungkinan besar ENUM (dibuat lewat import SQL manual, bukan lewat
 * migration Laravel). Migration ini mengecek dulu tipe kolom sebenarnya lewat
 * information_schema, dan hanya melebarkan ENUM kalau memang ENUM.
 *
 * Aman dijalankan berkali-kali (idempotent) dan tidak menyentuh data user yang ada.
 */
return new class extends Migration
{
    private const NEW_ROLES = ['super_tif'];

    public function up(): void
    {
        $column = $this->getRoleColumnInfo();

        if (!$column) {
            // Kolom role tidak ditemukan sama sekali — tidak melakukan apapun.
            return;
        }

        if (strtolower($column->DATA_TYPE) !== 'enum') {
            // Bukan ENUM (mis. VARCHAR bebas) -> tidak perlu ubah skema,
            // validasi role baru cukup di level aplikasi (controller).
            return;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $column->COLUMN_TYPE, $matches);
        $existingValues = $matches[1] ?? [];

        $changed = false;
        foreach (self::NEW_ROLES as $role) {
            if (!in_array($role, $existingValues, true)) {
                $existingValues[] = $role;
                $changed = true;
            }
        }

        if (!$changed) {
            // super_tif sudah ada di ENUM (migration pernah jalan sebelumnya).
            return;
        }

        $enumList = implode(',', array_map(
            fn ($v) => "'" . addslashes($v) . "'",
            $existingValues
        ));

        $nullable = strtoupper($column->IS_NULLABLE) === 'YES' ? '' : ' NOT NULL';
        $default = $column->COLUMN_DEFAULT !== null
            ? " DEFAULT '" . addslashes($column->COLUMN_DEFAULT) . "'"
            : '';

        DB::statement("ALTER TABLE users MODIFY role ENUM($enumList)$nullable$default");
    }

    public function down(): void
    {
        // Sengaja tidak di-revert otomatis: menyempitkan ENUM berisiko merusak
        // data kalau sudah ada user ber-role 'super_tif'. Kalau perlu di-rollback,
        // lakukan manual setelah memastikan tidak ada user dengan role tersebut.
    }

    private function getRoleColumnInfo(): ?object
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return null;
        }

        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        return $connection->selectOne(
            'SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$dbName, 'users', 'role']
        );
    }
};
