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
        // TIF memakai menu & dashboard yang sama persis dengan PM.
        if ($role === 'pm' || $role === 'tif') {
            return redirect()->route('pm.dashboard');
        }
        if ($role === 'teknisi') {
            return redirect()->route('teknisi.pt2.index');
        }
        if ($role === 'sdi') {
            return redirect()->route('sdi.index');
        }
        if ($role === 'sdi_surveyor') {
            return redirect()->route('surveyor.index');
        }

        // Super Admin memakai dashboard & seluruh menu Admin (lihat sidebar untuk
        // perbedaan tampilannya: tanpa Inbox, dengan User Management).
        // Super TIF memakai tampilan persis seperti Admin, hanya saja seluruh
        // project program Konstruksi Eksternal disembunyikan (lihat filter
        // $isSuperTif di bawah).
        if (!in_array($role, ['admin', 'superadmin', 'super_tif'], true)) {
            abort(403);
        }

        $isSuperTif = $role === 'super_tif';

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

        if ($isSuperTif) {
            $query->whereHas('project', function ($q) {
                $q->whereRaw('UPPER(TRIM(program)) != ?', ['KONSTRUKSI EKSTERNAL']);
            });
        }

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

        // Super TIF punya view dashboard sendiri (resources/views/super_tif/dashboard.blade.php)
        // supaya kolom EKSBIS/Konstruksi Eksternal pada tabel matrix bisa
        // dihilangkan langsung dari filenya, terpisah dari dashboard admin.
        $dashboardView = $isSuperTif ? 'super_tif.dashboard' : 'admin.dashboard';

        return view($dashboardView, compact(
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

    /**
     * MENU: DASHBOARD - DETAIL LOP UNTUK MODAL KLIK ANGKA PADA TABEL MATRIX
     *
     * Dipakai oleh 3 tabel matrix di Dashboard:
     * - type=assignment -> "Rekap Assignment & Status Project Regular"
     * - type=regular    -> "Matriks Progress Project Regular"
     * - type=pt2        -> "Matriks Progress Project PT 2"
     */
    public function matrixDetail(Request $request)
    {
        // Super TIF: sembunyikan seluruh project program Konstruksi Eksternal
        // dari modal detail matrix, sama seperti dashboard utama.
        $isSuperTif = auth()->user()?->role === 'super_tif';

        $regions = [
            'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
            'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
            'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES'],
        ];

        $regularProgramSet = array_fill_keys(['OSP', 'OLO', 'HEM', 'NODE B', 'EKSBIS'], true);

        $type = (string) $request->input('type', '');
        $regionKey = strtoupper(trim((string) $request->input('region', '')));
        $branchKey = strtoupper(trim((string) $request->input('branch', '')));
        $metric = (string) $request->input('metric', '');
        $program = strtoupper(trim((string) $request->input('program', '')));

        // Region kosong = "Semua Region" (dipakai widget ringkasan pipeline
        // yang klik angkanya global, tidak per-region seperti tabel matrix).
        if ($regionKey !== '' && !isset($regions[$regionKey])) {
            return response()->json(['message' => 'Region tidak valid.'], 422);
        }

        $branchList = $branchKey !== ''
            ? [$branchKey]
            : ($regionKey !== '' ? $regions[$regionKey] : collect($regions)->flatten()->all());

        $rows = collect();
        $title = '';

        if ($type === 'assignment') {
            $query = Lop::query()->with([
                'project.assignment',
                'project.evidences',
                'project.boqItems.designatorData',
                'project.boqItems.designatorDataByCode',
            ]);

            if ($isSuperTif) {
                $query->whereHas('project', function ($q) {
                    $q->whereRaw('UPPER(TRIM(program)) != ?', ['KONSTRUKSI EKSTERNAL']);
                });
            }

            if ($request->filled('f_program')) {
                $selectedProgram = strtoupper(trim((string) $request->f_program));
                if (isset($regularProgramSet[$selectedProgram])) {
                    $query->whereHas('project', function ($q) use ($selectedProgram) {
                        $q->whereRaw('UPPER(program) = ?', [$selectedProgram]);
                    });
                }
            }

            if ($request->filled('f_region')) {
                $selRegion = strtoupper(trim((string) $request->f_region));
                if (isset($regions[$selRegion])) {
                    $query->whereIn(DB::raw('UPPER(branch)'), $regions[$selRegion]);
                }
            }

            if ($request->filled('f_branch')) {
                $query->whereRaw('UPPER(branch) = ?', [strtoupper(trim((string) $request->f_branch))]);
            }

            if ($request->filled('f_status')) {
                if ($request->f_status === 'drop') {
                    $query->whereHas('project', function ($q) {
                        $q->where('status_project', 'drop');
                    });
                } else {
                    $query->where('status_progress', $request->f_status)
                        ->whereHas('project', function ($q) {
                            $q->where('status_project', '!=', 'drop');
                        });
                }
            } else {
                $query->whereHas('project', function ($q) {
                    $q->where('status_project', '!=', 'drop');
                });
            }

            $query->whereIn(DB::raw('UPPER(branch)'), $branchList);

            $lops = $query->get();

            foreach ($lops as $lop) {
                $project = $lop->project;

                if (!$project) {
                    continue;
                }

                $summary = $project->progressSummary();
                $progress = (int) ($summary['progress'] ?? 0);
                $isAssigned = (bool) $project->assignment;
                $isGoLive = (int) $project->is_golive === 1;
                $isCompleted = $isGoLive || $progress === 100;
                $isWaiting = !$isGoLive && $progress > 0 && $progress < 100;
                $hasBoq = (bool) $project->boqItems?->isNotEmpty();

                $match = match ($metric) {
                    'assigned' => $isAssigned,
                    'unassigned' => !$isAssigned,
                    'waiting' => $isWaiting,
                    'completed' => $isCompleted,
                    'boq_ready' => $hasBoq,
                    'belum_boq' => !$hasBoq,
                    default => true,
                };

                if (!$match) {
                    continue;
                }

                $statusLabel = $isGoLive
                    ? 'Go-Live'
                    : ($isCompleted
                        ? 'Completed'
                        : ($isWaiting
                            ? 'On Progress'
                            : ($isAssigned ? 'Assigned' : 'Belum Assign')));

                $rows->push([
                    'pid' => $project->pid ?: ($project->pid_sap ?: '-'),
                    'project_name' => $project->project_name ?: '-',
                    'lop_name' => $lop->lop_name ?: '-',
                    'branch' => strtoupper((string) ($lop->branch ?? '-')),
                    'sto' => strtoupper((string) ($lop->sto ?? '-')),
                    'program' => $project->program ?: '-',
                    'progress' => $progress,
                    'status_label' => $statusLabel,
                    'has_boq' => $hasBoq,
                    'detail_url' => route('admin.projects.tracking', $project->id_project),
                ]);
            }

            $metricLabel = [
                'assigned' => 'Sudah Assign',
                'unassigned' => 'Belum Assign',
                'waiting' => 'On Progress',
                'completed' => 'Completed',
                'boq_ready' => 'BOQ Ready',
                'belum_boq' => 'Belum BOQ',
            ][$metric] ?? 'Total LOP';

            $regionLabel = $regionKey !== '' ? $regionKey : 'Semua Region';

            $title = 'Rekap Assignment — ' . $regionLabel . ($branchKey !== '' ? ' / ' . $branchKey : '') . ' — ' . $metricLabel;
        } elseif ($type === 'regular') {
            if (!isset($regularProgramSet[$program])) {
                return response()->json(['message' => 'Program tidak valid.'], 422);
            }

            $lopRows = DB::table('lops as l')
                ->join('projects as p', 'l.project_id', '=', 'p.id_project')
                ->where('p.status_project', '!=', 'drop')
                ->whereRaw('UPPER(TRIM(p.program)) = ?', [$program])
                ->whereIn(DB::raw('UPPER(TRIM(l.branch))'), $branchList)
                ->select([
                    'l.id_lop', 'l.lop_name', 'l.branch', 'l.sto', 'l.status_progress',
                    'p.id_project', 'p.pid', 'p.pid_sap', 'p.project_name', 'p.is_golive',
                ])
                ->get();

            foreach ($lopRows as $row) {
                $statusKey = ((int) $row->is_golive === 1)
                    ? 'finishing'
                    : ($row->status_progress === 'instalasi'
                        ? 'instalasi'
                        : ($row->status_progress === 'finishing' ? 'finishing' : 'preparation'));

                if ($statusKey !== $metric) {
                    continue;
                }

                $rows->push([
                    'pid' => $row->pid ?: ($row->pid_sap ?: '-'),
                    'project_name' => $row->project_name ?: '-',
                    'lop_name' => $row->lop_name ?: '-',
                    'branch' => strtoupper((string) ($row->branch ?? '-')),
                    'sto' => strtoupper((string) ($row->sto ?? '-')),
                    'program' => $program,
                    'progress' => null,
                    'status_label' => ucfirst($statusKey),
                    'detail_url' => route('admin.projects.tracking', $row->id_project),
                ]);
            }

            $metricLabel = [
                'preparation' => 'Prepare',
                'instalasi' => 'Progress',
                'finishing' => 'Finish',
            ][$metric] ?? $metric;

            $title = 'Matriks Regular — ' . $program . ' — ' . $regionKey . ($branchKey !== '' ? ' / ' . $branchKey : '') . ' — ' . $metricLabel;
        } elseif ($type === 'pt2') {
            $pt2Query = DB::table('pt2_lops as l')
                ->join('pt2_projects as p', 'l.pt2_project_id', '=', 'p.id_pt2_project');

            if ($request->filled('f_status') && strtolower((string) $request->f_status) === 'drop') {
                $pt2Query->where('p.status_project', 'drop');
            } else {
                $pt2Query->where(function ($q) {
                    $q->whereNull('p.status_project')
                        ->orWhere('p.status_project', '!=', 'drop');
                });
            }

            if ($request->filled('f_region')) {
                $selRegion = strtoupper(trim((string) $request->f_region));
                if (isset($regions[$selRegion])) {
                    $pt2Query->whereIn(
                        DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(l.branch), ''), p.branch)))"),
                        $regions[$selRegion]
                    );
                }
            }

            if ($request->filled('f_branch')) {
                $pt2Query->whereRaw(
                    "UPPER(TRIM(COALESCE(NULLIF(TRIM(l.branch), ''), p.branch))) = ?",
                    [strtoupper(trim((string) $request->f_branch))]
                );
            }

            $pt2Query->whereIn(
                DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(l.branch), ''), p.branch)))"),
                $branchList
            );

            $pt2Rows = $pt2Query->select([
                'l.id_pt2_lop', 'l.lop_name', 'l.sto',
                DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(l.branch), ''), p.branch))) as branch"),
                'l.status_progress',
                DB::raw('COALESCE(l.is_golive, 0) as lop_is_golive'),
                DB::raw('COALESCE(p.is_golive, 0) as project_is_golive'),
                'p.pid', 'p.pid_sap', 'p.project_name',
            ])->get();

            foreach ($pt2Rows as $row) {
                $isGoLive = (int) ($row->lop_is_golive ?? 0) === 1
                    || (int) ($row->project_is_golive ?? 0) === 1;

                $statusProgress = strtolower(trim((string) ($row->status_progress ?? '')));

                if ($isGoLive || $statusProgress === 'finishing') {
                    $statusKey = 'finishing';
                } elseif ($statusProgress === 'instalasi') {
                    $statusKey = 'instalasi';
                } else {
                    $statusKey = 'preparation';
                }

                if ($metric !== 'total' && $statusKey !== $metric) {
                    continue;
                }

                $rows->push([
                    'pid' => $row->pid ?: ($row->pid_sap ?: '-'),
                    'project_name' => $row->project_name ?: '-',
                    'lop_name' => $row->lop_name ?: '-',
                    'branch' => strtoupper((string) ($row->branch ?? '-')),
                    'sto' => strtoupper((string) ($row->sto ?? '-')),
                    'program' => 'PT 2',
                    'progress' => null,
                    'status_label' => $isGoLive ? 'Go-Live' : ucfirst($statusKey),
                    'detail_url' => route('admin.pt2.tracking', $row->id_pt2_lop),
                ]);
            }

            $metricLabel = [
                'preparation' => 'Preparation',
                'instalasi' => 'Instalasi',
                'finishing' => 'Finishing / Go-Live',
                'total' => 'Total',
            ][$metric] ?? $metric;

            $title = 'Matriks PT 2 — ' . $regionKey . ($branchKey !== '' ? ' / ' . $branchKey : '') . ' — ' . $metricLabel;
        } else {
            return response()->json(['message' => 'Tipe matrix tidak dikenal.'], 422);
        }

        $rows = $rows->values()->map(function ($row, $i) {
            $row['no'] = $i + 1;
            return $row;
        });

        return response()->json([
            'title' => $title,
            'count' => $rows->count(),
            'rows' => $rows,
        ]);
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
        $projectsQuery = Project::with([
            'evidences',
            'assignments.waspang'
        ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if (auth()->user()?->role === 'super_tif') {
            $projectsQuery->whereRaw('UPPER(TRIM(program)) != ?', ['KONSTRUKSI EKSTERNAL']);
        }

        $projects = $projectsQuery->get();

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
        $isSuperTif = auth()->user()?->role === 'super_tif';

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
            ->filter(function ($assignment) use ($isSuperTif) {
                if (!$assignment->project) {
                    return false;
                }

                if ($isSuperTif && strtoupper(trim((string) $assignment->project->program)) === 'KONSTRUKSI EKSTERNAL') {
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
        $isSuperTif = auth()->user()?->role === 'super_tif';

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
            ->filter(function ($assignment) use ($isSuperTif) {
                if (!$assignment->project) {
                    return false;
                }

                if ($isSuperTif && strtoupper(trim((string) $assignment->project->program)) === 'KONSTRUKSI EKSTERNAL') {
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
    /*
    |--------------------------------------------------------------------------
    | REGION
    |--------------------------------------------------------------------------
    */
    $regions = [
        'JATIM' => [
            'SIDOARJO',
            'SURABAYA',
            'MADIUN',
            'JEMBER',
            'LAMONGAN',
            'MALANG',
        ],

        'JATENG DIY' => [
            'YOGYAKARTA',
            'SEMARANG',
            'PURWOKERTO',
            'PEKALONGAN',
            'SURAKARTA',
            'MAGELANG',
        ],

        'BALNUS' => [
            'DENPASAR',
            'KUPANG',
            'MATARAM',
            'FLORES',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | TABLE NAME
    |--------------------------------------------------------------------------
    |
    | Mengikuti nama table dari Model.
    |
    */
    $lopTable = (new \App\Models\Lop())->getTable();
    $projectTable = (new \App\Models\Project())->getTable();
    $boqTable = (new \App\Models\BoqItem())->getTable();
    $designatorTable = (new \App\Models\Designator())->getTable();

    $priceTable =
        (new \App\Models\DesignatorPackagePrice())
            ->getTable();


    /*
    |--------------------------------------------------------------------------
    | FILTER VALUE
    |--------------------------------------------------------------------------
    */
    $selectedProgram = trim(
        (string) $request->input('program', '')
    );

    $selectedRegion = strtoupper(
        trim((string) $request->input('region', ''))
    );

    $selectedBranch = strtoupper(
        trim((string) $request->input('branch', ''))
    );

    $selectedStatus = strtolower(
        trim((string) $request->input('status', ''))
    );

    // Super TIF tidak boleh melihat data program Konstruksi Eksternal sama
    // sekali di rekap progress, baik lewat filter dropdown maupun default.
    $isSuperTif = auth()->user()?->role === 'super_tif';


    /*
    |--------------------------------------------------------------------------
    | PER PAGE
    |--------------------------------------------------------------------------
    |
    | Batasi supaya user tidak bisa mengirim ?per_page=999999
    |
    */
    $perPage = (int) $request->input(
        'per_page',
        10
    );

    if (
        !in_array(
            $perPage,
            [10, 20, 50, 100],
            true
        )
    ) {
        $perPage = 10;
    }


    /*
    |--------------------------------------------------------------------------
    | PROGRAM FILTER
    |--------------------------------------------------------------------------
    */
    $programsQuery = \App\Models\Project::query()
        ->whereNotNull('program')
        ->where('program', '!=', '');

    if ($isSuperTif) {
        $programsQuery->whereRaw('UPPER(TRIM(program)) != ?', ['KONSTRUKSI EKSTERNAL']);
    }

    $programs = $programsQuery
        ->distinct()
        ->orderBy('program')
        ->pluck('program');


    /*
    |--------------------------------------------------------------------------
    | HARGA PACKAGE TERBARU
    |--------------------------------------------------------------------------
    |
    | Bila ada lebih dari satu harga untuk:
    |
    | designator_id + package_id
    |
    | maka id_price terbesar dianggap harga terbaru.
    |
    */
    $latestPriceIds =
        \Illuminate\Support\Facades\DB::table(
            $priceTable
        )
        ->selectRaw(
            'MAX(id_price) AS id_price'
        )
        ->groupBy(
            'designator_id',
            'package_id'
        );


    $currentPriceSub =
        \Illuminate\Support\Facades\DB::table(
            "{$priceTable} as dpp"
        )

        ->joinSub(
            $latestPriceIds,
            'latest_price',
            function ($join) {

                $join->on(
                    'latest_price.id_price',
                    '=',
                    'dpp.id_price'
                );
            }
        )

        ->select([
            'dpp.designator_id',
            'dpp.package_id',
        ])

        ->selectRaw("
            CAST(
                NULLIF(
                    TRIM(dpp.price),
                    ''
                )
                AS DECIMAL(20,2)
            ) AS price
        ");


    /*
    |--------------------------------------------------------------------------
    | AGGREGATE BOQ PER LOP
    |--------------------------------------------------------------------------
    |
    | Hasil subquery ini hanya satu row per LOP.
    |
    | Jadi query utama tidak mengalami row multiplication
    | karena banyak boq_items.
    |
    */
    $lopProgressSub =
        \Illuminate\Support\Facades\DB::table(
            "{$boqTable} as b"
        )

        ->join(
            "{$lopTable} as lp",
            'lp.id_lop',
            '=',
            'b.lop_id'
        )

        ->leftJoin(
            "{$designatorTable} as d",
            'd.id_designator',
            '=',
            'b.designator_id'
        )

        ->leftJoinSub(
            $currentPriceSub,
            'cp',
            function ($join) {

                $join->on(
                    'cp.designator_id',
                    '=',
                    'b.designator_id'
                );

                $join->on(
                    'cp.package_id',
                    '=',
                    'lp.package_id'
                );
            }
        )

        ->select(
            'b.lop_id'
        )


        /*
        |--------------------------------------------------------------------------
        | TOTAL NILAI
        |--------------------------------------------------------------------------
        |
        | Tidak menggunakan boq_items.unit_price.
        |
        | quantity_plan × harga package terbaru
        |
        */
        ->selectRaw("
            SUM(
                CASE
                    WHEN d.type IN (
                        'material',
                        'jasa'
                    )
                    THEN
                        COALESCE(
                            b.quantity_plan,
                            0
                        )
                        *
                        COALESCE(
                            cp.price,
                            0
                        )
                    ELSE 0
                END
            )
            AS total_nilai_boq
        ")


        /*
        |--------------------------------------------------------------------------
        | KABEL PLAN
        |--------------------------------------------------------------------------
        |
        | PENTING: hanya menjumlahkan baris BOQ bertipe designator "material"
        | (d.type = 'material'). Designator hasil "Virtual Split" (import tanpa
        | prefix M-/J- otomatis dipecah jadi 2 baris: material & jasa dengan
        | quantity_plan yang SAMA dan progress_category yang SAMA) — kalau tidak
        | dibatasi ke material saja, SUM(quantity_plan) akan dobel (baris material
        | + baris jasa kembarannya). Pola yang sama dengan
        | DashboardPmController::kabelTiangByProgram()/rekapProgress().
        */
        ->selectRaw("
            SUM(
                CASE
                    WHEN LOWER(
                        TRIM(
                            d.progress_category
                        )
                    ) = 'kabel'
                    AND LOWER(
                        TRIM(
                            d.type
                        )
                    ) = 'material'

                    THEN COALESCE(
                        b.quantity_plan,
                        0
                    )

                    ELSE 0
                END
            )
            AS kabel_plan
        ")


        /*
        |--------------------------------------------------------------------------
        | KABEL ACTUAL
        |--------------------------------------------------------------------------
        */
        ->selectRaw("
            SUM(
                CASE
                    WHEN LOWER(
                        TRIM(
                            d.progress_category
                        )
                    ) = 'kabel'
                    AND LOWER(
                        TRIM(
                            d.type
                        )
                    ) = 'material'

                    THEN COALESCE(
                        b.quantity_actual,
                        0
                    )

                    ELSE 0
                END
            )
            AS kabel_actual
        ")


        /*
        |--------------------------------------------------------------------------
        | TIANG PLAN
        |--------------------------------------------------------------------------
        */
        ->selectRaw("
            SUM(
                CASE
                    WHEN LOWER(
                        TRIM(
                            d.progress_category
                        )
                    ) = 'tiang'
                    AND LOWER(
                        TRIM(
                            d.type
                        )
                    ) = 'material'

                    THEN COALESCE(
                        b.quantity_plan,
                        0
                    )

                    ELSE 0
                END
            )
            AS tiang_plan
        ")


        /*
        |--------------------------------------------------------------------------
        | TIANG ACTUAL
        |--------------------------------------------------------------------------
        */
        ->selectRaw("
            SUM(
                CASE
                    WHEN LOWER(
                        TRIM(
                            d.progress_category
                        )
                    ) = 'tiang'
                    AND LOWER(
                        TRIM(
                            d.type
                        )
                    ) = 'material'

                    THEN COALESCE(
                        b.quantity_actual,
                        0
                    )

                    ELSE 0
                END
            )
            AS tiang_actual
        ")

        ->groupBy(
            'b.lop_id'
        );


    /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    |
    | Digunakan bersama oleh:
    |
    | - KPI / Card
    | - Gauge
    | - Pagination
    |
    | Sehingga filter selalu konsisten.
    |
    */
    $baseQuery =
        \Illuminate\Support\Facades\DB::table(
            "{$lopTable} as l"
        )

        ->join(
            "{$projectTable} as p",
            'l.project_id',
            '=',
            'p.id_project'
        )

        ->leftJoinSub(
            $lopProgressSub,
            'progress',
            function ($join) {

                $join->on(
                    'progress.lop_id',
                    '=',
                    'l.id_lop'
                );
            }
        );


    /*
    |--------------------------------------------------------------------------
    | FILTER PROGRAM
    |--------------------------------------------------------------------------
    */
    if ($selectedProgram !== '') {

        $baseQuery->where(
            'p.program',
            $selectedProgram
        );
    }

    if ($isSuperTif) {
        $baseQuery->whereRaw(
            'UPPER(TRIM(p.program)) != ?',
            ['KONSTRUKSI EKSTERNAL']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FILTER REGION
    |--------------------------------------------------------------------------
    */
    if (
        $selectedRegion !== ''
        &&
        isset(
            $regions[$selectedRegion]
        )
    ) {

        $baseQuery->whereIn(
            \Illuminate\Support\Facades\DB::raw(
                'UPPER(TRIM(l.branch))'
            ),
            $regions[$selectedRegion]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FILTER BRANCH
    |--------------------------------------------------------------------------
    */
    if ($selectedBranch !== '') {

        $baseQuery->whereRaw(
            'UPPER(TRIM(l.branch)) = ?',
            [$selectedBranch]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FILTER STATUS
    |--------------------------------------------------------------------------
    */
    if ($selectedStatus !== '') {

        $baseQuery->whereRaw(
            'LOWER(TRIM(l.status_progress)) = ?',
            [$selectedStatus]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GLOBAL / FILTERED SUMMARY
    |--------------------------------------------------------------------------
    |
    | Jika TIDAK ada filter:
    |
    | → Total seluruh LOP
    | → Total seluruh nilai Jasa + Material
    |
    | Jika ada filter:
    |
    | → otomatis mengikuti Program / Region /
    |   Branch / Status.
    |
    */
    $globalStats =
        (clone $baseQuery)

        ->selectRaw("
            COUNT(l.id_lop)
            AS total_segments
        ")

        ->selectRaw("
            COALESCE(
                SUM(
                    progress.total_nilai_boq
                ),
                0
            )
            AS total_nilai_boq
        ")

        ->selectRaw("
            COALESCE(
                SUM(
                    progress.kabel_plan
                ),
                0
            )
            AS kabel_plan
        ")

        ->selectRaw("
            COALESCE(
                SUM(
                    progress.kabel_actual
                ),
                0
            )
            AS kabel_actual
        ")

        ->selectRaw("
            COALESCE(
                SUM(
                    progress.tiang_plan
                ),
                0
            )
            AS tiang_plan
        ")

        ->selectRaw("
            COALESCE(
                SUM(
                    progress.tiang_actual
                ),
                0
            )
            AS tiang_actual
        ")

        ->first();


    /*
    |--------------------------------------------------------------------------
    | KPI
    |--------------------------------------------------------------------------
    */
    $totalSegments =
        (int) (
            $globalStats->total_segments
            ?? 0
        );


    $totalNilaiBoq =
        (float) (
            $globalStats->total_nilai_boq
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | KABEL
    |--------------------------------------------------------------------------
    */
    $totalKabelPlan =
        (float) (
            $globalStats->kabel_plan
            ?? 0
        );


    $totalKabelActual =
        (float) (
            $globalStats->kabel_actual
            ?? 0
        );


    $totalKabelPersen =
        $totalKabelPlan > 0

            ? (
                $totalKabelActual
                /
                $totalKabelPlan
            ) * 100

            : 0;


    /*
    |--------------------------------------------------------------------------
    | TIANG
    |--------------------------------------------------------------------------
    */
    $totalTiangPlan =
        (float) (
            $globalStats->tiang_plan
            ?? 0
        );


    $totalTiangActual =
        (float) (
            $globalStats->tiang_actual
            ?? 0
        );


    $totalTiangPersen =
        $totalTiangPlan > 0

            ? (
                $totalTiangActual
                /
                $totalTiangPlan
            ) * 100

            : 0;


    /*
    |--------------------------------------------------------------------------
    | TABLE PAGINATION
    |--------------------------------------------------------------------------
    |
    | Tidak perlu GROUP BY lagi.
    |
    | progress subquery sudah 1 row / LOP.
    |
    */
    $lopsData =
        (clone $baseQuery)

        ->select([
            'l.id_lop',
            'l.branch',
            'l.sto',
            'l.lop_name',
            'l.status_progress',

            'p.program',
            'p.id_project',
        ])

        ->selectRaw("
            COALESCE(
                progress.kabel_plan,
                0
            )
            AS kabel_plan
        ")

        ->selectRaw("
            COALESCE(
                progress.kabel_actual,
                0
            )
            AS kabel_actual
        ")

        ->selectRaw("
            COALESCE(
                progress.tiang_plan,
                0
            )
            AS tiang_plan
        ")

        ->selectRaw("
            COALESCE(
                progress.tiang_actual,
                0
            )
            AS tiang_actual
        ")

        ->selectRaw("
            COALESCE(
                progress.total_nilai_boq,
                0
            )
            AS total_nilai_boq
        ")

        ->orderByDesc(
            'l.id_lop'
        )

        ->paginate(
            $perPage
        )

        ->withQueryString();


    /*
    |--------------------------------------------------------------------------
    | PROJECT CURRENT PAGE
    |--------------------------------------------------------------------------
    |
    | progressSummary() tetap dipakai agar logic lama
    | tidak berubah.
    |
    | Tetapi hanya project di page saat ini yang dimuat.
    |
    */
    $pageProjectIds =
        collect(
            $lopsData->items()
        )
        ->pluck(
            'id_project'
        )
        ->filter()
        ->unique()
        ->values();


    $progressByProject =
        collect();


    if (
        $pageProjectIds->isNotEmpty()
    ) {

        $pageProjects =
            \App\Models\Project::query()

            ->with([
                'evidences',
                'boqItems',
            ])

            ->whereIn(
                'id_project',
                $pageProjectIds
            )

            ->get();


        /*
        |--------------------------------------------------------------------------
        | CACHE PROGRESS PER PROJECT
        |--------------------------------------------------------------------------
        |
        | progressSummary() hanya dihitung sekali
        | untuk setiap project.
        |
        */
        $progressByProject =
            $pageProjects
            ->mapWithKeys(
                function ($project) {

                    $summary =
                        $project
                            ->progressSummary();

                    return [
                        $project->id_project
                            =>
                        (float) (
                            $summary['progress']
                            ?? 0
                        ),
                    ];
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | PAGE SUMMARY
    |--------------------------------------------------------------------------
    */
    $filterSegments = 0;

    $filterKabelPlan = 0;
    $filterKabelActual = 0;

    $filterTiangPlan = 0;
    $filterTiangActual = 0;


    $summaryStatus = [
        'selesai' => 0,
        'sedang' => 0,
        'rendah' => 0,
        'belum' => 0,
    ];


    $tableData = [];


    $startNumber =
        (
            $lopsData->currentPage()
            - 1
        )
        *
        $lopsData->perPage();


    /*
    |--------------------------------------------------------------------------
    | LOOP CURRENT PAGE ONLY
    |--------------------------------------------------------------------------
    */
    foreach (
        $lopsData
        as $index => $lop
    ) {

        $kabelPlan =
            (float) (
                $lop->kabel_plan
                ?? 0
            );


        $kabelActual =
            (float) (
                $lop->kabel_actual
                ?? 0
            );


        $tiangPlan =
            (float) (
                $lop->tiang_plan
                ?? 0
            );


        $tiangActual =
            (float) (
                $lop->tiang_actual
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | PERCENT KABEL
        |--------------------------------------------------------------------------
        */
        $persenKabel =
            $kabelPlan > 0

                ? (
                    $kabelActual
                    /
                    $kabelPlan
                ) * 100

                : 0;


        /*
        |--------------------------------------------------------------------------
        | PERCENT TIANG
        |--------------------------------------------------------------------------
        */
        $persenTiang =
            $tiangPlan > 0

                ? (
                    $tiangActual
                    /
                    $tiangPlan
                ) * 100

                : 0;


        /*
        |--------------------------------------------------------------------------
        | TABLE DATA
        |--------------------------------------------------------------------------
        */
        $tableData[] = [

            'no'
                =>
            $startNumber
                +
            $index
                +
            1,


            'program'
                =>
            $lop->program
                ?? '-',


            'branch'
                =>
            strtoupper(
                trim(
                    $lop->branch
                    ?? '-'
                )
            ),


            'sto'
                =>
            strtoupper(
                trim(
                    $lop->sto
                    ?? '-'
                )
            ),


            'nama_lop'
                =>
            $lop->lop_name
                ?? '-',


            'kabel_plan'
                =>
            $kabelPlan,


            'kabel_actual'
                =>
            $kabelActual,


            'kabel_persen'
                =>
            $persenKabel,


            'tiang_plan'
                =>
            $tiangPlan,


            'tiang_actual'
                =>
            $tiangActual,


            'tiang_persen'
                =>
            $persenTiang,


            'total_nilai_boq'
                =>
            (float) (
                $lop->total_nilai_boq
                ?? 0
            ),
        ];


        /*
        |--------------------------------------------------------------------------
        | SUMMARY PAGE
        |--------------------------------------------------------------------------
        */
        $filterSegments++;

        $filterKabelPlan
            +=
        $kabelPlan;

        $filterKabelActual
            +=
        $kabelActual;

        $filterTiangPlan
            +=
        $tiangPlan;

        $filterTiangActual
            +=
        $tiangActual;


        /*
        |--------------------------------------------------------------------------
        | PROJECT PROGRESS
        |--------------------------------------------------------------------------
        */
        $projectProgress =
            (float) (
                $progressByProject
                    ->get(
                        $lop->id_project,
                        0
                    )
            );


        if (
            $projectProgress >= 100
        ) {

            $summaryStatus['selesai']++;

        } elseif (
            $projectProgress >= 50
        ) {

            $summaryStatus['sedang']++;

        } elseif (
            $projectProgress >= 1
        ) {

            $summaryStatus['rendah']++;

        } else {

            $summaryStatus['belum']++;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */
    return view(
        'admin.dashboard.rekap_progress',
        compact(
            'regions',
            'programs',
            'totalSegments',
            'totalNilaiBoq',
            'totalKabelPlan',
            'totalKabelActual',
            'totalKabelPersen',
            'totalTiangPlan',
            'totalTiangActual',
            'totalTiangPersen',
            'filterSegments',
            'filterKabelPlan',
            'filterKabelActual',
            'filterTiangPlan',
            'filterTiangActual',
            'summaryStatus',
            'lopsData',
            'tableData'
        )
    );
}
}