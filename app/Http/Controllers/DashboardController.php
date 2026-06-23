<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Evidence;
use App\Models\ProjectAssignment;
use App\Models\EvidenceRevisionHistory;
use App\Models\Lop;
use App\Models\BoqItem;
use App\Models\Designator;
use App\Models\ProjectActivityLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $role = auth()->user()->role;

        if ($role == 'admin') {

            $lops = Lop::with([
                'project.assignment',
                'project.assignments.waspang',
                'project.evidences',
                'project.boqItems.designatorData',
                'project.boqItems.designatorDataByCode',
            ])->get();

            $totalLop = $lops->count();

            $assignedLop = $lops->filter(function ($lop) {
                return $lop->project?->assignment;
            })->count();

            /*
            |--------------------------------------------------------------------------
            | Waiting Approval
            |--------------------------------------------------------------------------
            | Final logic:
            | In Review = progress > 0 dan progress < 100
            | Bukan berdasarkan evidence pending saja
            |--------------------------------------------------------------------------
            */
            $waitingApproval = $lops->filter(function ($lop) {
                if (!$lop->project) {
                    return false;
                }

                $summary = $lop->project->progressSummary();

                return $summary['progress'] > 0 && $summary['progress'] < 100;
            })->count();

            /*
            |--------------------------------------------------------------------------
            | Completed Approval
            |--------------------------------------------------------------------------
            | Complete = progress 100%
            |--------------------------------------------------------------------------
            */
            $completedApproval = $lops->filter(function ($lop) {
                if (!$lop->project) {
                    return false;
                }

                $summary = $lop->project->progressSummary();

                return $summary['progress'] == 100;
            })->count();

            /*
            |--------------------------------------------------------------------------
            | Statistik Per Batch / Branch / Program
            |--------------------------------------------------------------------------
            */
            $makeStats = function ($field) use ($lops) {
                return $lops
                    ->groupBy(function ($lop) use ($field) {

                        if ($field === 'program') {
                            return $lop->project?->program ?: '-';
                        }

                        return $lop->{$field} ?: '-';
                    })
                    ->map(function ($items, $label) {

                        $total = $items->count();

                        $assigned = $items->filter(function ($lop) {
                            return $lop->project?->assignment;
                        })->count();

                        $waiting = $items->filter(function ($lop) {
                            if (!$lop->project) {
                                return false;
                            }

                            $summary = $lop->project->progressSummary();

                            return $summary['progress'] > 0 && $summary['progress'] < 100;
                        })->count();

                        $completed = $items->filter(function ($lop) {
                            if (!$lop->project) {
                                return false;
                            }

                            $summary = $lop->project->progressSummary();

                            return $summary['progress'] == 100;
                        })->count();

                        $percent = $total > 0
                            ? round(($completed / $total) * 100)
                            : 0;

                        return [
                            'label' => $label,
                            'total' => $total,
                            'assigned' => $assigned,
                            'waiting' => $waiting,
                            'completed' => $completed,
                            'percent' => $percent,
                        ];
                    })
                    ->sortByDesc('total')
                    ->values();
            };

            $statsByBatch = $makeStats('batch');
            $statsByBranch = $makeStats('branch');
            $statsByProgram = $makeStats('program');

            return view('admin.dashboard', compact(
                'totalLop',
                'assignedLop',
                'waitingApproval',
                'completedApproval',
                'statsByBatch',
                'statsByBranch',
                'statsByProgram'
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

        ProjectAssignment::updateOrCreate(
            [
                'project_id' => $request->project_id
            ],
            [
                'waspang_id' => $request->waspang_id
            ]
        );

        return back()->with('success', 'Waspang berhasil di-assign');
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

        return back()->with('success', 'Project berhasil dibuat');
    }

    public function storeBoq(Request $request)
    {
        BoqItem::create([
            'project_id' => $request->project_id,
            'item_name' => $request->item_name,
            'unit' => $request->unit,
            'quantity_plan' => $request->quantity_plan,
            'quantity_actual' => 0,
        ]);

        return back()->with('success', 'BOQ berhasil ditambahkan');
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

    public function tracking($project)
    {
        $project = Project::with([
            'lop',
            'evidences',
            'activityLogs.user',
            'activityLogs.targetUser',
            'activityLogs.evidence',
            'boqItems.designatorData',
        ])->where('id_project', $project)->firstOrFail();

        $logs = ProjectActivityLog::with([
            'user',
            'targetUser',
            'evidence',
        ])
            ->where('project_id', $project->id_project)
            ->latest()
            ->get();

        return view('admin.projects.tracking', compact('project', 'logs'));
    }
}