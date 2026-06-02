<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Evidence;
use App\Models\ProjectAssignment;
use App\Models\EvidenceRevisionHistory;

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

            $allProjectsForAnalytics = Project::with(['boqItems', 'evidences'])->get();

            $analyticsBySto = $allProjectsForAnalytics->groupBy('sto')->map(function ($items, $sto) {
                $total = $items->count();

                $ready = $items->filter(function ($project) {
                    return $project->evidences
                        ->where('stage', 'finishing')
                        ->where('status', 'approved')
                        ->count() > 0;
                })->count();

                return [
                    'label' => $sto ?: '-',
                    'total' => $total,
                    'ready' => $ready,
                    'ongoing' => $total - $ready,
                    'percent' => $total > 0 ? round(($ready / $total) * 100) : 0,
                ];
            })->values();

           $analyticsByBranch = $allProjectsForAnalytics->groupBy('branch')->map(function ($items, $branch) {
                $total = $items->count();

                $ready = $items->filter(function ($project) {
                    return $project->evidences
                        ->where('stage', 'finishing')
                        ->where('status', 'approved')
                        ->count() > 0;
                })->count();

                return [
                    'label' => $branch ?: '-',
                    'total' => $total,
                    'ready' => $ready,
                    'ongoing' => $total - $ready,
                    'percent' => $total > 0 ? round(($ready / $total) * 100) : 0,
                ];
            })->values();

            $totalApprovedEvidence = \App\Models\Evidence::where('status', 'approved')->count();

            $totalPendingEvidence = \App\Models\Evidence::where('status', 'pending')->count();

            $totalRejectedEvidence = \App\Models\Evidence::where('status', 'rejected')->count();    

            return view('admin.dashboard', compact(
                'projects',
                'waspangs',
                'totalProject',
                'activeProject',
                'waitingUt',
                'completedProject',
                'analyticsBySto',
                'analyticsByBranch',
                'totalApprovedEvidence',
                'totalPendingEvidence',
                'totalRejectedEvidence',
                
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
    

    /*
        |--------------------------------------------------------------------------
        | MAP MONITORING
        |--------------------------------------------------------------------------
        */

    public function mapMonitoring()
        {
            $projects = Project::with([
                'evidences',
                'assignments.waspang'
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

            $evidences = Evidence::with(['project', 'uploader'])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->latest()
                ->get();

            return view('admin.map-monitoring', compact(
                'projects',
                'evidences'
            ));
        }
}