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
        $role = auth()->user()->role;

        // Sesuai dengan instruksi arsitektur baru: Isolasi Role
        if ($role == 'waspang') { return redirect()->route('waspang.dashboard'); }
        if ($role == 'pm') { return redirect()->route('pm.dashboard'); }
        if ($role == 'teknisi') { return redirect()->route('teknisi.pt2.index'); }
        if ($role == 'sdi') { return redirect()->route('sdi.index'); }

        if ($role == 'admin') {
            
            // 1. Setup Mapping Region (Untuk Filter dan Tabel Rekap)
            $regions = [
                'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
                'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
                'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
            ];

            // 2. Ambil list Program untuk Filter
            $programs = \App\Models\Project::whereNotNull('program')->where('program', '!=', '')
                ->distinct()->orderBy('program', 'asc')->pluck('program');

            // 3. Base Query LOP dengan relasi
            $query = \App\Models\Lop::with([
                'project.assignment',
                'project.assignments.waspang',
                'project.evidences',
                'project.boqItems.designatorData',
                'project.boqItems.designatorDataByCode',
            ]);

            // --- KODE BARU: DATA MATRIX UNFILTERED (TIDAK TERPENGARUH DROPDOWN) ---
            // Kita abaikan project yang berstatus 'drop' agar tidak merusak angka matriks progress
            $allLopsUnfiltered = \App\Models\Lop::with('project')
                ->whereHas('project', function($q) {
                    $q->where('status_project', '!=', 'drop');
                })->get();

            $matrixData = [];
            foreach ($regions as $regionName => $regionBranches) {
                
                // Ambil semua LOP di Region tersebut
                $regionLopsUnfiltered = $allLopsUnfiltered->filter(function ($lop) use ($regionBranches) {
                    return in_array(strtoupper($lop->branch ?? ''), $regionBranches);
                });

                // Hitung per Program untuk Region utama
                $regionProgs = [];
                foreach ($programs as $prog) {
                    $pLops = $regionLopsUnfiltered->filter(function($l) use ($prog) { return ($l->project->program ?? '') === $prog; });
                    $regionProgs[$prog] = [
                        'preparation' => $pLops->where('status_progress', 'preparation')->count(),
                        'instalasi'   => $pLops->where('status_progress', 'instalasi')->count(),
                        'finishing'   => $pLops->where('status_progress', 'finishing')->count(),
                    ];
                }

                // Hitung per Program untuk sub-Branch
                $branchesData = [];
                foreach ($regionBranches as $bName) {
                    $bLops = $regionLopsUnfiltered->filter(function($l) use ($bName) { return strtoupper($l->branch ?? '') === $bName; });
                    
                    if ($bLops->isEmpty()) continue; // Jangan tampilkan branch jika kosong

                    $bProgs = [];
                    foreach ($programs as $prog) {
                        $bpLops = $bLops->filter(function($l) use ($prog) { return ($l->project->program ?? '') === $prog; });
                        $bProgs[$prog] = [
                            'preparation' => $bpLops->where('status_progress', 'preparation')->count(),
                            'instalasi'   => $bpLops->where('status_progress', 'instalasi')->count(),
                            'finishing'   => $bpLops->where('status_progress', 'finishing')->count(),
                        ];
                    }
                    $branchesData[] = [
                        'name' => $bName,
                        'programs' => $bProgs
                    ];
                }

                $matrixData[] = [
                    'region' => $regionName,
                    'programs' => $regionProgs,
                    'branches' => $branchesData
                ];
            } 
            
            // ================= TERAPKAN FILTER =================
            if ($request->filled('program')) {
                $query->whereHas('project', function ($q) use ($request) {
                    $q->where('program', $request->program);
                });
            }
            if ($request->filled('region')) {
                $selectedRegion = strtoupper($request->region);
                if (isset($regions[$selectedRegion])) {
                    $query->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(branch)'), $regions[$selectedRegion]);
                }
            }
            if ($request->filled('branch')) {
                $query->whereRaw('UPPER(branch) = ?', [strtoupper($request->branch)]);
            }

            // FILTER STATUS PINTAR (Memisahkan LOP dan PROJECT)
            if ($request->filled('status')) {
                if ($request->status === 'drop') {
                    // Jika filter 'drop', cari dari tabel Project
                    $query->whereHas('project', function ($q) {
                        $q->where('status_project', 'drop');
                    });
                } else {
                    // Jika filter 'prepare/progress/finish', cari di tabel LOP
                    // Dan pastikan project-nya BUKAN drop
                    $query->where('status_progress', $request->status)
                          ->whereHas('project', function ($q) {
                              $q->where('status_project', '!=', 'drop');
                          });
                }
            } else {
                // Default: Sembunyikan project 'drop' dari perhitungan dashboard aktif
                $query->whereHas('project', function ($q) {
                    $q->where('status_project', '!=', 'drop');
                });
            }
            // ===================================================

            $lops = $query->get(); // Eksekusi Query setelah difilter

            // 4. Kalkulasi Data KPI (Otomatis menyesuaikan filter karena $lops sudah difilter)
            $totalLop = $lops->count();

            $boqReady = $lops->filter(function ($lop) { return $lop->project?->boqItems?->count() > 0; })->count();
            $belumBoq = max($totalLop - $boqReady, 0);

            $assignedLop = $lops->filter(function ($lop) { return $lop->project?->assignment; })->count();
            $unassignedLop = max($totalLop - $assignedLop, 0);

            $waitingApproval = $lops->filter(function ($lop) {
                if (!$lop->project) return false;
                $summary = $lop->project->progressSummary();
                return $summary['progress'] > 0 && $summary['progress'] < 100;
            })->count();

            $completedApproval = $lops->filter(function ($lop) {
                if (!$lop->project) return false;
                $summary = $lop->project->progressSummary();
                return $summary['progress'] == 100;
            })->count();

            $onProgress = max($assignedLop - $completedApproval, 0);
            $completionRate = $totalLop > 0 ? round(($completedApproval / $totalLop) * 100) : 0;

            // Evidence & BOQ Global Summary (Tidak terpengaruh filter LOP, bersifat global)
            $totalEvidence = \App\Models\Evidence::count();
            $pendingEvidence = \App\Models\Evidence::where('status', 'pending')->count();
            $approvedEvidence = \App\Models\Evidence::where('status', 'approved')->count();
            $rejectedEvidence = \App\Models\Evidence::where('status', 'rejected')->count();

            // Stage / Pipeline Summary
            $stageSummary = [
                ['label' => 'Belum BOQ', 'value' => $belumBoq, 'color' => 'amber', 'desc' => 'LOP belum memiliki BOQ'],
                ['label' => 'Belum Assign', 'value' => $unassignedLop, 'color' => 'red', 'desc' => 'LOP belum dibagikan ke Waspang'],
                ['label' => 'On Progress', 'value' => $onProgress, 'color' => 'blue', 'desc' => 'Sudah assign dan sedang berjalan'],
                ['label' => 'Waiting Approval', 'value' => $waitingApproval, 'color' => 'orange', 'desc' => 'Progress menunggu review'],
                ['label' => 'Completed', 'value' => $completedApproval, 'color' => 'emerald', 'desc' => 'Progress selesai 100%'],
            ];

            // 5. Build Data Spesifik: Rekap Assignment & Status per REGION
            $statsByRegion = [];
            foreach ($regions as $regionName => $regionBranches) {
                // Filter LOP khusus Region ini
                $regionLops = $lops->filter(function ($lop) use ($regionBranches) {
                    return in_array(strtoupper($lop->branch ?? ''), $regionBranches);
                });

                if ($regionLops->isEmpty()) continue;

                $calcStats = function ($items) {
                    $total = $items->count();
                    $assigned = $items->filter(function ($lop) { return $lop->project?->assignment; })->count();
                    $completed = $items->filter(function ($lop) { return ($lop->project?->progressSummary()['progress'] ?? 0) == 100; })->count();
                    $waiting = $items->filter(function ($lop) {
                        $prog = $lop->project?->progressSummary()['progress'] ?? 0;
                        return $prog > 0 && $prog < 100;
                    })->count();
                    $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
                    return compact('total', 'assigned', 'waiting', 'completed', 'percent');
                };

                // Rekap Total Region
                $regionData = $calcStats($regionLops);
                $regionData['region'] = $regionName;
                $regionData['branches'] = [];

                // Rekap per Branch di dalam Region tersebut
                foreach ($regionBranches as $branchName) {
                    $branchLops = $regionLops->filter(function ($lop) use ($branchName) {
                        return strtoupper($lop->branch ?? '') === $branchName;
                    });
                    if ($branchLops->isNotEmpty()) {
                        $branchData = $calcStats($branchLops);
                        $branchData['name'] = $branchName;
                        $regionData['branches'][] = $branchData;
                    }
                }
                $statsByRegion[] = $regionData;
            }

            return view('admin.dashboard', compact(
                'programs', // Untuk dropdown filter
                'totalLop', 'boqReady', 'belumBoq', 'assignedLop', 'unassignedLop', 'waitingApproval', 'completedApproval', 'onProgress', 'completionRate',
                'totalEvidence', 'pendingEvidence', 'approvedEvidence', 'rejectedEvidence',
                'stageSummary', 'statsByRegion', 'matrixData'
            ));
        }
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