@extends('layouts.admin')

@section('content')

<style>
    /* Tooltip kecil untuk tombol aksi -- murni CSS, tidak butuh library JS
       tambahan. Dipicu lewat atribut data-tooltip pada elemen ber-class
       um-tooltip. */
    .um-tooltip { position: relative; }
    .um-tooltip::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        background: #111827;
        color: #fff;
        font-size: 11px;
        line-height: 1.2;
        font-weight: 600;
        padding: 5px 9px;
        border-radius: 8px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
        z-index: 30;
    }
    .um-tooltip:hover::after {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }
</style>

@php
    // Warna badge role -- sekadar polesan visual, fallback abu-abu untuk role
    // yang tidak terdaftar di sini (mis. role baru yang belum sempat ditambah).
    $roleColors = [
        'superadmin'   => 'bg-purple-100 text-purple-700',
        'admin'        => 'bg-blue-100 text-blue-700',
        'officer'      => 'bg-teal-100 text-teal-700',
        'waspang'      => 'bg-amber-100 text-amber-700',
        'teknisi'      => 'bg-slate-200 text-slate-700',
        'pm'           => 'bg-indigo-100 text-indigo-700',
        'sdi'          => 'bg-pink-100 text-pink-700',
        'sdi_surveyor' => 'bg-lime-100 text-lime-700',
        'tif'          => 'bg-cyan-100 text-cyan-700',
        'super_tif'    => 'bg-fuchsia-100 text-fuchsia-700',
    ];
@endphp

