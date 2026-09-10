<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware role-based backend (2026-09-08) -- sebelumnya SAMA SEKALI tidak
 * ada pengecekan role di level route, cuma 'auth' (siapapun yang login bisa
 * membuka route siapapun kalau tahu URL-nya). Beberapa controller sudah
 * punya guard manual sendiri (GisCadController, SurveyorController,
 * DashboardController, UserManagementController) -- middleware ini
 * menyamakan pola itu supaya berlaku konsisten di SEMUA route, termasuk yang
 * sebelumnya tidak dijaga sama sekali.
 *
 * Pemakaian di routes: ->middleware('role:admin,superadmin,super_tif')
 * Daftar role valid ada di tabel `roles` (lihat Role.php) -- middleware ini
 * sengaja menerima kode role sebagai string biasa (bukan divalidasi ke tabel
 * roles saat request masuk) supaya tidak menambah query per-request; salah
 * ketik nama role di route cukup berakibat "role itu tidak akan pernah lolos"
 * (aman, fail-closed), bukan error.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
