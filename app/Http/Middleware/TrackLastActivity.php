<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catat "aktivitas terakhir" user yang sedang login (fitur Log Activity di
 * User Management). Sengaja di-throttle -- HANYA menulis ke DB kalau catatan
 * terakhir sudah lebih dari 5 menit lalu (atau belum pernah ada) -- supaya
 * tidak nambah 1 UPDATE query di SETIAP request halaman yang dibuka user.
 */
class TrackLastActivity
{
    private const THROTTLE_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $last = $user->last_activity_at;

            if (!$last || $last->diffInMinutes(now()) >= self::THROTTLE_MINUTES) {
                $user->forceFill(['last_activity_at' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }
}
