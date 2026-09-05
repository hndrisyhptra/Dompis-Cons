<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isi tabel roles dengan 9 role yang sudah dipakai aktif di aplikasi (hasil
 * audit: ditemukan di ENUM users.role, di 12 controller, dan di 20+ file
 * Blade). `name` diambil dari 2 array label yang sebelumnya duplikat:
 * admin/users/index.blade.php ($roleNames) dan
 * components/profile-account-menu.blade.php ($__roleLabels) — isinya identik,
 * jadi disatukan di sini sebagai satu sumber kebenaran.
 *
 * Idempotent: pakai updateOrInsert supaya aman dijalankan ulang / di-deploy
 * ke environment yang mungkin sebagian datanya sudah ada.
 */
return new class extends Migration
{
    private const ROLES = [
        ['code' => 'admin', 'name' => 'Approval', 'description' => 'Approval project & eviden regular (PT 3), akses dashboard Admin penuh.'],
        ['code' => 'superadmin', 'name' => 'Super Admin', 'description' => 'Seperti Admin, ditambah akses User Management. Tidak melihat menu Inbox.'],
        ['code' => 'waspang', 'name' => 'Inputer', 'description' => 'Input progress & upload eviden project regular (PT 3) di lapangan.'],
        ['code' => 'pm', 'name' => 'PM', 'description' => 'Project Manager — memantau & mengelola project yang di-assign ke tim.'],
        ['code' => 'tif', 'name' => 'TIF', 'description' => 'Sama seperti PM, tanpa akses ke Program Konstruksi Eksternal.'],
        ['code' => 'super_tif', 'name' => 'Super TIF', 'description' => 'Seperti Admin, tanpa Program/Project Konstruksi Eksternal (EKSBIS) & tanpa User Management.'],
        ['code' => 'teknisi', 'name' => 'Inputer PT2', 'description' => 'Input progress & upload eviden project PT2.'],
        ['code' => 'sdi', 'name' => 'SDI', 'description' => 'Approval & monitoring hasil site survey.'],
        ['code' => 'sdi_surveyor', 'name' => 'SDI Surveyor', 'description' => 'Input hasil survey lapangan (fitur ini sekarang menyatu ke role Waspang; dipertahankan untuk akun lama).'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::ROLES as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('code', array_column(self::ROLES, 'code'))->delete();
    }
};
