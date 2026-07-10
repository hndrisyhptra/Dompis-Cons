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
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $role = auth()->user()->role;

        // Sesuai dengan instruksi arsitektur baru: Isolasi Role PM dan Waspang
        if ($role == 'waspang') {
            return redirect()->route('waspang.dashboard');
        }

        if ($role == 'pm') {
            return redirect()->route('pm.dashboard'); // Dialihkan ke Controller Mandiri PM
        }

        if ($role == 'admin') {
            $lops = Lop::with([
            'project.assignment',
            'project.assignments.waspang',
            'project.evidences',
            'project.boqItems.designatorData',
            'project.boqItems.designatorDataByCode',
        ])->get();

        $totalLop = $lops->count();

        $boqReady = $lops->filter(function ($lop) {
            return $lop->project?->boqItems?->count() > 0;
        })->count();

        $belumBoq = max($totalLop - $boqReady, 0);

        $assignedLop = $lops->filter(function ($lop) {
            return $lop->project?->assignment;
        })->count();

        $unassignedLop = max($totalLop - $assignedLop, 0);

        $waitingApproval = $lops->filter(function ($lop) {
            if (!$lop->project) {
                return false;
            }

            $summary = $lop->project->progressSummary();

            return $summary['progress'] > 0 && $summary['progress'] < 100;
        })->count();

        $completedApproval = $lops->filter(function ($lop) {
            if (!$lop->project) {
                return false;
            }

            $summary = $lop->project->progressSummary();

            return $summary['progress'] == 100;
        })->count();

        $onProgress = max($assignedLop - $completedApproval, 0);

        $completionRate = $totalLop > 0
            ? round(($completedApproval / $totalLop) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Evidence Summary
        |--------------------------------------------------------------------------
        */
        $totalEvidence = Evidence::count();

        $pendingEvidence = Evidence::where('status', 'pending')->count();

        $approvedEvidence = Evidence::where('status', 'approved')->count();

        $rejectedEvidence = Evidence::where('status', 'rejected')->count();

        /*
        |--------------------------------------------------------------------------
        | BOQ Summary
        |--------------------------------------------------------------------------
        */
        $totalBoqItem = BoqItem::count();

        $totalBoqValue = BoqItem::sum('total_price');

        $materialItem = BoqItem::where('designator', 'like', 'M-%')->count();

        $jasaItem = BoqItem::where('designator', 'like', 'J-%')->count();

        $boqActualItem = BoqItem::where('quantity_actual', '>', 0)->count();

        $boqActualRate = $totalBoqItem > 0
            ? round(($boqActualItem / $totalBoqItem) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Stage / Pipeline Summary
        |--------------------------------------------------------------------------
        */
        $stageSummary = [
            [
                'label' => 'Belum BOQ',
                'value' => $belumBoq,
                'color' => 'amber',
                'desc' => 'LOP belum memiliki BOQ',
            ],
            [
                'label' => 'Belum Assign',
                'value' => $unassignedLop,
                'color' => 'red',
                'desc' => 'LOP belum dibagikan ke Waspang',
            ],
            [
                'label' => 'On Progress',
                'value' => $onProgress,
                'color' => 'blue',
                'desc' => 'Sudah assign dan sedang berjalan',
            ],
            [
                'label' => 'Waiting Approval',
                'value' => $waitingApproval,
                'color' => 'orange',
                'desc' => 'Progress menunggu review',
            ],
            [
                'label' => 'Completed',
                'value' => $completedApproval,
                'color' => 'emerald',
                'desc' => 'Progress selesai 100%',
            ],
        ];

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

        /*
        |--------------------------------------------------------------------------
        | Waspang Performance
        |--------------------------------------------------------------------------
        */
        $waspangStats = User::where('role', 'waspang')
            ->withCount([
                'assignments as total_assignment',
            ])
            ->orderByDesc('total_assignment')
            ->take(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Project Butuh Perhatian
        |--------------------------------------------------------------------------
        */
        $attentionProjects = $lops
            ->filter(function ($lop) {
                if (!$lop->project) {
                    return true;
                }

                $hasBoq = $lop->project->boqItems?->count() > 0;
                $hasAssignment = $lop->project?->assignment;
                $hasRejected = $lop->project->evidences?->where('status', 'rejected')->count() > 0;

                return !$hasBoq || !$hasAssignment || $hasRejected;
            })
            ->take(8)
            ->values();

        return view('admin.dashboard', compact(
            'totalLop',
            'boqReady',
            'belumBoq',
            'assignedLop',
            'unassignedLop',
            'waitingApproval',
            'completedApproval',
            'onProgress',
            'completionRate',

            'totalEvidence',
            'pendingEvidence',
            'approvedEvidence',
            'rejectedEvidence',

            'totalBoqItem',
            'totalBoqValue',
            'materialItem',
            'jasaItem',
            'boqActualItem',
            'boqActualRate',

            'stageSummary',

            'statsByBatch',
            'statsByBranch',
            'statsByProgram',

            'waspangStats',
            'attentionProjects'
        ));
    }
            return view('admin.dashboard', compact(
                'totalLop', 'boqReady', 'belumBoq', 'assignedLop', 'unassignedLop',
                'waitingApproval', 'completedApproval', 'onProgress', 'completionRate',
                'totalEvidence', 'pendingEvidence', 'approvedEvidence', 'rejectedEvidence',
                'totalBoqItem', 'totalBoqValue', 'materialItem', 'jasaItem',
                'boqActualItem', 'boqActualRate', 'stageSummary', 'statsByBatch',
                'statsByBranch', 'statsByProgram', 'waspangStats', 'attentionProjects'
            ));
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

    public function adminInbox(Request $request)
{
    $search = $request->search;

    $assignments = ProjectAssignment::with([
        'project.lop',
        'project.evidences',
        'project.boqItems',
        'waspang',
        'admin',
    ])
        ->where('assigned_by', auth()->user()->id_user)
        ->latest()
        ->get()
        ->filter(function ($assignment) {
            if (!$assignment->project) {
                return false;
            }

            $summary = $assignment->project->progressSummary();

            return $summary['progress'] < 100;
        });

    if ($search) {
        $assignments = $assignments->filter(function ($assignment) use ($search) {
            $project = $assignment->project;
            $lop = $project?->lop;

            return str_contains(strtolower($project?->pid ?? ''), strtolower($search))
                || str_contains(strtolower($project?->pid_sap ?? ''), strtolower($search))
                || str_contains(strtolower($project?->project_name ?? ''), strtolower($search))
                || str_contains(strtolower($lop?->lop_name ?? ''), strtolower($search))
                || str_contains(strtolower($lop?->sto ?? ''), strtolower($search))
                || str_contains(strtolower($lop?->branch ?? ''), strtolower($search));
        });
    }

        $assignments = $assignments->values();

        $page = request()->get('page', 1);
        $perPage = 20;

        $assignments = new LengthAwarePaginator(
            $assignments->forPage($page, $perPage),
            $assignments->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

    return view('admin.inbox.index', compact('assignments', 'search'));
}

    public function adminHistory(Request $request)
    {
        $search = $request->search;

        $assignments = ProjectAssignment::with([
            'project.lop',
            'project.evidences',
            'project.boqItems',
            'waspang',
            'admin',
        ])
            ->where('assigned_by', auth()->user()->id_user)
            ->latest()
            ->get()
            ->filter(function ($assignment) {
                if (!$assignment->project) {
                    return false;
                }

                $summary = $assignment->project->progressSummary();

                return $summary['progress'] >= 100;
            });

        if ($search) {
            $assignments = $assignments->filter(function ($assignment) use ($search) {
                $project = $assignment->project;
                $lop = $project?->lop;

                return str_contains(strtolower($project?->pid ?? ''), strtolower($search))
                    || str_contains(strtolower($project?->pid_sap ?? ''), strtolower($search))
                    || str_contains(strtolower($project?->project_name ?? ''), strtolower($search))
                    || str_contains(strtolower($lop?->lop_name ?? ''), strtolower($search))
                    || str_contains(strtolower($lop?->sto ?? ''), strtolower($search))
                    || str_contains(strtolower($lop?->branch ?? ''), strtolower($search));
            });
        }

        $assignments = $assignments->values();

        $page = request()->get('page', 1);
        $perPage = 20;

        $assignments = new LengthAwarePaginator(
            $assignments->forPage($page, $perPage),
            $assignments->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('admin.inbox.history', compact('assignments', 'search'));
    }
}