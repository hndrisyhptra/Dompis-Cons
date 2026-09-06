<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Lop;
use App\Models\User;
use App\Models\Designator; // <-- TAMBAHKAN MODEL INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramController extends Controller
{
    /**
     * Daftar Region -> Branch, dipakai untuk filter Region di halaman Project
     * ID (menu baru role TIF/PM) -- disamakan persis dengan daftar yang
     * dipakai di ImportController::pidRegions() dan DashboardController
     * supaya konsisten di seluruh aplikasi.
     */
    private function pidRegions(): array
    {
        return [
            'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
            'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
            'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES'],
        ];
    }

    /**
     * Opsi Status Project untuk dropdown filter -- disamakan persis dengan
     * ImportController::dataBoq()/dataPid() (kolom projects.status_project,
     * lihat validasi di ImportController::updatePid()).
     */
    private function statusProjectOptions(): array
    {
        return [
            'init' => 'Init',
            'active' => 'Active',
            'close' => 'Close',
            'bast' => 'BAST',
            'drop' => 'Drop',
        ];
    }

    /**
     * Role TIF & PM memakai tampilan Project ID yang berbeda dari Admin --
     * cuma "Detail Project" (modal) & "Tracking Progress", tanpa aksi
     * manajemen (Assign/Edit/Delete/Upload KML) yang memang bukan wewenang
     * kedua role ini. Makanya view-nya diarahkan ke folder pm.program.*,
     * BUKAN admin.program.* (lihat resources/views/pm/program/*.blade.php).
     */
    private function viewForRole(string $slug): string
    {
        $role = auth()->user()?->role;

        return in_array($role, ['tif', 'pm'], true)
            ? "pm.program.{$slug}"
            : "admin.program.{$slug}";
    }

    /**
     * Engine Utama Pencarian & Pagination untuk Semua Program (Selain PT 2)
     */
    private function getProgramData(Request $request, $programName)
    {
        $search = $request->input('search');
        $branch = $request->input('branch');
        $region = $request->input('region');
        $statusProject = $request->input('status_project');
        $regions = $this->pidRegions();

        // Gunakan 'lop' (tunggal) karena relasi Project biasa adalah 1-to-1
        $query = Project::with(['lop', 'assignment.waspang', 'assignment.teknisi'])
            ->where('program', $programName);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('pid', 'like', "%{$search}%")
                  ->orWhere('pid_sap', 'like', "%{$search}%")
                  ->orWhere('mitra_name', 'like', "%{$search}%")
                  ->orWhereHas('lop', function ($qLop) use ($search) {
                      $qLop->where('sto', 'like', "%{$search}%")
                           ->orWhere('branch', 'like', "%{$search}%")
                           ->orWhere('id_ihld', 'like', "%{$search}%")
                           ->orWhere('lop_name', 'like', "%{$search}%");
                  });
            });
        }

        // Region: batasi ke branch-branch yang termasuk region tsb. Kalau
        // branch spesifik JUGA dipilih, filter branch di bawah yang menang
        // (persis pola updateBranchDropdown() di Data BOQ).
        if ($region) {
            $regionKey = strtoupper(trim((string) $region));

            if (isset($regions[$regionKey])) {
                $query->whereHas('lop', function ($qLop) use ($regions, $regionKey) {
                    $qLop->whereIn(DB::raw('UPPER(TRIM(branch))'), $regions[$regionKey]);
                });
            }
        }

        if ($branch) {
            $query->whereHas('lop', function ($qLop) use ($branch) {
                $qLop->where('branch', $branch);
            });
        }

        if ($statusProject) {
            $query->where('status_project', $statusProject);
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

        $assignableUsers = User::roleCode(['teknisi', 'waspang'])->get();

        // PERBAIKAN: Ambil data designator untuk dilempar ke modal BOQ
        $designators = Designator::orderBy('designator', 'asc')->get();

        return [
            'projects' => $projects,
            'branches' => $branches,
            'assignableUsers' => $assignableUsers,
            'designators' => $designators, // <-- KIRIM VARIABELNYA KE BLADE
            'regions' => $regions,
            'statusOptions' => $this->statusProjectOptions(),
            'programName' => $programName,
        ];
    }

    public function osp(Request $request)
    {
        $data = $this->getProgramData($request, 'OSP');
        return view($this->viewForRole('osp'), $data);
    }

    public function nodeb(Request $request)
    {
        $data = $this->getProgramData($request, 'NODE B');
        return view($this->viewForRole('nodeb'), $data);
    }

    public function hem(Request $request)
    {
        $data = $this->getProgramData($request, 'HEM');
        return view($this->viewForRole('hem'), $data);
    }

    public function olo(Request $request)
    {
        $data = $this->getProgramData($request, 'OLO');
        return view($this->viewForRole('olo'), $data);
    }

    public function konstruk(Request $request)
    {
        // Role super_tif & tif tidak boleh melihat project Konstruksi Eksternal
        // sama sekali (role PM TETAP boleh, sesuai permintaan user).
        if (in_array(auth()->user()?->role, ['super_tif', 'tif'], true)) {
            abort(403, 'Program Konstruksi Eksternal tidak tersedia untuk role ini.');
        }

        $data = $this->getProgramData($request, 'Konstruksi Eksternal');
        return view($this->viewForRole('konstruk'), $data);
    }

    /**
     * Terapkan filter search/region/branch/status_project ke query DB raw
     * (dipakai khusus export supaya tidak perlu load relasi Eloquent yang
     * berat untuk seluruh data yang cocok filter, bukan cuma 1 halaman).
     * Polanya disamakan dengan ImportController::applyRegularPidFilters().
     */
    private function applyProgramExportFilters($query, Request $request, array $regions): void
    {
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($like) {
                $q->where('p.pid', 'like', $like)
                    ->orWhere('p.pid_sap', 'like', $like)
                    ->orWhere('p.project_name', 'like', $like)
                    ->orWhere('p.mitra_name', 'like', $like)
                    ->orWhereExists(function ($l) use ($like) {
                        $l->selectRaw('1')
                            ->from('lops as lx')
                            ->whereColumn('lx.project_id', 'p.id_project')
                            ->where(function ($x) use ($like) {
                                $x->where('lx.lop_name', 'like', $like)
                                    ->orWhere('lx.id_ihld', 'like', $like)
                                    ->orWhere('lx.branch', 'like', $like)
                                    ->orWhere('lx.sto', 'like', $like);
                            });
                    });
            });
        }

        if ($request->filled('region')) {
            $region = strtoupper(trim((string) $request->region));

            if (isset($regions[$region])) {
                $branches = $regions[$region];

                $query->whereExists(function ($l) use ($branches) {
                    $l->selectRaw('1')
                        ->from('lops as lx')
                        ->whereColumn('lx.project_id', 'p.id_project')
                        ->whereIn(DB::raw('UPPER(TRIM(lx.branch))'), $branches);
                });
            }
        }

        if ($request->filled('branch')) {
            $branch = strtoupper(trim((string) $request->branch));

            $query->whereExists(function ($l) use ($branch) {
                $l->selectRaw('1')
                    ->from('lops as lx')
                    ->whereColumn('lx.project_id', 'p.id_project')
                    ->whereRaw('UPPER(TRIM(lx.branch)) = ?', [$branch]);
            });
        }

        if ($request->filled('status_project')) {
            $query->where('p.status_project', $request->status_project);
        }
    }

    /**
     * Export data LOP per program ke Excel -- mengikuti filter yang sedang
     * aktif di halaman (search/region/branch/status_project), ATAU seluruh
     * data kalau tidak ada filter aktif (link "Download Semua" mengarah ke
     * URL tanpa query string). Polanya disamakan dengan
     * ImportController::exportPid()/exportBoq().
     */
    private function exportProgramLop(Request $request, string $programName)
    {
        $regions = $this->pidRegions();

        $base = DB::table('projects as p')->where('p.program', $programName);
        $this->applyProgramExportFilters($base, $request, $regions);

        $rows = (clone $base)
            ->leftJoin('lops as l', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('project_assignments as pa', 'pa.project_id', '=', 'p.id_project')
            ->leftJoin('users as uw', 'uw.id_user', '=', 'pa.waspang_id')
            ->leftJoin('users as ut', 'ut.id_user', '=', 'pa.teknisi_id')
            ->orderByDesc('p.id_project')
            ->get([
                'p.pid',
                'p.pid_sap',
                'p.project_name',
                'p.program',
                'p.execution_type',
                'p.status_project',
                'p.mitra_name',
                'l.id_ihld',
                'l.lop_name',
                'l.branch',
                'l.sto',
                'uw.name as waspang_name',
                'ut.name as teknisi_name',
            ]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data LOP ' . Str::limit($programName, 25, ''));

        $headers = [
            'PID', 'PID SAP', 'Nama Project', 'Program', 'Execution Type', 'Status Project',
            'Mitra', 'ID IHLD', 'Nama LOP', 'Branch', 'STO', 'Waspang', 'Teknisi',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row->pid ?? '-',
                $row->pid_sap ?? '-',
                $row->project_name ?? '-',
                $row->program ?? '-',
                $row->execution_type ?? '-',
                $row->status_project ?? '-',
                $row->mitra_name ?? '-',
                $row->id_ihld ?? '-',
                $row->lop_name ?? '-',
                $row->branch ?? '-',
                $row->sto ?? '-',
                $row->waspang_name ?? '-',
                $row->teknisi_name ?? '-',
            ], null, 'A' . $rowIndex);
            $rowIndex++;
        }

        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('DBEAFE');

        foreach (range('A', $lastColumn) as $columnId) {
            $sheet->getColumnDimension($columnId)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $fileName = 'data-lop-' . Str::slug($programName) . '-' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportOsp(Request $request)
    {
        return $this->exportProgramLop($request, 'OSP');
    }

    public function exportNodeb(Request $request)
    {
        return $this->exportProgramLop($request, 'NODE B');
    }

    public function exportHem(Request $request)
    {
        return $this->exportProgramLop($request, 'HEM');
    }

    public function exportOlo(Request $request)
    {
        return $this->exportProgramLop($request, 'OLO');
    }

    public function exportKonstruk(Request $request)
    {
        if (in_array(auth()->user()?->role, ['super_tif', 'tif'], true)) {
            abort(403, 'Program Konstruksi Eksternal tidak tersedia untuk role ini.');
        }

        return $this->exportProgramLop($request, 'Konstruksi Eksternal');
    }
}
