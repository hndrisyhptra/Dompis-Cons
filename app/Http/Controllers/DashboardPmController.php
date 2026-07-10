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


class DashboardPmController extends Controller
{
    /**
     * 1. MENU: DASHBOARD PM UTAMA (Metrik Makro & Kinerja)
     * Route: pm.dashboard
     */
    public function index()
    {
        // Widget Top Cards
        $totalLop = DB::table('lops')->count();
        
        $projectStats = DB::table('lops')
            ->select(
                DB::raw("COUNT(CASE WHEN status_progress = 'preparation' THEN 1 END) as total_prep"),
                DB::raw("COUNT(CASE WHEN status_progress = 'instalasi' THEN 1 END) as total_inst"),
                DB::raw("COUNT(CASE WHEN status_progress = 'finishing' THEN 1 END) as total_finish")
            )->first();

        $totalEvidence = DB::table('evidences')->count();
        $pendingEvidence = DB::table('evidences')->where('status', 'pending')->count();

        // Statistik Per Wilayah (Branch)
        $statsByBranch = DB::table('lops')
            ->select(
                'branch as label',
                DB::raw("COUNT(id_lop) as total"),
                DB::raw("COUNT(CASE WHEN mapping_status = 'manual_mapped' OR mapping_status = 'auto_matched' THEN 1 END) as assigned"),
                DB::raw("COUNT(CASE WHEN status_progress = 'instalasi' THEN 1 END) as waiting"),
                DB::raw("COUNT(CASE WHEN status_progress = 'finishing' THEN 1 END) as completed")
            )
            ->groupBy('branch')
            ->orderByDesc('total')
            ->get()
            ->map(function($item) {
                $item->percent = $item->total > 0 ? round(($item->completed / $item->total) * 100) : 0;
                return (array) $item;
            });

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

        // Top 8 Kinerja Waspang
        $waspangStats = DB::table('users')
            ->leftJoin('pro_assign', 'users.id_user', '=', 'pro_assign.waspang_id') // Ubah di sini
            ->where('users.role', 'waspang')
            ->select('users.name', DB::raw('COUNT(pro_assign.id_proassign) as total_assignment'))
            ->groupBy('users.id_user', 'users.name') // Ubah di sini juga
            ->orderByDesc('total_assignment')
            ->take(8)
            ->get();

        // LOP Butuh Perhatian
        $attentionProjects = DB::table('lops')
            ->leftJoin('evidences', 'lops.project_id', '=', 'evidences.project_id')
            ->select('lops.id_lop', 'lops.lop_name', 'lops.branch', 'lops.status_progress')
            ->where('lops.mapping_status', 'unmapped')
            ->orWhere('evidences.status', 'rejected')
            ->distinct()
            ->take(8)
            ->get();

        return view('pm.dashboard', compact(
            'totalLop', 'pendingEvidence', 'totalEvidence', 
            'stageSummary', 'statsByBranch', 'waspangStats', 'attentionProjects'
        ));
    }

    /**
     * 2. MENU: REKAP PROGRESS LOP (Detail Kabel, Tiang, Pagination)
     * Route: pm.rekap.progress
     */
    public function rekap(Request $request)
    {
        // 1. Ambil list unik untuk filter dropdown
        $programs = Project::whereNotNull('program')
            ->where('program', '!=', '')
            ->distinct()
            ->orderBy('program', 'asc')
            ->pluck('program');
            
        $branches = DB::table('lops')
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch', 'asc')
            ->pluck('branch');

        // 2. Query Builder dengan Raw SQL Aggregation (Database Engine Level)
        $query = DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('boq_items as b', 'l.id_lop', '=', 'b.lop_id')
            ->leftJoin('designators as d', 'b.designator_id', '=', 'd.id_designator')
            ->select([
                'l.id_lop',
                'l.branch',
                'l.sto',
                'l.lop_name',
                'p.program',
                'p.id_project',
                
                // Agregasi KABEL
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),

                // Agregasi TIANG 
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
            ]);

        // Filter Dropdown Program & Branch
        if ($request->filled('program')) {
            $query->where('p.program', $request->program);
        }
        if ($request->filled('branch')) {
            $query->where('l.branch', $request->branch);
        }

        $perPage = $request->input('per_page', 10);

        // Grouping & Pagination
        $lopsData = $query->groupBy(
            'l.id_lop', 'l.branch', 'l.sto', 'l.lop_name', 'p.program', 'p.id_project'
        )->paginate($perPage)->withQueryString();

        // Ambil total data asli (Diperbaiki agar tidak tertimpa $lopsData->count() di bawah)
        $totalSegments = $lopsData->total(); 

        // 3. Tarik data riwayat relasi Project secara massal (Mencegah N+1)
        $projectIds = $lopsData->pluck('id_project')->unique();
        $projects = Project::with(['evidences', 'boqItems'])
            ->whereIn('id_project', $projectIds)
            ->get()
            ->keyBy('id_project');

        // 4. Kalkulasi Data Summary untuk Widget & View Table
        $totalKabelPlan = 0;
        $totalKabelActual = 0;
        $totalTiangPlan = 0;
        $totalTiangActual = 0;

        $summaryStatus = ['selesai' => 0, 'sedang' => 0, 'rendah' => 0, 'belum' => 0];
        $tableData = [];

        // Menentukan nomor urut dinamis berdasarkan halaman aktif
        $startNumber = ($lopsData->currentPage() - 1) * $lopsData->perPage();

        foreach ($lopsData as $index => $lop) {
            $totalKabelPlan += $lop->kabel_plan;
            $totalKabelActual += $lop->kabel_actual;
            $totalTiangPlan += $lop->tiang_plan;
            $totalTiangActual += $lop->tiang_actual;

            $persenKabel = $lop->kabel_plan > 0 ? ($lop->kabel_actual / $lop->kabel_plan) * 100 : 0;
            $persenTiang = $lop->tiang_plan > 0 ? ($lop->tiang_actual / $lop->tiang_plan) * 100 : 0;

            $projectProgress = 0;
            if (isset($projects[$lop->id_project])) {
                $summary = $projects[$lop->id_project]->progressSummary();
                $projectProgress = $summary['progress'] ?? 0;
            }

            if ($projectProgress >= 100) { 
                $summaryStatus['selesai']++; 
            } elseif ($projectProgress >= 50) { 
                $summaryStatus['sedang']++; 
            } elseif ($projectProgress >= 1) { 
                $summaryStatus['rendah']++; 
            } else { 
                $summaryStatus['belum']++; 
            }

            $tableData[] = [
                'no' => $startNumber + $index + 1, // Perbaikan nomor urut dinamis
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

        $totalKabelPersen = $totalKabelPlan > 0 ? ($totalKabelActual / $totalKabelPlan) * 100 : 0;
        $totalTiangPersen = $totalTiangPlan > 0 ? ($totalTiangActual / $totalTiangPlan) * 100 : 0;

        // View diubah menyesuaikan standar direktori PM yang terisolasi
        return view('pm.rekap-progress', compact(
            'programs', 'branches', 'totalSegments',
            'totalKabelPlan', 'totalKabelActual', 'totalKabelPersen',
            'totalTiangPlan', 'totalTiangActual', 'totalTiangPersen',
            'summaryStatus', 'tableData', 'lopsData'
        ));
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
                'l.id_lop',
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
}