<div class="space-y-5">

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                User Management
            </h1>
            <p class="text-sm text-gray-500">
                Kelola akun & hak akses pengguna sistem
            </p>
        </div>

        <button type="button"
                onclick="openUserModal()"
                class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold inline-flex items-center gap-1.5 shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah User
        </button>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}"
      class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <div class="flex flex-col sm:flex-row gap-3">

            <input type="text"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Cari NIK, nama, username, atau role..."
                class="flex-1 h-11 rounded-xl border-gray-300 text-sm">

            <div class="flex gap-2">
                <button class="h-11 px-5 rounded-xl bg-blue-600 text-white text-sm font-bold">
                    Cari
                </button>

                @if (!empty($search))
                    <a href="{{ route('admin.users.index') }}"
                    class="h-11 px-5 rounded-xl border border-gray-300 text-sm font-bold flex items-center">
                        Reset
                    </a>
                @endif
            </div>

        </div>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Username</th>
                    <th class="px-4 py-3 text-left">Role</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                @forelse($users as $user)
                    @php
                        // Label ditampilkan langsung dari tabel roles (relasi
                        // roleRef) -- bukan array hardcode lagi.
                        $displayRole = $user->roleRef?->name ?? strtoupper($user->role ?? '-');
                        $roleCode = $user->roleRef?->code ?? $user->role;
                        $roleBadgeClass = $roleColors[$roleCode] ?? 'bg-gray-100 text-gray-700';
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 shrink-0 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center text-xs font-bold uppercase">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-gray-900 dark:text-white truncate">
                                        {{ $user->name }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $user->nik ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $user->username }}
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 rounded-lg {{ $roleBadgeClass }} text-xs font-bold whitespace-nowrap">
                                {{ $displayRole }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            @if($user->status === 'active')
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-bold inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Inactive
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1.5">

                                {{-- Edit --}}
                                <button type="button"
                                        onclick="editUser({
                                            id: '{{ $user->id_user }}',
                                            nik: @js($user->nik),
                                            name: @js($user->name),
                                            username: @js($user->username),
                                            role: @js($roleCode)
                                        })"
                                        class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-100 transition inline-flex items-center justify-center um-tooltip"
                                        data-tooltip="Edit User">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                {{-- Log Activity: hanya superadmin (officer tidak menampilkan
                                     tombol ini sesuai permintaan) --}}
                                @if($isSuperAdmin)
                                    <button type="button"
                                            onclick="showActivity({
                                                name: @js($user->name),
                                                created_at: @js(optional($user->created_at)->format('d M Y, H:i')),
                                                last_login_at: @js(optional($user->last_login_at)->format('d M Y, H:i')),
                                                last_activity_at: @js(optional($user->last_activity_at)->format('d M Y, H:i'))
                                            })"
                                            class="w-8 h-8 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition inline-flex items-center justify-center um-tooltip"
                                            data-tooltip="Log Aktivitas">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                @endif

                                {{-- Nonaktifkan / Aktifkan (toggle status, tetap dipertahankan) --}}
                                @if($user->status === 'active')
                                    <form method="POST"
                                          action="{{ route('admin.users.destroy', $user->id_user) }}"
                                          class="inline"
                                          onsubmit="return confirm('Nonaktifkan user ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="w-8 h-8 rounded-lg border border-amber-200 bg-amber-50 text-amber-600 hover:bg-amber-100 transition inline-flex items-center justify-center um-tooltip"
                                                data-tooltip="Nonaktifkan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 105.636 5.636a9 9 0 0012.728 12.728zM5.636 5.636l12.728 12.728" />
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST"
                                          action="{{ route('admin.users.activate', $user->id_user) }}"
                                          class="inline"
                                          onsubmit="return confirm('Aktifkan kembali user ini?')">
                                        @csrf
                                        <button class="w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition inline-flex items-center justify-center um-tooltip"
                                                data-tooltip="Aktifkan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                {{-- Hapus permanen: superadmin only, dipisah dari toggle
                                     Nonaktifkan/Aktifkan di atas --}}
                                @if($isSuperAdmin)
                                    <form method="POST"
                                          action="{{ route('admin.users.force-delete', $user->id_user) }}"
                                          class="inline"
                                          onsubmit="return confirm('Hapus PERMANEN user {{ addslashes($user->name) }}?\n\nTindakan ini TIDAK BISA DIBATALKAN. Jika user masih punya riwayat data terkait, hapus akan ditolak -- gunakan Nonaktifkan saja untuk kasus itu.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="w-8 h-8 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition inline-flex items-center justify-center um-tooltip"
                                                data-tooltip="Hapus Permanen">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                            </div>
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            Belum ada data user.
                        </td>
                    </tr>

                @endforelse

            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-800">

        <div class="text-sm text-gray-500 dark:text-gray-400">
            Menampilkan
            <span class="font-semibold">{{ $users->firstItem() }}</span>
            -
            <span class="font-semibold">{{ $users->lastItem() }}</span>
            dari
            <span class="font-semibold">{{ $users->total() }}</span>
            data
        </div>

        <div class="flex items-center gap-1">

            {{-- Previous --}}
            @if ($users->onFirstPage())
                <span class="px-3 py-2 rounded-lg border text-gray-400 cursor-not-allowed">
                    ←
                </span>
            @else
                <a href="{{ $users->previousPageUrl() }}"
                   class="px-3 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                    ←
                </a>
            @endif

            {{-- Page Numbers --}}
            @foreach ($users->getUrlRange(
                max(1, $users->currentPage() - 2),
                min($users->lastPage(), $users->currentPage() + 2)
            ) as $page => $url)

                @if ($page == $users->currentPage())
                    <span class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ $url }}"
                       class="px-4 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                        {{ $page }}
                    </a>
                @endif

            @endforeach

            {{-- Next --}}
            @if ($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}"
                   class="px-3 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                    →
                </a>
            @else
                <span class="px-3 py-2 rounded-lg border text-gray-400 cursor-not-allowed">
                    →
                </span>
            @endif

        </div>
    </div>
    @endif

</div>

