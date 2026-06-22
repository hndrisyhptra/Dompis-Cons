<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $users = User::where('status', 'active')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->latest('id_user')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|max:30|unique:users,nik',
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'role' => 'required|in:admin,waspang,pm',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'nik' => $request->nik,
            'name' => $request->name,
            'username' => $request->username,
            'role' => $request->role,
            'status' => 'active',
            'password' => $request->password,
        ]);

        return back()->with('success', 'User berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $user = User::where('id_user', $id)->firstOrFail();

        $request->validate([
            'nik' => 'required|string|max:30|unique:users,nik,' . $user->id_user . ',id_user',
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id_user . ',id_user',
            'role' => 'required|in:admin,waspang,pm',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'nik' => $request->nik,
            'name' => $request->name,
            'username' => $request->username,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return back()->with('success', 'User berhasil di Update');
    }

    public function destroy($id)
    {
        $user = User::where('id_user', $id)->firstOrFail();

        $user->update([
            'status' => 'inactive',
        ]);

        return back()->with('success', 'User berhasil dinonaktifkan');
    }

    public function importCsv(Request $request)
{
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
            'role' => 'required|in:admin,waspang,pm',
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