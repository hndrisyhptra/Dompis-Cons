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
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user?->role;

        // Isolasi role
        if ($role === 'waspang') {
            return redirect()->route('waspang.dashboard');
        }
        if ($role === 'pm') {
            return redirect()->route('pm.dashboard');
        }
        if ($role === 'teknisi') {
            return redirect()->route('teknisi.pt2.index');
        }
        if ($role === 'sdi') {
            return redirect()->route('sdi.index');
        }

        if ($role !== 'admin') {
            abort(403);
        }

        $regions = [
            'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
            'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
            'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES'],
        ];

        // Program Regular yang selalu ditampilkan pada filter & matrix,
        // termasuk saat count-nya 0.
        $programs = collect([
            'OSP',
            'OLO',
            'HEM',
            'NODE B',
            'EKSBIS',
        ]);

        $programSet = array_fill_keys($programs->all(), true);

        /*
        |--------------------------------------------------------------------------
        | MATRIX DATA - gunakan query ringan + single pass
        |--------------------------------------------------------------------------
        */
        $branchToRegion = [];
        foreach ($regions as $regionName => $regionBranches) {
            foreach ($regionBranches as $branchName) {
                $branchToRegion[$branchName] = $regionName;
            }
        }

        $newProgramMap = function () use ($programs) {
            $map = [];
            foreach ($programs as $program) {
                $map[$program] = [
                    'preparation' => 0,
                    'instalasi' => 0,
                    'finishing' => 0,
                ];
            }
            return $map;
        };

        $matrixAccumulator = [];
        foreach ($regions as $regionName => $regionBranches) {
            $matrixAccumulator[$regionName] = [
                'programs' => $newProgramMap(),
                'branches' => [],
            ];
        }

        $matrixRows = DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->where('p.status_project', '!=', 'drop')
            ->get([
                'l.branch',
                'l.status_progress',
                'p.program',
                'p.is_golive',
            ]);

        foreach ($matrixRows as $row) {
            $branch = strtoupper($row->branch ?? '');
            $regionName = $branchToRegion[$branch] ?? null;

            if (!$regionName) {
                continue;
            }

            $program = strtoupper(trim($row->program ?? ''));

            // Matrix Regular hanya menghitung 5 program yang ditentukan.
            if (!isset($programSet[$program])) {
                continue;
            }

            if ((int) $row->is_golive === 1) {
                $statusKey = 'finishing';
            } elseif ($row->status_progress === 'instalasi') {
                $statusKey = 'instalasi';
            } elseif ($row->status_progress === 'finishing') {
                $statusKey = 'finishing';
            } else {
                $statusKey = 'preparation';
            }

            $matrixAccumulator[$regionName]['programs'][$program][$statusKey]++;

            if (!isset($matrixAccumulator[$regionName]['branches'][$branch])) {
                $matrixAccumulator[$regionName]['branches'][$branch] = [
                    'programs' => $newProgramMap(),
                ];
            }

            $matrixAccumulator[$regionName]['branches'][$branch]['programs'][$program][$statusKey]++;
        }

        $matrixData = [];
        foreach ($regions as $regionName => $regionBranches) {
            $branchesData = [];

            foreach ($regionBranches as $branchName) {
                if (!isset($matrixAccumulator[$regionName]['branches'][$branchName])) {
                    continue;
                }

                $branchesData[] = [
                    'name' => $branchName,
                    'programs' => $matrixAccumulator[$regionName]['branches'][$branchName]['programs'],
                ];
            }

            $matrixData[] = [
                'region' => $regionName,
                'programs' => $matrixAccumulator[$regionName]['programs'],
                'branches' => $branchesData,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | MATRIX PT 2 - query ringan + single pass
        |--------------------------------------------------------------------------
        | PT 2 hanya digunakan untuk Matrix PT 2.
        | Tidak digabung ke KPI, filter Program, atau matrix Regular.
        */
        $emptyPt2Stats = static function () {
            return [
                'preparation' => 0,
                'instalasi' => 0,
                'finishing' => 0,
                'total' => 0,
                'percent' => 0,
            ];
        };

        // Pre-initialize semua Region dan semua Branch agar Branch bernilai 0
        // tetap muncul pada Matrix PT 2.
        $pt2Accumulator = [];

        foreach ($regions as $regionName => $regionBranches) {
            $pt2Accumulator[$regionName] = [
                'stats' => $emptyPt2Stats(),
                'branches' => [],
            ];

            foreach ($regionBranches as $branchName) {
                $pt2Accumulator[$regionName]['branches'][$branchName] = $emptyPt2Stats();
            }
        }

        $pt2MatrixQuery = DB::table('pt2_lops as l')
            ->join('pt2_projects as p', 'l.pt2_project_id', '=', 'p.id_pt2_project');

        // Status "drop" mengikuti status project. Selain itu project drop disembunyikan.
        if ($request->filled('status') && strtolower($request->status) === 'drop') {
            $pt2MatrixQuery->where('p.status_project', 'drop');
        } else {
            $pt2MatrixQuery->where(function ($q) {
                $q->whereNull('p.status_project')
                    ->orWhere('p.status_project', '!=', 'drop');
            });
        }

        // Region berlaku juga untuk Matrix PT 2.
        if ($request->filled('region')) {
            $selectedRegion = strtoupper(trim($request->region));

            if (isset($regions[$selectedRegion])) {
                $pt2MatrixQuery->whereIn(
                    DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(l.branch), ''), p.branch)))"),
                    $regions[$selectedRegion]
                );
            }
        }

        // Branch berlaku juga untuk Matrix PT 2.
        if ($request->filled('branch')) {
            $pt2MatrixQuery->whereRaw(
                "UPPER(TRIM(COALESCE(NULLIF(TRIM(l.branch), ''), p.branch))) = ?",
                [strtoupper(trim($request->branch))]
            );
        }

        /*
        | Status LOP berlaku untuk Matrix PT 2.
        | Go-Live selalu dinormalisasi sebagai finishing.
        */
        if ($request->filled('status')) {
            $selectedStatus = strtolower(trim($request->status));

            if ($selectedStatus === 'finishing') {
                $pt2MatrixQuery->where(function ($q) {
                    $q->whereRaw('LOWER(l.status_progress) = ?', ['finishing'])
                        ->orWhere('l.is_golive', 1)
                        ->orWhere('p.is_golive', 1);
                });
            } elseif (in_array($selectedStatus, ['preparation', 'instalasi'], true)) {
                $pt2MatrixQuery
                    ->whereRaw('LOWER(l.status_progress) = ?', [$selectedStatus])
                    ->where(function ($q) {
                        $q->whereNull('l.is_golive')
                            ->orWhere('l.is_golive', '!=', 1);
                    })
                    ->where(function ($q) {
                        $q->whereNull('p.is_golive')
                            ->orWhere('p.is_golive', '!=', 1);
                    });
            }
        }

        $pt2Rows = $pt2MatrixQuery->get([
            DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(l.branch), ''), p.branch))) as branch"),
            'l.status_progress',
            DB::raw('COALESCE(l.is_golive, 0) as lop_is_golive'),
            DB::raw('COALESCE(p.is_golive, 0) as project_is_golive'),
        ]);

        foreach ($pt2Rows as $row) {
            $branch = strtoupper(trim($row->branch ?? ''));
            $regionName = $branchToRegion[$branch] ?? null;

            if (!$regionName || !isset($pt2Accumulator[$regionName]['branches'][$branch])) {
                continue;
            }

            $isGoLive =
                (int) ($row->lop_is_golive ?? 0) === 1
                || (int) ($row->project_is_golive ?? 0) === 1;

            $statusProgress = strtolower(trim($row->status_progress ?? ''));

            if ($isGoLive || $statusProgress === 'finishing') {
                $statusKey = 'finishing';
            } elseif ($statusProgress === 'instalasi') {
                $statusKey = 'instalasi';
            } else {
                $statusKey = 'preparation';
            }

            $pt2Accumulator[$regionName]['stats'][$statusKey]++;
            $pt2Accumulator[$regionName]['stats']['total']++;

            $pt2Accumulator[$regionName]['branches'][$branch][$statusKey]++;
            $pt2Accumulator[$regionName]['branches'][$branch]['total']++;
        }

        $selectedMatrixRegion = $request->filled('region')
            ? strtoupper(trim($request->region))
            : null;

        $selectedMatrixBranch = $request->filled('branch')
            ? strtoupper(trim($request->branch))
            : null;

        $matrixPt2Data = [];

        foreach ($regions as $regionName => $regionBranches) {
            if ($selectedMatrixRegion && $regionName !== $selectedMatrixRegion) {
                continue;
            }

            $regionStats = $pt2Accumulator[$regionName]['stats'];
            $regionStats['percent'] = $regionStats['total'] > 0
                ? round(($regionStats['finishing'] / $regionStats['total']) * 100)
                : 0;

            $branchesData = [];

            foreach ($regionBranches as $branchName) {
                if ($selectedMatrixBranch && $branchName !== $selectedMatrixBranch) {
                    continue;
                }

                $branchStats = $pt2Accumulator[$regionName]['branches'][$branchName];
                $branchStats['percent'] = $branchStats['total'] > 0
                    ? round(($branchStats['finishing'] / $branchStats['total']) * 100)
                    : 0;

                $branchesData[] = [
                    'name' => $branchName,
                    'stats' => $branchStats,
                ];
            }

            // Jika branch dipilih tetapi branch tersebut bukan milik region ini,
            // region tersebut tidak perlu ditampilkan.
            if ($selectedMatrixBranch && empty($branchesData)) {
                continue;
            }

            $matrixPt2Data[] = [
                'region' => $regionName,
                'stats' => $regionStats,
                'branches' => $branchesData,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | QUERY LOP FILTERED
        |--------------------------------------------------------------------------
        | Hanya eager-load relasi yang benar-benar dipakai oleh index().
        */
        $query = Lop::query()->with([
            'project.assignment',
            'project.evidences',
            'project.boqItems.designatorData',
            'project.boqItems.designatorDataByCode',
        ]);

        if ($request->filled('program')) {
            $selectedProgram = strtoupper(trim($request->program));

            if (isset($programSet[$selectedProgram])) {
                $query->whereHas('project', function ($q) use ($selectedProgram) {
                    $q->whereRaw('UPPER(program) = ?', [$selectedProgram]);
                });
            }
        }

        if ($request->filled('region')) {
            $selectedRegion = strtoupper($request->region);

            if (isset($regions[$selectedRegion])) {
                $query->whereIn(DB::raw('UPPER(branch)'), $regions[$selectedRegion]);
            }
        }

        if ($request->filled('branch')) {
            $query->whereRaw('UPPER(branch) = ?', [strtoupper($request->branch)]);
        }

        if ($request->filled('status')) {
            if ($request->status === 'drop') {
                $query->whereHas('project', function ($q) {
                    $q->where('status_project', 'drop');
                });
            } else {
                $query->where('status_progress', $request->status)
                    ->whereHas('project', function ($q) {
                        $q->where('status_project', '!=', 'drop');
                    });
            }
        } else {
            $query->whereHas('project', function ($q) {
                $q->where('status_project', '!=', 'drop');
            });
        }

        $lops = $query->get();

        /*
        |--------------------------------------------------------------------------
        | KPI + STATS - progressSummary dihitung 1x per project per request
        |--------------------------------------------------------------------------
        */
        $progressCache = [];

        $getProgress = function ($project) use (&$progressCache) {
            if (!$project) {
                return 0;
            }

            $projectKey = (string) ($project->getKey() ?? spl_object_id($project));

            if (!array_key_exists($projectKey, $progressCache)) {
                $summary = $project->progressSummary();
                $progressCache[$projectKey] = (int) ($summary['progress'] ?? 0);
            }

            return $progressCache[$projectKey];
        };

        $totalLop = $lops->count();
        $boqReady = 0;
        $assignedLop = 0;
        $waitingApproval = 0;
        $completedApproval = 0;
        $onProgress = 0;

        $statsAccumulator = [];
        foreach ($regions as $regionName => $regionBranches) {
            $statsAccumulator[$regionName] = [
                'total' => 0,
                'assigned' => 0,
                'waiting' => 0,
                'completed' => 0,
                'branches' => [],
            ];
        }

        foreach ($lops as $lop) {
            $project = $lop->project;

            if ($project?->boqItems?->isNotEmpty()) {
                $boqReady++;
            }

            $isAssigned = (bool) $project?->assignment;
            if ($isAssigned) {
                $assignedLop++;
            }

            if (!$project) {
                continue;
            }

            $progress = $getProgress($project);
            $isGoLive = (int) $project->is_golive === 1;
            $isCompleted = $isGoLive || $progress === 100;
            $isWaitingRegion = !$isGoLive && $progress > 0 && $progress < 100;

            // Pertahankan logika KPI lama.
            if ($progress > 0 && $progress < 100) {
                $waitingApproval++;
            }

            if ($isCompleted) {
                $completedApproval++;
            }

            if ($isWaitingRegion) {
                $onProgress++;
            }

            $branch = strtoupper($lop->branch ?? '');
            $regionName = $branchToRegion[$branch] ?? null;

            if (!$regionName) {
                continue;
            }

            $statsAccumulator[$regionName]['total']++;

            if ($isAssigned) {
                $statsAccumulator[$regionName]['assigned']++;
            }
            if ($isWaitingRegion) {
                $statsAccumulator[$regionName]['waiting']++;
            }
            if ($isCompleted) {
                $statsAccumulator[$regionName]['completed']++;
            }

            if (!isset($statsAccumulator[$regionName]['branches'][$branch])) {
                $statsAccumulator[$regionName]['branches'][$branch] = [
                    'total' => 0,
                    'assigned' => 0,
                    'waiting' => 0,
                    'completed' => 0,
                ];
            }

            $statsAccumulator[$regionName]['branches'][$branch]['total']++;

            if ($isAssigned) {
                $statsAccumulator[$regionName]['branches'][$branch]['assigned']++;
            }
            if ($isWaitingRegion) {
                $statsAccumulator[$regionName]['branches'][$branch]['waiting']++;
            }
            if ($isCompleted) {
                $statsAccumulator[$regionName]['branches'][$branch]['completed']++;
            }
        }

        $belumBoq = max($totalLop - $boqReady, 0);
        $unassignedLop = max($totalLop - $assignedLop, 0);
        $completionRate = $totalLop > 0
            ? round(($completedApproval / $totalLop) * 100)
            : 0;

        // Satu query untuk seluruh summary evidence.
        $evidenceStats = Evidence::query()
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected
            ")
            ->first();

        $totalEvidence = (int) ($evidenceStats->total ?? 0);
        $pendingEvidence = (int) ($evidenceStats->pending ?? 0);
        $approvedEvidence = (int) ($evidenceStats->approved ?? 0);
        $rejectedEvidence = (int) ($evidenceStats->rejected ?? 0);

        $stageSummary = [
            ['label' => 'Belum BOQ', 'value' => $belumBoq, 'color' => 'amber', 'desc' => 'LOP belum memiliki BOQ'],
            ['label' => 'Belum Assign', 'value' => $unassignedLop, 'color' => 'red', 'desc' => 'LOP belum dibagikan ke Waspang'],
            ['label' => 'On Progress', 'value' => $onProgress, 'color' => 'blue', 'desc' => 'Sudah assign dan sedang berjalan'],
            ['label' => 'Waiting Approval', 'value' => $waitingApproval, 'color' => 'orange', 'desc' => 'Progress menunggu review'],
            ['label' => 'Completed', 'value' => $completedApproval, 'color' => 'emerald', 'desc' => 'Progress selesai 100%'],
        ];

        $statsByRegion = [];

        foreach ($regions as $regionName => $regionBranches) {
            $regionStats = $statsAccumulator[$regionName];

            if ($regionStats['total'] === 0) {
                continue;
            }

            $branchesData = [];

            foreach ($regionBranches as $branchName) {
                $branchStats = $regionStats['branches'][$branchName] ?? null;

                if (!$branchStats || $branchStats['total'] === 0) {
                    continue;
                }

                $branchStats['percent'] = round(
                    ($branchStats['completed'] / $branchStats['total']) * 100
                );
                $branchStats['name'] = $branchName;

                $branchesData[] = $branchStats;
            }

            $statsByRegion[] = [
                'total' => $regionStats['total'],
                'assigned' => $regionStats['assigned'],
                'waiting' => $regionStats['waiting'],
                'completed' => $regionStats['completed'],
                'percent' => round(
                    ($regionStats['completed'] / $regionStats['total']) * 100
                ),
                'region' => $regionName,
                'branches' => $branchesData,
            ];
        }

        return view('admin.dashboard', compact(
            'programs',
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
            'stageSummary',
            'statsByRegion',
            'matrixData',
            'matrixPt2Data'
        ));
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
            'status_project' => 'active',
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

    /**
     * MENU: REKAP PROGRESS ADMIN (Detail Kabel, Tiang, Pagination)
     */
    public function rekapProgress(Request $request)
    {
        // 1. Data Mapping Region (Sama seperti di Javascript)
        $regions = [
            'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
            'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
            'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
        ];

        // 2. Ambil list unik untuk filter dropdown program
        $programs = \App\Models\Project::whereNotNull('program')
            ->where('program', '!=', '')
            ->distinct()
            ->orderBy('program', 'asc')
            ->pluck('program');

        /*
        |--------------------------------------------------------------------------
        | 1. DATA KESELURUHAN (DINAMIS MENGIKUTI FILTER) UNTUK CARD ATAS & GAUGE
        |--------------------------------------------------------------------------
        */
        $statsQuery = \Illuminate\Support\Facades\DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('boq_items as b', 'l.id_lop', '=', 'b.lop_id')
            ->leftJoin('designators as d', 'b.designator_id', '=', 'd.id_designator');

        // ================= TERAPKAN FILTER =================
        if ($request->filled('program')) {
            $statsQuery->where('p.program', $request->program);
        }
        if ($request->filled('region')) {
            $selectedRegion = strtoupper($request->region);
            if (isset($regions[$selectedRegion])) {
                $statsQuery->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(l.branch)'), $regions[$selectedRegion]);
            }
        }
        if ($request->filled('branch')) {
            $statsQuery->whereRaw('UPPER(l.branch) = ?', [strtoupper($request->branch)]);
        }
        if ($request->filled('status')) {
            $statsQuery->where('l.status_progress', $request->status);
        }
        // ===================================================

        // Jalankan Query untuk Card Atas & Gauge
        $globalStats = $statsQuery->select([
            \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT l.id_lop) as total_segments'),
            \Illuminate\Support\Facades\DB::raw("SUM(IFNULL(b.quantity_plan, 0) * IFNULL(CAST(b.unit_price AS DECIMAL(20,2)), 0)) as total_nilai_boq"),
            \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
            \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),
            \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
            \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
        ])->first();

        // Variabel untuk Card Atas (Sekarang sudah dinamis mengikuti filter)
        $totalSegments = $globalStats->total_segments ?? 0;
        $totalNilaiBoq = $globalStats->total_nilai_boq ?? 0;
        
        $totalKabelPlan = $globalStats->kabel_plan ?? 0;
        $totalKabelActual = $globalStats->kabel_actual ?? 0;
        $totalKabelPersen = $totalKabelPlan > 0 ? ($totalKabelActual / $totalKabelPlan) * 100 : 0;
        
        $totalTiangPlan = $globalStats->tiang_plan ?? 0;
        $totalTiangActual = $globalStats->tiang_actual ?? 0;
        $totalTiangPersen = $totalTiangPlan > 0 ? ($totalTiangActual / $totalTiangPlan) * 100 : 0;

        /*
        |--------------------------------------------------------------------------
        | 2. DATA UTAMA TABEL (DENGAN FILTER & PAGINATION)
        |--------------------------------------------------------------------------
        */
        $query = \Illuminate\Support\Facades\DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('boq_items as b', 'l.id_lop', '=', 'b.lop_id')
            ->leftJoin('designators as d', 'b.designator_id', '=', 'd.id_designator')
            ->select([
                'l.id_lop', 'l.branch', 'l.sto', 'l.lop_name', 'p.program', 'p.id_project',
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
            ]);

        // ================= TERAPKAN FILTER YANG SAMA =================
        if ($request->filled('program')) {
            $query->where('p.program', $request->program);
        }
        if ($request->filled('region')) {
            $selectedRegion = strtoupper($request->region);
            if (isset($regions[$selectedRegion])) {
                $query->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(l.branch)'), $regions[$selectedRegion]);
            }
        }
        if ($request->filled('branch')) {
            $query->whereRaw('UPPER(l.branch) = ?', [strtoupper($request->branch)]);
        }
        if ($request->filled('status')) {
            $query->where('l.status_progress', $request->status);
        }
        // ==============================================================

        $perPage = $request->input('per_page', 10);
        $lopsData = $query->groupBy('l.id_lop', 'l.branch', 'l.sto', 'l.lop_name', 'p.program', 'p.id_project')
                          ->paginate($perPage)->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | 3. DATA DINAMIS CARD KANAN (SUMMARY HALAMAN INI)
        |--------------------------------------------------------------------------
        */
        $filterSegments = 0;
        $filterKabelPlan = 0;
        $filterKabelActual = 0;
        $filterTiangPlan = 0;
        $filterTiangActual = 0;
        $summaryStatus = ['selesai' => 0, 'sedang' => 0, 'rendah' => 0, 'belum' => 0];
        
        $tableData = [];
        $startNumber = ($lopsData->currentPage() - 1) * $lopsData->perPage();

        $pageProjectIds = collect($lopsData->items())->pluck('id_project')->unique();
        $pageProjects = \App\Models\Project::with(['evidences', 'boqItems'])->whereIn('id_project', $pageProjectIds)->get()->keyBy('id_project');

        foreach ($lopsData as $index => $lop) {
            $persenKabel = $lop->kabel_plan > 0 ? ($lop->kabel_actual / $lop->kabel_plan) * 100 : 0;
            $persenTiang = $lop->tiang_plan > 0 ? ($lop->tiang_actual / $lop->tiang_plan) * 100 : 0;

            $tableData[] = [
                'no' => $startNumber + $index + 1, 
                'program' => $lop->program ?? '-',
                'branch' => strtoupper($lop->branch ?? '-'),
                'sto' => strtoupper($lop->sto ?? '-'),
                'nama_lop' => $lop->lop_name ?? '-',
                'kabel_plan' => $lop->kabel_plan,
                'kabel_actual' => $lop->kabel_actual,
                'kabel_persen' => $persenKabel,
                'tiang_plan' => $lop->tiang_plan,
                'tiang_actual' => $lop->tiang_actual,
                'tiang_persen' => $persenTiang,
            ];

            $filterSegments++;
            $filterKabelPlan += $lop->kabel_plan;
            $filterKabelActual += $lop->kabel_actual;
            $filterTiangPlan += $lop->tiang_plan;
            $filterTiangActual += $lop->tiang_actual;

            $projectProgress = 0;
            if (isset($pageProjects[$lop->id_project])) {
                $summary = $pageProjects[$lop->id_project]->progressSummary();
                $projectProgress = $summary['progress'] ?? 0;
            }

            if ($projectProgress >= 100) { $summaryStatus['selesai']++; }
            elseif ($projectProgress >= 50) { $summaryStatus['sedang']++; }
            elseif ($projectProgress >= 1) { $summaryStatus['rendah']++; }
            else { $summaryStatus['belum']++; }
        }

        // Return Data (Tidak perlu lagi mengirim dashKabelPlan dll karena totalKabelPlan sudah mewakili data filtered)
        return view('admin.dashboard.rekap_progress', compact(
            'programs',
            'totalSegments', 'totalNilaiBoq', 'totalKabelPlan', 'totalKabelActual', 'totalKabelPersen', 'totalTiangPlan', 'totalTiangActual', 'totalTiangPersen',
            'filterSegments', 'filterKabelPlan', 'filterKabelActual', 'filterTiangPlan', 'filterTiangActual', 'summaryStatus',
            'lopsData', 'tableData'
        ));
    }
}