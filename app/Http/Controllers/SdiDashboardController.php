<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Revisi (permintaan user): menu dashboard baru khusus SDI -- matrix
 * breakdown Region > Branch (Total LOP, Blm Golive, Golive, Persentase)
 * utk kedua program Golive yg ditangani tim SDI: PT 2 (lewat
 * SdiController, model Pt2Lop) & PT 3/Reguler (lewat SdiGoliveController,
 * model Lop) -- DUA TABEL TERPISAH, konsisten dgn pemisahan menu approval
 * yg sudah ada. Region/Branch grouping pakai konstanta yg SAMA persis dgn
 * DashboardController (Admin) supaya konsisten di seluruh app. Klik angka
 * Total/Blm Golive/Golive di tabel membuka modal daftar LOP (AJAX, lihat
 * lops()) -- tidak pindah halaman.
 */
class SdiDashboardController extends Controller
{
    /**
     * Konsisten dgn DashboardController::index() (Admin) baris ~59 --
     * grouping Branch ke Region yg sudah dipakai di seluruh app.
     */
    public const REGIONS = [
        'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
        'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
        'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES'],
    ];

    public function index(Request $request)
    {
        $matrixPt2 = $this->buildMatrix($this->pt2Rows());
        $matrixReguler = $this->buildMatrix($this->regulerRows());

        return view('sdi.dashboard.index', compact('matrixPt2', 'matrixReguler'));
    }

    /**
     * AJAX: daftar LOP utk 1 sel matrix yg diklik (region [+ branch] +
     * status: total/waiting/golive), dipisah per type (pt2/reguler).
     */
    public function lops(Request $request)
    {
        $request->validate([
            'type' => 'required|in:pt2,reguler',
            'region' => 'required|string',
            'branch' => 'nullable|string',
            'status' => 'required|in:total,waiting,golive',
        ]);

        $region = strtoupper(trim($request->region));
        $branch = $request->filled('branch') ? strtoupper(trim($request->branch)) : null;

        if (! isset(self::REGIONS[$region])) {
            return response()->json(['lops' => []]);
        }

        $branches = $branch ? [$branch] : self::REGIONS[$region];

        $rows = $request->type === 'pt2' ? $this->pt2Rows() : $this->regulerRows();

        $filtered = $rows->filter(function ($row) use ($branches, $request) {
            if (! in_array($row->branch, $branches, true)) {
                return false;
            }

            if ($request->status === 'golive') {
                return $row->is_golive_flag;
            }

            if ($request->status === 'waiting') {
                return ! $row->is_golive_flag;
            }

            return true; // total
        })->values();

        $list = $filtered->map(function ($row) {
            return [
                'id' => $row->id,
                'lop_name' => $row->lop_name,
                'branch' => $row->branch,
                'is_golive' => (bool) $row->is_golive_flag,
            ];
        });

        return response()->json(['lops' => $list]);
    }

    private function pt2Rows()
    {
        return DB::table('pt2_lops as l')
            ->join('pt2_projects as p', 'l.pt2_project_id', '=', 'p.id_pt2_project')
            ->whereNotNull('l.sdi_approval_status')
            ->where('l.sdi_approval_status', '!=', '')
            ->get([
                DB::raw("UPPER(TRIM(COALESCE(NULLIF(TRIM(l.branch), ''), p.branch))) as branch"),
                'l.sdi_approval_status',
                DB::raw('COALESCE(l.is_golive, 0) as is_golive'),
                'l.id_pt2_lop as id',
                'l.lop_name',
            ])
            ->map(function ($row) {
                $row->is_golive_flag = $row->sdi_approval_status === 'approved' || (int) $row->is_golive === 1;

                return $row;
            });
    }

    private function regulerRows()
    {
        return DB::table('lops as l')
            ->whereIn('l.status_progress', ['fi_ogp_golive', 'golive'])
            ->whereRaw("UPPER(COALESCE(l.program_sap, '')) NOT LIKE '%PT2%'")
            ->whereRaw("UPPER(COALESCE(l.program_sap, '')) NOT LIKE '%PT-2%'")
            ->whereRaw("UPPER(COALESCE(l.program_sap, '')) NOT LIKE '%PT 2%'")
            ->get([
                DB::raw('UPPER(TRIM(l.branch)) as branch'),
                'l.status_progress',
                DB::raw('COALESCE(l.is_golive, 0) as is_golive'),
                'l.id_lop as id',
                'l.lop_name',
            ])
            ->map(function ($row) {
                $row->is_golive_flag = $row->status_progress === 'golive' || (int) $row->is_golive === 1;

                return $row;
            });
    }

    /**
     * Susun baris hasil pt2Rows()/regulerRows() (sudah punya kolom branch
     * & is_golive_flag) jadi struktur matrix Region > Branch, dgn Region &
     * Branch yg count-nya 0 TETAP muncul (pre-initialize) -- pola sama dgn
     * DashboardController::index() (Admin).
     */
    private function buildMatrix($rows)
    {
        $branchToRegion = [];
        foreach (self::REGIONS as $regionName => $branches) {
            foreach ($branches as $branchName) {
                $branchToRegion[$branchName] = $regionName;
            }
        }

        $accumulator = [];
        foreach (self::REGIONS as $regionName => $branches) {
            $accumulator[$regionName] = [
                'total' => 0,
                'golive' => 0,
                'branches' => array_fill_keys($branches, ['total' => 0, 'golive' => 0]),
            ];
        }

        foreach ($rows as $row) {
            $branch = $row->branch;
            $regionName = $branchToRegion[$branch] ?? null;

            if (! $regionName) {
                continue;
            }

            $accumulator[$regionName]['total']++;
            $accumulator[$regionName]['branches'][$branch]['total']++;

            if ($row->is_golive_flag) {
                $accumulator[$regionName]['golive']++;
                $accumulator[$regionName]['branches'][$branch]['golive']++;
            }
        }

        $matrix = [];
        foreach (self::REGIONS as $regionName => $branches) {
            $regionStats = $accumulator[$regionName];
            $regionStats['belum'] = $regionStats['total'] - $regionStats['golive'];
            $regionStats['percent'] = $regionStats['total'] > 0
                ? round(($regionStats['golive'] / $regionStats['total']) * 100)
                : 0;

            $branchRows = [];
            foreach ($branches as $branchName) {
                $b = $regionStats['branches'][$branchName];
                $b['belum'] = $b['total'] - $b['golive'];
                $b['percent'] = $b['total'] > 0 ? round(($b['golive'] / $b['total']) * 100) : 0;
                $branchRows[] = array_merge(['name' => $branchName], $b);
            }

            $matrix[] = [
                'region' => $regionName,
                'total' => $regionStats['total'],
                'belum' => $regionStats['belum'],
                'golive' => $regionStats['golive'],
                'percent' => $regionStats['percent'],
                'branches' => $branchRows,
            ];
        }

        return $matrix;
    }
}
