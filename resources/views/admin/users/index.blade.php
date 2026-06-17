@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                User Management
            </h1>
            <p class="text-sm text-gray-500">
                Kelola user 
            </p>
        </div>

        <button type="button"
                onclick="openUserModal()"
                class="h-10 px-4 rounded-xl bg-blue-600 text-white text-sm font-bold">
            + Tambah User
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
                        <th class="px-4 py-3 text-left">NIK</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Username</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($users as $user)

                        <tr>
                            <td class="px-4 py-3 font-semibold">
                                {{ $user->nik ?? '-' }}
                            </td>

                            <td class="px-4 py-3 font-bold">
                                {{ $user->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $user->username }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-bold">
                                    Active
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                        onclick="editUser({
                                            id: '{{ $user->id_user }}',
                                            nik: @js($user->nik),
                                            name: @js($user->name),
                                            username: @js($user->username),
                                            role: @js($user->role)
                                        })"
                                        class="h-8 px-3 rounded-lg border border-gray-300 text-xs font-bold">
                                    Edit
                                </button>

                                <form method="POST"
                                      action="{{ route('admin.users.destroy', $user->id_user) }}"
                                      class="inline"
                                      onsubmit="return confirm('Nonaktifkan user ini?')">
                                    @csrf
                                    @method('DELETE')

                                    <button class="h-8 px-3 rounded-lg border border-red-300 text-red-600 text-xs font-bold">
                                        Nonaktif
                                    </button>
                                </form>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                Belum ada user aktif.
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

{{-- MODAL USER --}}
<div id="userModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 id="userModalTitle" class="text-lg font-bold">
                    Tambah User
                </h2>
                <p class="text-sm text-gray-500">
                    Isi data user sistem
                </p>
            </div>

            <button type="button"
                    onclick="closeUserModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 text-xl">
                ×
            </button>
        </div>

        <form id="userForm" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <input type="hidden" name="_method" id="userMethod" value="POST">

            <div class="p-5 space-y-4">

                <input type="text"
                       name="nik"
                       id="user_nik"
                       required
                       placeholder="NIK"
                       class="w-full h-11 rounded-xl border-gray-300 text-sm">

                <input type="text"
                       name="name"
                       id="user_name"
                       required
                       placeholder="Nama lengkap"
                       class="w-full h-11 rounded-xl border-gray-300 text-sm">

                <input type="text"
                       name="username"
                       id="user_username"
                       required
                       placeholder="Username"
                       class="w-full h-11 rounded-xl border-gray-300 text-sm">

                <select name="role"
                        id="user_role"
                        required
                        class="w-full h-11 rounded-xl border-gray-300 text-sm">
                    <option value="">Pilih role</option>
                    <option value="admin">Admin</option>
                    <option value="waspang">Waspang</option>
                    <option value="pm">PM</option>
                </select>

                <input type="password"
                       name="password"
                       id="user_password"
                       placeholder="Password"
                       class="w-full h-11 rounded-xl border-gray-300 text-sm">

                <p class="text-xs text-gray-500">
                    Saat edit, kosongkan password jika tidak ingin mengubah password.
                </p>

            </div>

            <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200">
                <button type="button"
                        onclick="closeUserModal()"
                        class="h-10 rounded-xl border border-gray-300 text-sm font-bold">
                    Batal
                </button>

                <button class="h-10 rounded-xl bg-gray-900 text-white text-sm font-bold">
                    Simpan
                </button>
            </div>
        </form>

    </div>

</div>

<script>
function openUserModal()
{
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('userModal').classList.add('flex');

    document.getElementById('userModalTitle').innerText = 'Tambah User';
    document.getElementById('userForm').action = "{{ route('admin.users.store') }}";
    document.getElementById('userMethod').value = 'POST';

    document.getElementById('userForm').reset();
    document.getElementById('user_password').required = true;
}

function editUser(user)
{
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('userModal').classList.add('flex');

    document.getElementById('userModalTitle').innerText = 'Edit User';
    document.getElementById('userForm').action = `/admin/users/${user.id}`;
    document.getElementById('userMethod').value = 'PUT';

    document.getElementById('user_nik').value = user.nik ?? '';
    document.getElementById('user_name').value = user.name;
    document.getElementById('user_username').value = user.username;
    document.getElementById('user_role').value = user.role;

    document.getElementById('user_password').value = '';
    document.getElementById('user_password').required = false;
}

function closeUserModal()
{
    document.getElementById('userModal').classList.add('hidden');
    document.getElementById('userModal').classList.remove('flex');
}
</script>

@endsection