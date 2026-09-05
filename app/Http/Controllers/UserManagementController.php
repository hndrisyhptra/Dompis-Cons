<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    /**
     * Halaman User Management bisa diakses superadmin & officer. Beberapa
     * aksi di dalamnya (lihat method masing-masing) tetap dibatasi hanya
     * untuk superadmin -- lihat ensureSuperAdmin().
     */
    private function ensureUserManagementAccess(): void
    {
        if (!in_array(auth()->user()?->role, ['superadmin', 'officer'], true)) {
            abort(403, 'Anda tidak memiliki akses ke User Management.');
        }
    }

    /**
     * Beberapa aksi (reset password, hapus permanen, edit username/password
     * user lain, bulk import) SENGAJA dibatasi hanya untuk superadmin --
     * officer hanya bisa edit nama & role (lihat update()).
     */
    private function ensureSuperAdmin(): void
    {
        if (auth()->user()?->role !== 'superadmin') {
            abort(403, 'Hanya Super Admin yang dapat melakukan aksi ini.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureUserManagementAccess();

        $search = $request->search;

        // Query dirubah tanpa membatasi hanya status active
        $users = User::with('roleRef')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('roleRef', function ($rq) use ($search) {
                        $rq->where('code', 'like', "%{$search}%")
                           ->orWhere('name', 'like', "%{$search}%");
                    });
                });
            })
            ->latest('id_user')
            ->paginate(10)
            ->withQueryString();

        // Daftar role diambil dari tabel roles (dulu hardcode di Blade) supaya
        // dropdown form tambah/edit user otomatis ikut kalau ada role baru.
        $roles = Role::orderBy('name')->get();

        $isSuperAdmin = auth()->user()?->role === 'superadmin';

        return view('admin.users.index', compact('users', 'search', 'roles', 'isSuperAdmin'));
    }

    public function store(Request $request)
    {
        $this->ensureUserManagementAccess();

        $request->validate([
            'nik' => 'required|string|max:30|unique:users,nik',
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            // Daftar role divalidasi dinamis ke tabel roles (bukan hardcode
            // lagi) -- tambah role baru cukup insert baris baru di tabel ini.
            'role' => 'required|exists:roles,code',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'nik' => $request->nik,
            'name' => $request->name,
            'username' => $request->username,
            'role' => $request->role,
            'status' => 'active',
            // Update: Pastikan password di-hash agar user bisa login
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'User berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $this->ensureUserManagementAccess();

        $user = User::where('id_user', $id)->firstOrFail();

        $isSuperAdmin = auth()->user()?->role === 'superadmin';

        if (!$isSuperAdmin) {
            // Officer HANYA boleh ubah nama & role -- nik/username/password
            // sengaja diabaikan di sini walau ikut terkirim dari request,
            // supaya tidak bisa "dipaksa" lewat request mentah dari luar UI.
            $request->validate([
                'name' => 'required|string|max:255',
                'role' => 'required|exists:roles,code',
            ]);

            $user->update([
                'name' => $request->name,
                'role' => $request->role,
            ]);

            return back()->with('success', 'User berhasil di Update');
        }

        $request->validate([
            'nik' => 'required|string|max:30|unique:users,nik,' . $user->id_user . ',id_user',
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id_user . ',id_user',
            // Daftar role divalidasi dinamis ke tabel roles (bukan hardcode
            // lagi) -- tambah role baru cukup insert baris baru di tabel ini.
            'role' => 'required|exists:roles,code',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'nik' => $request->nik,
            'name' => $request->name,
            'username' => $request->username,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            // Update: Pastikan password baru di-hash
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'User berhasil di Update');
    }

    /**
     * Reset password user ke default (= username user itu sendiri).
     * Superadmin only -- password adalah data sensitif.
     */
    public function resetPassword($id)
    {
        $this->ensureSuperAdmin();

        $user = User::where('id_user', $id)->firstOrFail();

        $user->update([
            'password' => Hash::make($user->username),
        ]);

        return back()->with('success', "Password {$user->name} berhasil direset. Password baru sama dengan username: {$user->username}");
    }

    public function destroy($id)
    {
        $this->ensureUserManagementAccess();

        $user = User::where('id_user', $id)->firstOrFail();

        $user->update([
            'status' => 'inactive',
        ]);

        return back()->with('success', 'User berhasil dinonaktifkan');
    }

    public function activate($id)
    {
        $this->ensureUserManagementAccess();

        $user = User::where('id_user', $id)->firstOrFail();

        $user->update([
            'status' => 'active',
        ]);

        return back()->with('success', 'User berhasil diaktifkan kembali!');
    }

    /**
     * Hapus PERMANEN dari database (beda dengan destroy() di atas yang cuma
     * menonaktifkan). Superadmin only -- aksi ini tidak bisa dibatalkan.
     *
     * Kalau user ini masih punya riwayat terkait (pernah di-assign project,
     * upload eviden, dsb -- semua tabel itu punya foreign key ke users),
     * MySQL akan menolak DELETE-nya (integrity constraint violation). Itu
     * ditangkap di sini dan diberi pesan yang jelas, bukan error 500 mentah.
     */
    public function forceDelete($id)
    {
        $this->ensureSuperAdmin();

        $user = User::where('id_user', $id)->firstOrFail();
        $name = $user->name;

        try {
            $user->delete();
        } catch (QueryException $e) {
            return back()->with(
                'error',
                "User {$name} tidak bisa dihapus permanen karena masih punya riwayat data terkait " .
                "(pernah di-assign project, upload eviden, atau sejenisnya). " .
                "Gunakan tombol Nonaktifkan saja untuk user ini."
            );
        }

        return back()->with('success', "User {$name} berhasil dihapus permanen.");
    }

    public function importCsv(Request $request)
    {
        // Bulk import tetap superadmin-only (beda dengan store() satuan) --
        // sekali proses bisa buat banyak akun baru sekaligus lengkap dengan
        // password, jadi tetap dianggap aksi sensitif.
        $this->ensureSuperAdmin();

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->with('error', 'File CSV tidak bisa dibaca.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Format CSV
        |--------------------------------------------------------------------------
        | Kolom:
        | A = nik
        | B = name
        | C = username
        | D = role
        | E = password
        |--------------------------------------------------------------------------
        */

        $rowNumber = 0;

        while (($row = fgetcsv($handle, 10000, ',')) !== false) {
            $rowNumber++;

            // Skip header baris pertama
            if ($rowNumber == 1) {
                continue;
            }

            $nik = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            $username = trim((string) ($row[2] ?? ''));
            $role = strtolower(trim((string) ($row[3] ?? '')));
            $password = trim((string) ($row[4] ?? ''));

            if (!$nik && !$name && !$username && !$role && !$password) {
                continue;
            }

            $validator = Validator::make([
                'nik' => $nik,
                'name' => $name,
                'username' => $username,
                'role' => $role,
                'password' => $password,
            ], [
                'nik' => 'required|string|max:30',
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:100',
                'role' => 'required|exists:roles,code',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = 'Baris ' . $rowNumber . ': ' . implode(', ', $validator->errors()->all());
                continue;
            }

            // Jika NIK sudah ada, skip
            $nikExists = User::where('nik', $nik)->exists();

            if ($nikExists) {
                $skipped++;
                $errors[] = "Baris {$rowNumber}: NIK {$nik} sudah ada, data dilewati.";
                continue;
            }

            // Jika username sudah ada, skip
            $usernameExists = User::where('username', $username)->exists();

            if ($usernameExists) {
                $skipped++;
                $errors[] = "Baris {$rowNumber}: Username {$username} sudah ada, data dilewati.";
                continue;
            }

            User::create([
                'nik' => $nik,
                'name' => $name,
                'username' => $username,
                'role' => $role,
                'status' => 'active',
                'password' => Hash::make($password),
            ]);

            $imported++;
        }

        fclose($handle);

        $message = "Import CSV selesai. Baru: {$imported}, Skip: {$skipped}.";

        if (!empty($errors)) {
            return back()
                ->with('success', $message)
                ->with('import_errors', array_slice($errors, 0, 10));
        }

        return back()->with('success', $message);
    }
}
