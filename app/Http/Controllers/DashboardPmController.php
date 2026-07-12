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

        // Daftar cabang untuk filter tambahan di dalam halaman
        $branches = DB::table('lops')->whereNotNull('branch')->where('branch', '!=', '')->distinct()->orderBy('branch', 'asc')->pluck('branch');

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY (DI-FILTER OTOMATIS BERDASARKAN PROGRAM & BRANCH)
        |--------------------------------------------------------------------------
        */
        $baseQuery = DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('boq_items as b', 'l.id_lop', '=', 'b.lop_id')
            ->leftJoin('designators as d', 'b.designator_id', '=', 'd.id_designator')
            ->where('p.program', $activeProgram);

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
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
        ])->first();

        // Tetapkan ke variabel untuk dikirim ke Widget Atas & Gauge
        $totalSegments = $widgetStats->total_segments;
        $totalKabelPlan = $widgetStats->kabel_plan;
        $totalKabelActual = $widgetStats->kabel_actual;
        $totalKabelPersen = $totalKabelPlan > 0 ? ($totalKabelActual / $totalKabelPlan) * 100 : 0;
        
        $totalTiangPlan = $widgetStats->tiang_plan;
        $totalTiangActual = $widgetStats->tiang_actual;
        $totalTiangPersen = $totalTiangPlan > 0 ? ($totalTiangActual / $totalTiangPlan) * 100 : 0;

        /*
        |--------------------------------------------------------------------------
        | 2. DATA TABEL & PAGINATION
        |--------------------------------------------------------------------------
        */
        // Kita clone base query untuk tabel, lalu ditambahkan Group By dan Pagination
        $tableQuery = (clone $baseQuery)->select([
            'l.id_lop', 'l.branch', 'l.sto', 'l.lop_name', 'p.program', 'p.id_project',
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
            DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
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
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as total_kabel_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as total_kabel_actual"),
                
                // Total Tiang Plan vs Actual
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as total_tiang_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as total_tiang_actual"),
                
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