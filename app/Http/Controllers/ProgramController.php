<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Lop;
use App\Models\User;
use App\Models\Designator; // <-- TAMBAHKAN MODEL INI
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Engine Utama Pencarian & Pagination untuk Semua Program (Selain PT 2)
     */
    private function getProgramData(Request $request, $programName)
    {
        $search = $request->input('search');
        $branch = $request->input('branch');

        // Gunakan 'lop' (tunggal) karena relasi Project biasa adalah 1-to-1
        $query = Project::with(['lop', 'assignment.waspang', 'assignment.teknisi'])
            ->where('program', $programName);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('pid', 'like', "%{$search}%")
                  ->orWhere('pid_sap', 'like', "%{$search}%")
                  ->orWhereHas('lop', function ($qLop) use ($search) { 
                      $qLop->where('sto', 'like', "%{$search}%")
                           ->orWhere('branch', 'like', "%{$search}%")
                           ->orWhere('id_ihld', 'like', "%{$search}%");
                  });
            });
        }

        if ($branch) {
            $query->whereHas('lop', function ($qLop) use ($branch) { 
                $qLop->where('branch', $branch);
            });
        }

        $projects = $query->latest('updated_at')->paginate($request->input('per_page', 10))->withQueryString();

        // Ambil unique branch khusus untuk dropdown filter program ini
        $branches = Lop::whereHas('project', function($q) use ($programName) {
                $q->where('program', $programName);
            })
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');

        $assignableUsers = User::whereIn('role', ['teknisi', 'waspang'])->get();
        
        // PERBAIKAN: Ambil data designator untuk dilempar ke modal BOQ
        $designators = Designator::orderBy('designator', 'asc')->get();

        return [
            'projects' => $projects,
            'branches' => $branches,
            'assignableUsers' => $assignableUsers,
            'designators' => $designators // <-- KIRIM VARIABELNYA KE BLADE
        ];
    }

    public function osp(Request $request)
    {
        $data = $this->getProgramData($request, 'OSP');
        return view('admin.program.osp', $data);
    }

    public function nodeb(Request $request)
    {
        $data = $this->getProgramData($request, 'NODE B');
        return view('admin.program.nodeb', $data);
    }

    public function hem(Request $request)
    {
        $data = $this->getProgramData($request, 'HEM');
        return view('admin.program.hem', $data);
    }

    public function olo(Request $request)
    {
        $data = $this->getProgramData($request, 'OLO');
        return view('admin.program.olo', $data);
    }

    public function konstruk(Request $request)
    {
        $data = $this->getProgramData($request, 'Konstruksi Eksternal'); 
        return view('admin.program.konstruk', $data);
    }
}