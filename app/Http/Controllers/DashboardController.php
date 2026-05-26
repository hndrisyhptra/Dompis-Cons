<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ProjectAssignment;

class DashboardController extends Controller
{
    public function index(Request $request)
{
    $role = auth()->user()->role;

    if ($role == 'admin') {

        $query = Project::with([
            'boqItems',
            'assignments.waspang',
            'evidences'
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('project_name', 'like', '%' . $request->search . '%')
                  ->orWhere('sto', 'like', '%' . $request->search . '%')
                  ->orWhere('branch', 'like', '%' . $request->search . '%')
                  ->orWhere('mitra_name', 'like', '%' . $request->search . '%');

            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->status) {

            $query->where('status', $request->status);
        }

        $projects = $query->latest()->get();

        $totalProject = Project::count();

        $activeProject = Project::where('status', 'active')->count();

        $waitingUt = Project::where('status', 'waiting_ut')->count();

        $completedProject = Project::where('status', 'completed')->count();

        $waspangs = User::with('assignments')
            ->where('role', 'waspang')
            ->get();

        return view('admin.dashboard', compact(
            'projects',
            'waspangs',
            'totalProject',
            'activeProject',
            'waitingUt',
            'completedProject'
        ));
    }

    if ($role == 'waspang') {
    return redirect()->route('waspang.dashboard');
    }

    if ($role == 'pm') {
        return view('dashboard.pm');
    }

    abort(403);
    }

    public function show($id)
    {
        $project = Project::with([
            'boqItems',
            'assignments.waspang',
            'evidences'
        ])->findOrFail($id);

        return view('admin.project-detail', compact('project'));
    }

    public function assignWaspang(Request $request)
    {
        $request->validate([
            'project_id' => 'required',
            'waspang_id' => 'required',
        ]);

        /*
        |--------------------------------------------------------------------------
        | UPDATE / CREATE ASSIGNMENT
        |--------------------------------------------------------------------------
        */

        ProjectAssignment::updateOrCreate(

            [
                'project_id' => $request->project_id
            ],

            [
                'waspang_id' => $request->waspang_id
            ]
        );

        return back()->with(
            'success',
            'Waspang berhasil di-assign'
        );
    }

    public function storeProject(Request $request)
    {
        Project::create([

            'project_name' => $request->project_name,
            'branch' => $request->branch,
            'sto' => $request->sto,
            'mitra_name' => $request->mitra_name,
            'jenis_eksekusi' => $request->jenis_eksekusi,
            'status' => 'active',

        ]);

        return back()->with(
            'success',
            'Project berhasil dibuat'
        );
    }
    
    public function storeBoq(Request $request)
    {
        \App\Models\BoqItem::create([

            'project_id' => $request->project_id,
            'item_name' => $request->item_name,
            'unit' => $request->unit,
            'quantity_plan' => $request->quantity_plan,
            'quantity_actual' => 0,

        ]);

        return back()->with(
            'success',
            'BOQ berhasil ditambahkan'
        );
    }

    public function removeAssign($project)
    {
        ProjectAssignment::where('project_id', $project)->delete();

        return back()->with('success', 'Assignment waspang berhasil dihapus');
    }
}