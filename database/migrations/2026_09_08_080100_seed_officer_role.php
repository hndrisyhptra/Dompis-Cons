<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role baru "officer": menu-nya sama persis dengan admin, kecuali Master
 * Designator, Bulk Import Data, dan Approval Eviden (dihapus), ditambah
 * User Management (lihat resources/views/officer/components/sidebar.blade.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(
            ['code' => 'officer'],
            [
                'name' => 'Officer',
                'description' => 'Menu sama seperti Admin (kecuali Master Designator, Bulk Import Data, Approval Eviden), ditambah akses User Management terbatas (tidak bisa ubah username/password user lain, tanpa Log Activity).',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('roles')->where('code', 'officer')->delete();
    }
};
