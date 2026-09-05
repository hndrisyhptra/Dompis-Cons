{{--
    Tombol avatar profile di topbar (desktop: admin/superadmin, pm/tif, sdi).
    Klik -> muncul modal berisi Detail Profile + form Ganti Password.

    Modal otomatis kebuka lagi setelah submit form Ganti Password kalau ada
    error validasi atau setelah sukses, supaya user langsung lihat hasilnya
    (bukan hilang begitu saja karena modal defaultnya tersembunyi).
--}}
@php
    $__u = auth()->user();

    // Label diambil langsung dari tabel roles (relasi roleRef) -- bukan
    // array hardcode lagi (dulu duplikat persis dengan admin/users/index.blade.php).
    $__roleLabel = $__u->roleRef?->name ?? strtoupper($__u->role ?? '-');
    $__initials = strtoupper(substr($__u->name ?? '?', 0, 2));

    $__openInitially = (isset($errors) && $errors->updatePassword->any())
        || session('status') === 'password-updated';
@endphp

<div x-data="{ profileMenuOpen: {{ $__openInitially ? 'true' : 'false' }} }" class="relative">

    {{-- TRIGGER --}}
    <button type="button"
            @click="profileMenuOpen = true"
            class="flex items-center gap-2.5 pl-2.5 pr-1.5 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">

        <div class="hidden sm:block text-right">
            <p class="text-sm font-bold text-gray-900 dark:text-white leading-tight">
                {{ $__u->name }}
            </p>
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400 leading-tight">
                {{ $__roleLabel }}
            </p>
        </div>

        <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-black shrink-0">
            {{ $__initials }}
        </div>
    </button>

    {{-- MODAL --}}
    <div x-show="profileMenuOpen"
         style="display: none;"
         x-transition.opacity
         class="fixed inset-0 z-[70] flex items-start sm:items-center justify-center p-4 overflow-y-auto">

        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="profileMenuOpen = false"></div>

        <div x-show="profileMenuOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-xl w-full max-w-md mt-8 sm:mt-0">

            <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-black text-gray-900 dark:text-white">Akun Saya</h3>
                <button type="button" @click="profileMenuOpen = false"
                        class="w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center justify-center text-gray-500 dark:text-gray-400 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-5 space-y-5 max-h-[75vh] overflow-y-auto">

                {{-- DETAIL PROFILE --}}
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center text-base font-black shrink-0">
                        {{ $__initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-black text-gray-900 dark:text-white truncate">
                            {{ $__u->name }}
                        </p>
                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-black uppercase tracking-wide">
                            {{ $__roleLabel }}
                        </span>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="px-4 py-2.5 flex justify-between items-center border-b border-gray-100 dark:border-gray-800">
                        <span class="text-xs text-gray-500 dark:text-gray-400">NIK</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $__u->nik ?? '-' }}</span>
                    </div>
                    <div class="px-4 py-2.5 flex justify-between items-center border-b border-gray-100 dark:border-gray-800">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Username</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $__u->username }}</span>
                    </div>
                    <div class="px-4 py-2.5 flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Role</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white uppercase">{{ $__roleLabel }}</span>
                    </div>
                </div>

                {{-- GANTI PASSWORD --}}
                <div>
                    <p class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                        Ganti Password
                    </p>
                    <x-change-password-form />
                </div>

                {{-- LOGOUT --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full h-10 rounded-xl bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-300 border border-red-100 dark:border-red-900 text-xs font-bold transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
