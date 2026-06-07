<?php

namespace App\Http\Controllers;

use App\Models\User;

class AssignWaspangController extends Controller
{
    public function index()
    {
        $waspangs = User::with(['assignments.project'])
            ->where('role', 'waspang')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.assign-waspang.index', compact('waspangs'));
    }
}