<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use Illuminate\Support\Facades\Validator;

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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        for ($row = 2; $row <= $highestRow; $row++) {

            $nik = trim((string) $sheet->getCell('A' . $row)->getValue());
            $name = trim((string) $sheet->getCell('B' . $row)->getValue());
            $username = trim((string) $sheet->getCell('C' . $row)->getValue());
            $role = strtolower(trim((string) $sheet->getCell('D' . $row)->getValue()));
            $password = trim((string) $sheet->getCell('E' . $row)->getValue());

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
                $errors[] = 'Baris ' . $row . ': ' . implode(', ', $validator->errors()->all());
                continue;
            }

            $existingUser = User::where('username', $username)
                ->orWhere('nik', $nik)
                ->first();

            if ($existingUser) {
                $existingUser->update([
                    'nik' => $nik,
                    'name' => $name,
                    'username' => $username,
                    'role' => $role,
                    'status' => 'active',
                    'password' => $password,
                ]);

                $updated++;
            } else {
                User::create([
                    'nik' => $nik,
                    'name' => $name,
                    'username' => $username,
                    'role' => $role,
                    'status' => 'active',
                    'password' => $password,
                ]);

                $imported++;
            }
        }

        $message = "Import selesai. Baru: {$imported}, Update: {$updated}, Skip: {$skipped}.";

        if (!empty($errors)) {
            return back()
                ->with('success', $message)
                ->with('import_errors', array_slice($errors, 0, 10));
        }

        return back()->with('success', $message);
    }
}