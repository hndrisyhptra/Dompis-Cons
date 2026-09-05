<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom untuk fitur "Log Activity" per user di User Management:
 * - last_login_at   : diisi setiap kali user berhasil login (lihat
 *                      AuthenticatedSessionController::store).
 * - last_activity_at : diisi lewat middleware App\Http\Middleware\TrackLastActivity,
 *                      di-throttle (tidak setiap request) supaya tidak membebani DB.
 *
 * created_at (kolom bawaan users, sudah ada) dipakai sebagai "Terdaftar".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter(['last_login_at', 'last_activity_at'], fn ($c) => Schema::hasColumn('users', $c));
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
