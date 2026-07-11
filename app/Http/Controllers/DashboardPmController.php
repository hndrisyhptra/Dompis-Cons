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
}