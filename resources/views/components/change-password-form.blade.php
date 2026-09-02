{{--
    Form Ganti Password — dipakai lintas role (admin, superadmin, pm, tif, sdi,
    waspang, teknisi, sdi_surveyor). Backend-nya memakai route password.update
    bawaan Laravel (App\Http\Controllers\Auth\PasswordController::update) yang
    sudah otomatis cocok dengan kolom `password` di tabel users — tidak perlu
    controller baru.

    Pesan sukses & error divalidasi lewat error bag "updatePassword" (lihat
    PasswordController::update() yang memakai validateWithBag('updatePassword', ...)).
--}}
<form method="POST" action="{{ route('password.update') }}" class="space-y-3.5">
    @csrf
    @method('PUT')

    @if (session('status') === 'password-updated')
        <div class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 text-xs font-bold">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span>Password berhasil diubah.</span>
        </div>
    @endif

    <div>
        <label for="cpf_current_password" class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">
            Password Saat Ini
        </label>
        <input id="cpf_current_password"
               name="current_password"
               type="password"
               autocomplete="current-password"
               class="w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
        @error('current_password', 'updatePassword')
            <p class="text-xs text-red-600 dark:text-red-400 mt-1 font-semibold">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="cpf_password" class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">
            Password Baru
        </label>
        <input id="cpf_password"
               name="password"
               type="password"
               autocomplete="new-password"
               class="w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
        @error('password', 'updatePassword')
            <p class="text-xs text-red-600 dark:text-red-400 mt-1 font-semibold">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="cpf_password_confirmation" class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">
            Konfirmasi Password Baru
        </label>
        <input id="cpf_password_confirmation"
               name="password_confirmation"
               type="password"
               autocomplete="new-password"
               class="w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
        @error('password_confirmation', 'updatePassword')
            <p class="text-xs text-red-600 dark:text-red-400 mt-1 font-semibold">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit"
            class="w-full h-10 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition">
        Simpan Password Baru
    </button>
</form>
