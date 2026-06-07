<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::where('status', 'active')
            ->latest()
            ->get();

        return view('admin.users.index', compact('users'));
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
            'password' => Hash::make($request->password),
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
            $data['password'] = Hash::make($request->password);
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
}