</div>
{{-- MODAL USER (Tambah / Edit) --}}
<div id="userModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 id="userModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">
                    Tambah User
                </h2>
                <p class="text-sm text-gray-500">
                    Isi data kredensial user sistem
                </p>
            </div>

            <button type="button"
                    onclick="closeUserModal()"
                    class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-red-100 hover:text-red-600 transition font-black text-gray-500">
                ×
            </button>
        </div>

        <form id="userForm" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <input type="hidden" name="_method" id="userMethod" value="POST">

            <div class="p-5 space-y-4">

                <div id="nikField">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">NIK</label>
                    <input type="text"
                           name="nik"
                           id="user_nik"
                           required
                           placeholder="Masukkan NIK"
                           class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                    <input type="text"
                           name="name"
                           id="user_name"
                           required
                           placeholder="Masukkan Nama Lengkap"
                           class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
                </div>

                <div id="usernameField">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Username</label>
                    <input type="text"
                           name="username"
                           id="user_username"
                           required
                           placeholder="Masukkan Username"
                           class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Role / Hak Akses</label>
                    <select name="role"
                            id="user_role"
                            required
                            class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
                        <option value="">-- Pilih Role --</option>
                        {{-- Daftar role diambil dari tabel roles (dikirim controller
                             lewat variabel $roles) -- role baru otomatis muncul di
                             sini tanpa perlu ubah Blade ini lagi. --}}
                        @foreach($roles as $role)
                            <option value="{{ $role->code }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="passwordField">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <input type="password"
                           name="password"
                           id="user_password"
                           placeholder="Masukkan Password"
                           class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
                    <p class="mt-1 text-[11px] text-red-500 dark:text-red-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Password minimal harus terdiri dari 6 karakter.
                    </p>
                    <p class="text-[10px] text-gray-500 mt-1">
                        *Saat edit akun, kosongkan kolom ini jika tidak ingin mengubah password.
                    </p>

                    {{-- Reset password ke default (= username): superadmin only, hanya
                         tampil saat mode Edit. Tombol ini submit ke #resetPasswordForm
                         (form terpisah di luar #userForm -- lihat bawah), BUKAN form
                         ini, lewat atribut form="resetPasswordForm". --}}
                    <button type="submit"
                            form="resetPasswordForm"
                            id="resetPasswordBtn"
                            class="hidden mt-2 h-9 px-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 text-xs font-bold hover:bg-amber-100 transition inline-flex items-center gap-1.5 um-tooltip"
                            data-tooltip="Password baru akan sama dengan username user ini">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 11-12 0 6 6 0 0112 0zM8 13l-5 5m0 0v-3m0 3h3" />
                        </svg>
                        Reset Password ke Default
                    </button>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                <button type="button"
                        onclick="closeUserModal()"
                        class="h-10 px-5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-bold hover:bg-gray-50 transition">
                    Batal
                </button>

                <button type="submit"
                        class="h-10 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition shadow-md">
                    Simpan Data
                </button>
            </div>
        </form>

    </div>

</div>

{{-- Form terpisah untuk Reset Password -- HARUS di luar #userForm karena
     <form> tidak boleh bersarang di dalam <form> lain. Action-nya diisi
     dinamis lewat JS (editUser()) sesuai user yang sedang dibuka. --}}
<form id="resetPasswordForm" method="POST" class="hidden"
      onsubmit="return confirm('Reset password user ini ke default?\n\nPassword baru akan sama dengan username user tersebut.')">
    @csrf
</form>

{{-- MODAL LOG ACTIVITY (superadmin only) --}}
<div id="activityModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-sm rounded-2xl overflow-hidden shadow-2xl">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Log Aktivitas
                </h2>
                <p id="activityUserName" class="text-sm text-gray-500"></p>
            </div>

            <button type="button"
                    onclick="closeActivityModal()"
                    class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-red-100 hover:text-red-600 transition font-black text-gray-500">
                ×
            </button>
        </div>

        <div class="p-5 space-y-3">
            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
                <span class="text-sm text-gray-500">Terdaftar</span>
                <span id="activityCreatedAt" class="text-sm font-bold text-gray-900 dark:text-white"></span>
            </div>

            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
                <span class="text-sm text-gray-500">Login Terakhir</span>
                <span id="activityLastLogin" class="text-sm font-bold text-gray-900 dark:text-white"></span>
            </div>

            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
                <span class="text-sm text-gray-500">Aktivitas Terakhir</span>
                <span id="activityLastActive" class="text-sm font-bold text-gray-900 dark:text-white"></span>
            </div>
        </div>

        <div class="flex justify-end px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
            <button type="button"
                    onclick="closeActivityModal()"
                    class="h-10 px-5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-bold hover:bg-gray-50 transition">
                Tutup
            </button>
        </div>
    </div>

</div>

<script>
// true hanya untuk superadmin -- dipakai editUser() untuk menampilkan/menyembunyikan
// field nik/username/password serta tombol reset password.
const IS_SUPERADMIN = @json($isSuperAdmin);

function openUserModal()
{
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('userModal').classList.add('flex');

    document.getElementById('userModalTitle').innerText = 'Tambah User';
    document.getElementById('userForm').action = "{{ route('admin.users.store') }}";
    document.getElementById('userMethod').value = 'POST';

    document.getElementById('userForm').reset();

    // Tambah User: field lengkap selalu ditampilkan untuk siapa pun yang
    // berhak buka User Management (superadmin ATAU officer) -- officer
    // memang diizinkan membuat user baru lengkap dengan username/password,
    // pembatasan hanya berlaku saat EDIT user yang sudah ada.
    document.getElementById('nikField').classList.remove('hidden');
    document.getElementById('usernameField').classList.remove('hidden');
    document.getElementById('passwordField').classList.remove('hidden');

    document.getElementById('user_nik').required = true;
    document.getElementById('user_username').required = true;
    document.getElementById('user_password').required = true;

    document.getElementById('resetPasswordBtn').classList.add('hidden');
}

function editUser(user)
{
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('userModal').classList.add('flex');

    document.getElementById('userModalTitle').innerText = 'Edit User';
    document.getElementById('userForm').action = `/admin/users/${user.id}`;
    document.getElementById('userMethod').value = 'PUT';

    document.getElementById('user_name').value = user.name;
    document.getElementById('user_role').value = user.role;

    if (IS_SUPERADMIN) {
        // Superadmin: boleh ubah semua field + tombol reset password muncul.
        document.getElementById('nikField').classList.remove('hidden');
        document.getElementById('usernameField').classList.remove('hidden');
        document.getElementById('passwordField').classList.remove('hidden');

        document.getElementById('user_nik').value = user.nik ?? '';
        document.getElementById('user_nik').required = true;

        document.getElementById('user_username').value = user.username;
        document.getElementById('user_username').required = true;

        document.getElementById('user_password').value = '';
        document.getElementById('user_password').required = false;

        document.getElementById('resetPasswordBtn').classList.remove('hidden');
        document.getElementById('resetPasswordForm').action = `/admin/users/${user.id}/reset-password`;
    } else {
        // Officer: HANYA nama & role -- nik/username/password disembunyikan
        // (memang tidak boleh diubah officer; dijaga juga di server, lihat
        // UserManagementController::update()).
        document.getElementById('nikField').classList.add('hidden');
        document.getElementById('usernameField').classList.add('hidden');
        document.getElementById('passwordField').classList.add('hidden');

        document.getElementById('user_nik').required = false;
        document.getElementById('user_username').required = false;
        document.getElementById('user_password').required = false;

        document.getElementById('resetPasswordBtn').classList.add('hidden');
    }
}

function closeUserModal()
{
    document.getElementById('userModal').classList.add('hidden');
    document.getElementById('userModal').classList.remove('flex');
}

function showActivity(user)
{
    document.getElementById('activityUserName').innerText = user.name;
    document.getElementById('activityCreatedAt').innerText = user.created_at ?? '-';
    document.getElementById('activityLastLogin').innerText = user.last_login_at ?? 'Belum pernah login';
    document.getElementById('activityLastActive').innerText = user.last_activity_at ?? '-';

    document.getElementById('activityModal').classList.remove('hidden');
    document.getElementById('activityModal').classList.add('flex');
}

function closeActivityModal()
{
    document.getElementById('activityModal').classList.add('hidden');
    document.getElementById('activityModal').classList.remove('flex');
}
</script>

@endsection
