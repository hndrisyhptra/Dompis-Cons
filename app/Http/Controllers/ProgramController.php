<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Models\Designator;
use App\Models\Lop;
use Illuminate\Support\Facades\DB;

class ProgramController extends Controller
{
    /**
     * MAPPING REGION & BRANCH (Digunakan di banyak tempat)
     */
    private $regions = [
        'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
        'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
        'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
    ];

    /**
     * Fungsi Helper Inti untuk mengambil data per program, KPI, dan Matrix
     */
    private function getProjectData(Request $request, $programName)
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status_project');
        $region = $request->input('region');
        $branch = $request->input('branch');

        // 1. QUERY DASAR
        $query = Project::with(['lops', 'assignment.teknisi', 'assignment.waspang', 'evidences'])
            ->where('program', $programName);

        // 2. FILTER PENCARIAN
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('pid_sap', 'like', "%{$search}%")
                  ->orWhereHas('lops', function ($qLop) use ($search) {
                      $qLop->where('id_ihld', 'like', "%{$search}%")
                           ->orWhere('lop_name', 'like', "%{$search}%")
                           ->orWhere('sto', 'like', "%{$search}%");
                  });
            });
        }

        // 3. FILTER DROPDOWN (STATUS, REGION, BRANCH)
        if ($statusFilter) {
            $query->where('status_project', $statusFilter);
        }
        
        // Perbaikan Region & Branch: Cek di tabel projects ATAU lops
        if ($region && isset($this->regions[strtoupper($region)])) {
            $regionBranches = $this->regions[strtoupper($region)];
            $query->where(function ($q) use ($regionBranches) {
                $q->whereIn(DB::raw('UPPER(branch)'), $regionBranches)
                  ->orWhereHas('lops', function ($qLop) use ($regionBranches) {
                      $qLop->whereIn(DB::raw('UPPER(branch)'), $regionBranches);
                  });
            });
        }
        
        if ($branch) {
            $query->where(function ($q) use ($branch) {
                $q->whereRaw('UPPER(branch) = ?', [strtoupper($branch)])
                  ->orWhereHas('lops', function ($qLop) use ($branch) {
                      $qLop->whereRaw('UPPER(branch) = ?', [strtoupper($branch)]);
                  });
            });
        }

        // 4. HITUNG WIDGET KPI (Dinamis sesuai filter)
        $kpiQuery = clone $query;
        $totalLop = Lop::whereIn('project_id', (clone $kpiQuery)->select('id_project'))->count();
        $activelop = (clone $kpiQuery)->whereIn('status_project', ['init', 'active'])->count();
        $complete = (clone $kpiQuery)->whereIn('status_project', ['close', 'bast', 'completed'])->count();
        $drop = (clone $kpiQuery)->where('status_project', 'drop')->count();

        // 5. EKSEKUSI PAGINASI
        $projects = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        // 6. AMBIL DATA MATRIX UNFILTERED (Khusus program ini saja)
        $matrixData = $this->getMatrixData($programName);

        return compact('projects', 'totalLop', 'activelop', 'complete', 'drop', 'matrixData');
    }

    /**
     * Fungsi Helper untuk generate Matrix Unfiltered per Program
     */
    private function getMatrixData($programName)
    {
        // Tarik HANYA status dan branch untuk mempercepat query
        $allProjects = Project::where('program', $programName)
            ->select('branch', 'status_project', 'id_project')
            ->with(['lops' => function($q) {
                $q->select('project_id', 'branch'); // Ambil branch dari LOP buat fallback
            }])
            ->get();

        $matrix = [];
        $statuses = ['init', 'active', 'close', 'bast', 'drop'];

        foreach ($this->regions as $regionName => $branches) {
            $regionData = [
                'region' => $regionName,
                'stats' => array_fill_keys($statuses, 0),
                'branches' => []
            ];

            foreach ($branches as $branchName) {
                $branchStats = array_fill_keys($statuses, 0);

                // Filter project berdasarkan branch
                $branchProjects = $allProjects->filter(function($p) use ($branchName) {
                    $projBranch = strtoupper($p->branch ?? optional($p->lops->first())->branch ?? '');
                    return $projBranch === strtoupper($branchName);
                });

                foreach ($branchProjects as $bp) {
                    $status = strtolower($bp->status_project ?? 'active');
                    if (!in_array($status, $statuses)) $status = 'active'; // Fallback
                    
                    $branchStats[$status]++;
                    $regionData['stats'][$status]++; // Tambah ke total Region
                }

                $regionData['branches'][] = [
                    'name' => $branchName,
                    'stats' => $branchStats
                ];
            }
            $matrix[] = $regionData;
        }

        return $matrix;
    }

    /**
     * Fungsi Helper untuk mengambil data master (Users & Designators)
     */
    private function getMasterData()
    {
        // Ganti 'status' menjadi 'is_active' atau kolom yang sesuai dengan database Anda
        // Jika tabel User tidak ada kolom status, HAPUS saja ->where('status', ...)
        $assignableUsers = User::whereIn('role', ['waspang', 'teknisi'])
            ->orderBy('name')
            ->get();

        $designators = Designator::orderBy('designator')->get();

        return compact('assignableUsers', 'designators');
    }

    // =========================================================================
    // METHOD UNTUK MASING-MASING PROGRAM
    // =========================================================================

    public function osp(Request $request)
    {
        $data = $this->getProjectData($request, 'OSP');
        $masterData = $this->getMasterData();

        return view('admin.programs.osp', array_merge($data, $masterData));
    }

    public function nodeB(Request $request)
    {
        $data = $this->getProjectData($request, 'NODE B');
        $masterData = $this->getMasterData();

        return view('admin.programs.node-b', array_merge($data, $masterData));
    }

    public function hem(Request $request)
    {
        $data = $this->getProjectData($request, 'HEM');
        $masterData = $this->getMasterData();

        return view('admin.programs.hem', array_merge($data, $masterData));
    }

    public function olo(Request $request)
    {
        $data = $this->getProjectData($request, 'OLO');
        $masterData = $this->getMasterData();

        return view('admin.programs.olo', array_merge($data, $masterData));
    }

    public function konstrukEksternal(Request $request)
    {
        $data = $this->getProjectData($request, 'Konstruk Eksternal');
        $masterData = $this->getMasterData();

        return view('admin.programs.konstruk-eksternal', array_merge($data, $masterData));
    }
}