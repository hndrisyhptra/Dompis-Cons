<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ApprovalCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, ApprovalCenterService $approvalCenter): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Dipakai fitur "Log Activity" di User Management (lihat
        // UserManagementController) -- kolom last_login_at.
        Auth::user()?->forceFill(['last_login_at' => now()])->saveQuietly();

        // Popup hanya untuk role Admin dan hanya menghitung project/PT2 LOP
        // yang memang di-assign oleh admin tersebut. Data disimpan sebagai
        // flash agar tampil satu kali setelah login, bukan setiap refresh.
        if (Auth::user()?->role === 'admin') {
            $snapshot = $approvalCenter->loginSnapshot((int) Auth::user()->id_user);

            if ($snapshot['evidence_count'] > 0) {
                $request->session()->flash('admin_approval_alert', $snapshot);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
