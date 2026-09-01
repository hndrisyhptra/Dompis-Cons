<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Cache;


class DashboardPmController extends Controller
{
    /**
     * 1. MENU: DASHBOARD PM UTAMA (Metrik Makro & Kinerja)
     * Route: pm.dashboard
     */
    public function index()
    {
        // Seluruh data dashboard (matrix, rekap per program, dsb) di-cache singkat
        // (90 detik) supaya buka-tutup menu Dashboard tidak menjalankan ulang semua
        // query berat setiap kali — cukup 1x hit DB per 90 detik untuk SEMUA user PM,
        // sisanya dilayani dari cache. Data BOQ/evidence tetap "cukup real-time"
        // karena jendela cache-nya pendek.
        $data = Cache::remember('pm_dashboard_index_v1', 90, function () {
            return $this->buildIndexData();
        });

        return view('pm.dashboard', $data);
    }

    private function buildIndexData(): array
    {
        $projectStats = DB::table('lops')
            ->select(
                DB::raw("COUNT(CASE WHEN status_progress = 'preparation' THEN 1 END) as total_prep"),
                DB::raw("COUNT(CASE WHEN status_progress = 'instalasi' THEN 1 END) as total_inst"),
                DB::raw("COUNT(CASE WHEN status_progress = 'finishing' THEN 1 END) as total_finish")
            )->first();

        $pendingEvidence = DB::table('evidences')->where('status', 'pending')->count();

        // Ringkasan Pipeline
        $stageSummary = [
            [
                'label' => 'On Progress',
                'value' => $projectStats->total_inst ?? 0,
                'color' => 'indigo',
                'desc' => 'Proyek dalam tahap instalasi fisik lapangan',
            ],
            [
                'label' => 'Waiting Approval',
                'value' => $pendingEvidence,
                'color' => 'amber',
                'desc' => 'Berkas fisik pending menunggu review',
            ],
            [
                'label' => 'Completed',
                'value' => $projectStats->total_finish ?? 0,
                'color' => 'emerald',
                'desc' => 'Proyek menyentuh tahap akhir (Finishing)',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | MATRIX & KPI REAL-TIME (disamakan dengan Dashboard Admin)
        |--------------------------------------------------------------------------
        */
        $regions = [
            'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
            'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
            'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES'],
        ];

        $regularPrograms = collect(['OSP', 'OLO', 'HEM', 'NODE B', 'EKSBIS']);
        $programSet = array_fill_keys($regularPrograms->all(), true);

        $branchToRegion = [];
        foreach ($regions as $regionName => $regionBranches) {
            foreach ($regionBranches as $branchName) {
                $branchToRegion[$branchName] = $regionName;
            }
        }

        $newProgramMap = function () use ($regularPrograms) {
            $map = [];
            foreach ($regularPrograms as $program) {
                $map[$program] = ['preparation' => 0, 'instalasi' => 0, 'finishing' => 0];
            }
            return $map;
        };

        // --- Matrix Regular (Region/Branch x Program x Status) ---
        $matrixAccumulator = [];
        foreach ($regions as $regionName => $regionBranches) {
            $matrixAccumulator[$regionName] = ['programs' => $newProgramMap(), 'branches' => []];
        }

        $matrixRows = DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->where('p.status_project', '!=', 'drop')
            ->get(['l.branch', 'l.status_progress', 'p.program', 'p.is_golive']);

        foreach ($matrixRows as $row) {
            $branch = strtoupper($row->branch ?? '');
            $regionName = $branchToRegion[$branch] ?? null;
            if (!$regionName) {
                continue;
            }

            $program = strtoupper(trim($row->program ?? ''));
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
                $matrixAccumulator[$regionName]['branches'][$branch] = ['programs' => $newProgramMap()];
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

        // --- Matrix PT 2 (Region/Branch x Status) ---
        $emptyPt2Stats = static function () {
            return ['preparation' => 0, 'instalasi' => 0, 'finishing' => 0, 'total' => 0, 'percent' => 0];
        };

        $pt2Accumulator = [];
        foreach ($regions as $regionName => $regionBranches) {
            $pt2Accumulator[$regionName] = ['stats' => $emptyPt2Stats(), 'branches' => []];
            foreach ($regionBranches as $branchName) {
                $pt2Accumulator[$regionName]['branches'][$branchName] = $emptyPt2Stats();
            }
        }

        $pt2Rows = DB::table('pt2_lops as l')
            ->join('pt2_projects as p', 'l.pt2_project_id', '=', 'p.id_pt2_project')
            ->where(function ($q) {
                $q->whereNull('p.status_project')->orWhere('p.status_project', '!=', 'drop');
            })
            ->get([
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

            $isGoLive = (int) ($row->lop_is_golive ?? 0) === 1 || (int) ($row->project_is_golive ?? 0) === 1;
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

        $matrixPt2Data = [];
        foreach ($regions as $regionName => $regionBranches) {
            $regionStats = $pt2Accumulator[$regionName]['stats'];
            $regionStats['percent'] = $regionStats['total'] > 0
                ? round(($regionStats['finishing'] / $regionStats['total']) * 100)
                : 0;

            $branchesData = [];
            foreach ($regionBranches as $branchName) {
                $branchStats = $pt2Accumulator[$regionName]['branches'][$branchName];
                $branchStats['percent'] = $branchStats['total'] > 0
                    ? round(($branchStats['finishing'] / $branchStats['total']) * 100)
                    : 0;
                $branchesData[] = ['name' => $branchName, 'stats' => $branchStats];
            }

            $matrixPt2Data[] = ['region' => $regionName, 'stats' => $regionStats, 'branches' => $branchesData];
        }

        // --- Rekap Assignment & Status (Region/Branch) ---
        // PENTING: eager-load SEMUA relasi yang dipakai Project::progressSummary()
        // (evidences, boqItems.designatorData, boqItems.designatorDataByCode) di sini.
        // Kalau tidak, progressSummary() akan memicu loadMissing() satu-per-satu untuk
        // SETIAP project di loop bawah (N+1 query) — ini penyebab utama dashboard PM
        // terasa berat saat dibuka.
        $lopsForAssignment = Lop::query()
            ->with([
                'project.assignment',
                'project.evidences',
                'project.boqItems.designatorData',
                'project.boqItems.designatorDataByCode',
            ])
            ->whereHas('project', function ($q) {
                $q->where('status_project', '!=', 'drop');
            })
            ->get();

        $statsAccumulator = [];
        foreach ($regions as $regionName => $regionBranches) {
            $statsAccumulator[$regionName] = ['total' => 0, 'assigned' => 0, 'waiting' => 0, 'completed' => 0, 'branches' => []];
        }

        foreach ($lopsForAssignment as $lop) {
            $project = $lop->project;
            if (!$project) {
                continue;
            }

            $branch = strtoupper($lop->branch ?? '');
            $regionName = $branchToRegion[$branch] ?? null;
            if (!$regionName) {
                continue;
            }

            $summary = $project->progressSummary();
            $progress = (int) ($summary['progress'] ?? 0);
            $isAssigned = (bool) $project->assignment;
            $isGoLive = (int) $project->is_golive === 1;
            $isCompleted = $isGoLive || $progress === 100;
            $isWaiting = !$isGoLive && $progress > 0 && $progress < 100;

            $statsAccumulator[$regionName]['total']++;
            if ($isAssigned) $statsAccumulator[$regionName]['assigned']++;
            if ($isWaiting) $statsAccumulator[$regionName]['waiting']++;
            if ($isCompleted) $statsAccumulator[$regionName]['completed']++;

            if (!isset($statsAccumulator[$regionName]['branches'][$branch])) {
                $statsAccumulator[$regionName]['branches'][$branch] = ['total' => 0, 'assigned' => 0, 'waiting' => 0, 'completed' => 0];
            }
            $statsAccumulator[$regionName]['branches'][$branch]['total']++;
            if ($isAssigned) $statsAccumulator[$regionName]['branches'][$branch]['assigned']++;
            if ($isWaiting) $statsAccumulator[$regionName]['branches'][$branch]['waiting']++;
            if ($isCompleted) $statsAccumulator[$regionName]['branches'][$branch]['completed']++;
        }

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
                $branchStats['percent'] = round(($branchStats['completed'] / $branchStats['total']) * 100);
                $branchStats['name'] = $branchName;
                $branchesData[] = $branchStats;
            }

            $statsByRegion[] = [
                'total' => $regionStats['total'],
                'assigned' => $regionStats['assigned'],
                'waiting' => $regionStats['waiting'],
                'completed' => $regionStats['completed'],
                'percent' => round(($regionStats['completed'] / $regionStats['total']) * 100),
                'region' => $regionName,
                'branches' => $branchesData,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | REKAP PROGRESS PER PROGRAM + TOTAL NILAI PER PROGRAM
        |--------------------------------------------------------------------------
        */
        $programRekap = $this->buildProgramRekap($regularPrograms->all());

        return compact(
            'pendingEvidence',
            'stageSummary', 'regularPrograms', 'matrixData', 'matrixPt2Data', 'statsByRegion',
            'programRekap'
        );
    }

    /**
     * Hitung Rekap Progress (Kabel/Tiang Plan vs Actual) dan Total Nilai
     * (Nilai Material + Nilai Jasa berdasarkan harga designator x quantity_actual,
     * dengan Nilai Jasa dipasangkan lewat pair_code ke quantity_actual Material-nya —
     * metodologi yang sama dengan halaman Approval Konstruksi) untuk masing-masing
     * program regular.
     */
    private function buildProgramRekap(array $programs): array
    {
        $progressRows = $this->kabelTiangByProgram($programs);
        $nilaiPerProgram = $this->computeNilaiPerProgram($programs);

        $result = [];
        foreach ($programs as $prog) {
            $row = $progressRows[$prog] ?? null;
            $kabelPlan = (float) ($row->kabel_plan ?? 0);
            $kabelActual = (float) ($row->kabel_actual ?? 0);
            $tiangPlan = (float) ($row->tiang_plan ?? 0);
            $tiangActual = (float) ($row->tiang_actual ?? 0);

            $result[] = [
                'program' => $prog,
                'total_lop' => (int) ($row->total_lop ?? 0),
                'kabel_plan' => $kabelPlan,
                'kabel_actual' => $kabelActual,
                'kabel_persen' => $kabelPlan > 0 ? round(($kabelActual / $kabelPlan) * 100) : 0,
                'tiang_plan' => $tiangPlan,
                'tiang_actual' => $tiangActual,
                'tiang_persen' => $tiangPlan > 0 ? round(($tiangActual / $tiangPlan) * 100) : 0,
                'nilai_total' => $nilaiPerProgram[$prog] ?? 0.0,
            ];
        }

        return $result;
    }

    /**
     * Hitung total Kabel (FO) & Tiang Plan vs Actual per program (key = program yang
     * sudah di-UPPER/TRIM). PENTING: hanya menjumlahkan baris BOQ bertipe designator
     * "material" (d.type = 'material'). Designator hasil "Virtual Split" (import tanpa
     * prefix M-/J- otomatis dipecah jadi 2 baris: material & jasa dengan quantity_plan
     * yang SAMA dan progress_category yang SAMA) — kalau tidak dibatasi ke material saja,
     * SUM(quantity_plan) akan dobel (baris material + baris jasa kembarannya).
     */
    private function kabelTiangByProgram(array $programs)
    {
        return DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('boq_items as b', 'l.id_lop', '=', 'b.lop_id')
            ->leftJoin('designators as d', 'b.designator_id', '=', 'd.id_designator')
            ->where('p.status_project', '!=', 'drop')
            ->whereIn(DB::raw('UPPER(TRIM(p.program))'), $programs)
            ->select([
                DB::raw('UPPER(TRIM(p.program)) as program'),
                DB::raw('COUNT(DISTINCT l.id_lop) as total_lop'),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
            ])
            ->groupBy(DB::raw('UPPER(TRIM(p.program))'))
            ->get()
            ->keyBy('program');
    }

    /**
     * Hitung Total Nilai (Nilai Material + Nilai Jasa berdasarkan harga designator x
     * quantity_actual, dengan Nilai Jasa dipasangkan lewat pair_code ke quantity_actual
     * Material-nya — metodologi yang sama dengan halaman Approval Konstruksi) per
     * program (key = program yang sudah di-UPPER/TRIM).
     */
    private function computeNilaiPerProgram(array $programs): array
    {
        $nilaiRows = DB::table('boq_items as bi')
            ->join('lops as l', 'bi.lop_id', '=', 'l.id_lop')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->join('designators as d', 'bi.designator_id', '=', 'd.id_designator')
            ->where('p.status_project', '!=', 'drop')
            ->whereIn(DB::raw('UPPER(TRIM(p.program))'), $programs)
            ->select([
                'bi.lop_id',
                'bi.designator_id',
                'bi.quantity_actual',
                'l.package_id',
                DB::raw('UPPER(TRIM(p.program)) as program'),
                'd.type',
                'd.pair_code',
            ])
            ->get();

        $packageIds = $nilaiRows->pluck('package_id')->filter()->unique()->values();

        $priceMap = [];
        if ($packageIds->isNotEmpty()) {
            $latestPriceIds = DB::table('designator_package_prices')
                ->selectRaw('MAX(id_price) as id_price')
                ->whereIn('package_id', $packageIds)
                ->groupBy('designator_id', 'package_id');

            $priceRows = DB::table('designator_package_prices as dpp')
                ->joinSub($latestPriceIds, 'latest_price', function ($join) {
                    $join->on('latest_price.id_price', '=', 'dpp.id_price');
                })
                ->selectRaw("dpp.designator_id, dpp.package_id, CAST(NULLIF(TRIM(dpp.price), '') AS DECIMAL(20,2)) AS price")
                ->get();

            foreach ($priceRows as $pr) {
                $priceMap[$pr->designator_id . '_' . $pr->package_id] = (float) $pr->price;
            }
        }

        $nilaiPerProgram = array_fill_keys($programs, 0.0);

        foreach ($nilaiRows->groupBy('lop_id') as $items) {
            $program = strtoupper(trim($items->first()->program ?? ''));
            if (!array_key_exists($program, $nilaiPerProgram)) {
                continue;
            }

            $packageId = $items->first()->package_id;
            $priceFor = function ($designatorId) use ($priceMap, $packageId) {
                return $priceMap[$designatorId . '_' . $packageId] ?? 0.0;
            };

            $materialItems = $items->filter(fn ($it) => strtolower(trim($it->type ?? '')) === 'material');
            $jasaItems = $items->filter(fn ($it) => strtolower(trim($it->type ?? '')) === 'jasa');

            $materialActualByPairCode = [];
            $nilaiMaterialLop = 0.0;
            foreach ($materialItems as $mi) {
                $qty = (float) ($mi->quantity_actual ?? 0);
                $nilaiMaterialLop += $priceFor($mi->designator_id) * $qty;

                $pairCode = $mi->pair_code;
                if ($pairCode !== null && $pairCode !== '') {
                    $materialActualByPairCode[$pairCode] = ($materialActualByPairCode[$pairCode] ?? 0) + $qty;
                }
            }

            $nilaiJasaLop = 0.0;
            foreach ($jasaItems as $ji) {
                $pairCode = $ji->pair_code;
                $qty = ($pairCode !== null && $pairCode !== '' && array_key_exists($pairCode, $materialActualByPairCode))
                    ? $materialActualByPairCode[$pairCode]
                    : (float) ($ji->quantity_actual ?? 0);

                $nilaiJasaLop += $priceFor($ji->designator_id) * $qty;
            }

            $nilaiPerProgram[$program] += $nilaiMaterialLop + $nilaiJasaLop;
        }

        return $nilaiPerProgram;
    }

    /**
     * Endpoint JSON untuk modal "klik angka pada tabel matrix" di Dashboard PM.
     * Menggunakan struktur data & route detail yang sama dengan Dashboard Admin.
     */
    public function matrixDetail(Request $request)
    {
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

        if (!isset($regions[$regionKey])) {
            return response()->json(['message' => 'Region tidak valid.'], 422);
        }

        $branchList = $branchKey !== '' ? [$branchKey] : $regions[$regionKey];

        $rows = collect();
        $title = '';

        if ($type === 'assignment') {
            $query = Lop::query()->with([
                'project.assignment',
                'project.evidences',
                'project.boqItems.designatorData',
                'project.boqItems.designatorDataByCode',
            ]);

            if ($request->filled('f_status')) {
                if ($request->f_status === 'drop') {
                    $query->whereHas('project', function ($q) {
                        $q->where('status_project', 'drop');
                    });
                } else {
                    $query->whereHas('project', function ($q) {
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

                $match = match ($metric) {
                    'assigned' => $isAssigned,
                    'waiting' => $isWaiting,
                    'completed' => $isCompleted,
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
                    'detail_url' => route('admin.projects.tracking', $project->id_project),
                ]);
            }

            $metricLabel = [
                'assigned' => 'Assign',
                'waiting' => 'In Review',
                'completed' => 'Complete (Done)',
            ][$metric] ?? 'Total LOP';

            $title = 'Rekap Assignment — ' . $regionKey . ($branchKey !== '' ? ' / ' . $branchKey : '') . ' — ' . $metricLabel;
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

            $pt2Query->where(function ($q) {
                $q->whereNull('p.status_project')->orWhere('p.status_project', '!=', 'drop');
            });

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

    /**
     * Menampilkan Halaman Peta
     */
    public function map()
    {
        return view('pm.map-monitoring');
    }

    /**
     * Menyediakan Data Koordinat Proyek (JSON API)
     */
    public function mapData(Request $request)
    {
        $projectsData = \Illuminate\Support\Facades\DB::table('projects as p')
            ->leftJoin('lops as l', 'p.id_project', '=', 'l.project_id')
            ->select([
                'p.id_project',
                'p.project_name',
                'p.program',
                'p.kml_file', 
                'p.kml_lat as latitude',   
                'p.kml_lng as longitude',  
                'l.id_ihld',
                'l.lop_name',
                'l.branch',
                'l.sto',
                'l.status_progress'
            ])
            ->whereNotNull('p.kml_file')
            ->whereNotNull('p.kml_lat')
            ->whereNotNull('p.kml_lng')
            ->where('p.kml_lat', '!=', 0)
            ->where('p.kml_lng', '!=', 0)
            ->get()
            ->map(function($project) {
                // KUNCI UTAMA: Ambil URL absolut resmi dari sistem Storage Laravel
                $project->kml_url = asset('storage/' . $project->kml_file);
                return $project;
            })
            ->unique('id_project')
            ->values();

        return response()->json($projectsData);
    }
    /*
    |--------------------------------------------------------------------------
    | FUNGSI REKAP PROGRESS KHUSUS PM
    |--------------------------------------------------------------------------
    */
    public function rekapProgress(Request $request)
    {
        // Tangkap program dari klik sub-menu di sidebar (default: OSP)
        $activeProgram = $request->query('program', 'OSP');

        // Perbandingan program dinormalisasi (UPPER + TRIM) supaya tidak meleset
        // hanya gara-gara perbedaan huruf besar/kecil atau spasi pada data
        // "projects.program" (mis. "Konstruksi Eksternal" vs "KONSTRUKSI EKSTERNAL").
        $normalizedProgram = strtoupper(trim($activeProgram));

        // Daftar cabang untuk filter tambahan di dalam halaman
        $branches = DB::table('lops')->whereNotNull('branch')->where('branch', '!=', '')->distinct()->orderBy('branch', 'asc')->pluck('branch');

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY (DI-FILTER OTOMATIS BERDASARKAN PROGRAM & BRANCH)
        |--------------------------------------------------------------------------
        | - Project berstatus "drop" dikeluarkan (konsisten dengan Dashboard PM/Admin).
        | - Kabel/Tiang HANYA dihitung dari baris BOQ bertipe designator "material".
        |   Designator hasil "Virtual Split" (auto split material+jasa saat import
        |   tanpa prefix M-/J-) menduplikasi quantity_plan pada 2 baris BOQ dengan
        |   progress_category yang sama — kalau tidak dibatasi ke material saja,
        |   Target FO / Target Tiang akan tampil 2x lipat dari nilai sebenarnya.
        */
        $baseQuery = DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('boq_items as b', 'l.id_lop', '=', 'b.lop_id')
            ->leftJoin('designators as d', 'b.designator_id', '=', 'd.id_designator')
            ->where('p.status_project', '!=', 'drop')
            ->whereRaw('UPPER(TRIM(p.program)) = ?', [$normalizedProgram]);

        // Jika PM memfilter branch dari dropdown di dalam halaman
        if ($request->filled('branch')) {
            $baseQuery->where('l.branch', $request->branch);
        }

        /*
        |--------------------------------------------------------------------------
        | 1. DATA STATIS WIDGET ATAS (TOTAL KESELURUHAN PROGRAM AKTIF)
        |--------------------------------------------------------------------------
        */
        // Kita hitung total menggunakan base query TANPA group by per LOP
        $widgetStats = (clone $baseQuery)->select([
            DB::raw('COUNT(DISTINCT l.id_lop) as total_segments'),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
        ])->first();

        // Tetapkan ke variabel untuk dikirim ke Widget Atas & Gauge
        $totalSegments = $widgetStats->total_segments;
        $totalKabelPlan = $widgetStats->kabel_plan;
        $totalKabelActual = $widgetStats->kabel_actual;
        $totalKabelPersen = $totalKabelPlan > 0 ? ($totalKabelActual / $totalKabelPlan) * 100 : 0;

        $totalTiangPlan = $widgetStats->tiang_plan;
        $totalTiangActual = $widgetStats->tiang_actual;
        $totalTiangPersen = $totalTiangPlan > 0 ? ($totalTiangActual / $totalTiangPlan) * 100 : 0;

        // Total Nilai (Material + Jasa, real-time) untuk program aktif ini —
        // metodologi sama dengan Approval Konstruksi & Dashboard PM.
        $nilaiPerProgram = $this->computeNilaiPerProgram([$normalizedProgram]);
        $totalNilaiProgram = $nilaiPerProgram[$normalizedProgram] ?? 0.0;

        /*
        |--------------------------------------------------------------------------
        | 2. DATA TABEL & PAGINATION
        |--------------------------------------------------------------------------
        */
        // Kita clone base query untuk tabel, lalu ditambahkan Group By dan Pagination
        $tableQuery = (clone $baseQuery)->select([
            'l.id_lop', 'l.branch', 'l.sto', 'l.lop_name', 'p.program', 'p.id_project',
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
        ])->groupBy('l.id_lop', 'l.branch', 'l.sto', 'l.lop_name', 'p.program', 'p.id_project');

        $perPage = $request->input('per_page', 10);
        $lopsData = $tableQuery->paginate($perPage)->withQueryString();

        $tableData = [];
        $startNumber = ($lopsData->currentPage() - 1) * $lopsData->perPage();

        foreach ($lopsData as $index => $lop) {
            $persenKabel = $lop->kabel_plan > 0 ? ($lop->kabel_actual / $lop->kabel_plan) * 100 : 0;
            $persenTiang = $lop->tiang_plan > 0 ? ($lop->tiang_actual / $lop->tiang_plan) * 100 : 0;

            $tableData[] = [
                'no' => $startNumber + $index + 1,
                'program' => $lop->program ?? '-',
                'branch' => $lop->branch ?? '-',
                'sto' => $lop->sto ?? '-',
                'nama_lop' => $lop->lop_name ?? '-',
                'kabel_plan' => $lop->kabel_plan,
                'kabel_actual' => $lop->kabel_actual,
                'kabel_persen' => $persenKabel,
                'tiang_plan' => $lop->tiang_plan,
                'tiang_actual' => $lop->tiang_actual,
                'tiang_persen' => $persenTiang,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. DATA CHART BULANAN (DINAMIS OTOMATIS)
        |--------------------------------------------------------------------------
        */
        $currentMonth = \Carbon\Carbon::now()->month; // Mendapatkan angka bulan saat ini (misal: 7 untuk Juli)
        $bulanIndo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        
        $chartLabels = [];
        $chartDataFO = [];
        $chartDataTiang = [];

        for ($i = 0; $i < $currentMonth; $i++) {
            $chartLabels[] = $bulanIndo[$i];
            
            // LOGIKA RIWAYAT PROGRES:
            // Jika Anda sudah punya tabel riwayat update, Anda bisa melakukan query SUM aktual per bulan di sini.
            // Untuk saat ini, kita menempatkan nilai aktual di bulan berjalan, dan menyimulasikan/mengosongkan bulan lalu.
            if ($i == ($currentMonth - 1)) {
                $chartDataFO[] = round($totalKabelPersen, 1);
                $chartDataTiang[] = round($totalTiangPersen, 1);
            } else {
                // TODO: Ganti angka ini dengan query agregasi database per bulan jika riwayat tabel sudah tersedia
                $chartDataFO[] = 0; 
                $chartDataTiang[] = 0; 
            }
        }

        // Pastikan variabel baru ini ikut dikirim ke dalam compact()
        return view('pm.rekap_progress', compact(
            'activeProgram', 'branches', 'lopsData', 'tableData',
            'totalSegments', 'totalKabelPlan', 'totalKabelActual', 'totalKabelPersen',
            'totalTiangPlan', 'totalTiangActual', 'totalTiangPersen',
            'totalNilaiProgram',
            'chartLabels', 'chartDataFO', 'chartDataTiang' // <--- TAMBAHAN BARU
        ));
    }

    /**
     * MENU: KINERJA / PERFORMANCE WASPANG
     */
    public function waspangPerformance(Request $request)
    {
        $branches = DB::table('lops')->whereNotNull('branch')->where('branch', '!=', '')->distinct()->orderBy('branch', 'asc')->pluck('branch');

        /*
        |--------------------------------------------------------------------------
        | QUERY UTAMA: JOIN DARI USERS -> PRO_ASSIGN -> PROJECTS -> LOPS
        |--------------------------------------------------------------------------
        */
        $query = DB::table('users as u')
            ->join('pro_assign as pa', 'pa.waspang_id', '=', 'u.id_user')
            ->join('projects as p', 'p.id_project', '=', 'pa.project_id')
            ->join('lops as l', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('boq_items as b', 'l.id_lop', '=', 'b.lop_id')
            ->leftJoin('designators as d', 'b.designator_id', '=', 'd.id_designator')
            ->select([
                'u.name as waspang_name', 
                DB::raw('COUNT(DISTINCT l.id_lop) as total_segments'),
                
                // Total Kabel FO Plan vs Actual
                // (dibatasi ke d.type = 'material' agar tidak dobel akibat baris
                // "Virtual Split" material+jasa — pola sama dengan rekapProgress()/
                // kabelTiangByProgram() di atas)
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as total_kabel_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as total_kabel_actual"),

                // Total Tiang Plan vs Actual
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as total_tiang_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' AND LOWER(TRIM(d.type)) = 'material' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as total_tiang_actual"),
                
                // Menghitung jumlah berkas eviden milik Waspang ini
                DB::raw("(SELECT COUNT(*) FROM evidences e JOIN pro_assign pa2 ON e.project_id = pa2.project_id WHERE pa2.waspang_id = u.id_user) as total_evidences")
            ])
            ->where('u.role', 'waspang'); // Pastikan hanya role Waspang yang ditarik

        // Terapkan filter Branch jika dipilih
        if ($request->filled('branch')) {
            $query->where('l.branch', $request->branch);
        }

        // PERBAIKAN: Group by ID dan Nama User agar data Waspang tidak duplikat
        $waspangStats = $query->groupBy('u.id_user', 'u.name')->get();

        /*
        |--------------------------------------------------------------------------
        | KALKULASI SKOR & PREDIKAT
        |--------------------------------------------------------------------------
        */
        $performanceData = [];
        foreach ($waspangStats as $index => $stat) {
            $persenKabel = $stat->total_kabel_plan > 0 ? ($stat->total_kabel_actual / $stat->total_kabel_plan) * 100 : 0;
            $persenTiang = $stat->total_tiang_plan > 0 ? ($stat->total_tiang_actual / $stat->total_tiang_plan) * 100 : 0;
            
            $avgPerformance = ($persenKabel + $persenTiang) / 2;

            if ($avgPerformance >= 85) {
                $statusClass = 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300 border-green-200';
                $grade = 'Excellent';
            } elseif ($avgPerformance >= 50) {
                $statusClass = 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border-amber-200';
                $grade = 'Productive';
            } else {
                $statusClass = 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300 border-red-200';
                $grade = 'Underperform';
            }

            $performanceData[] = [
                'no' => $index + 1,
                'name' => $stat->waspang_name,
                'segments' => $stat->total_segments,
                'kabel_persen' => $persenKabel,
                'tiang_persen' => $persenTiang,
                'evidences' => $stat->total_evidences,
                'avg_score' => $avgPerformance,
                'grade' => $grade,
                'class' => $statusClass
            ];
        }

        $totalWaspangActive = count($performanceData);
        $totalAllEvidences = array_sum(array_column($performanceData, 'evidences'));
        
        return view('pm.waspang_performance', compact('performanceData', 'branches', 'totalWaspangActive', 'totalAllEvidences'));
    }
}