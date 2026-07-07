<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardPmController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil list unik untuk filter dropdown (Dibersihkan dari nilai null/kosong)
        $programs = Project::whereNotNull('program')
            ->where('program', '!=', '')
            ->distinct()
            ->orderBy('program', 'asc')
            ->pluck('program');
            
        // Sinkronisasi: Ambil dari lops karena filter query menggunakan l.branch
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
                
                // Agregasi KABEL (TRIM & LOWER untuk mengamankan data entri manual)
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as kabel_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'kabel' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as kabel_actual"),

                // Agregasi TIANG 
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_plan, 0) ELSE 0 END) as tiang_plan"),
                DB::raw("SUM(CASE WHEN TRIM(LOWER(d.progress_category)) = 'tiang' THEN IFNULL(b.quantity_actual, 0) ELSE 0 END) as tiang_actual"),
            ]);

        // Filter Dropdown Program (Tabel Projects)
        if ($request->filled('program')) {
            $query->where('p.program', $request->program);
        }
        
        // Filter Dropdown Branch (Tabel Lops)
        if ($request->filled('branch')) {
            $query->where('l.branch', $request->branch);
        }

        // Menentukan jumlah data per halaman (default: 10)
        $perPage = $request->input('per_page', 10);

        // Grouping & Ubah dari ->get() menjadi ->paginate($perPage)
        $lopsData = $query->groupBy(
            'l.id_lop', 
            'l.branch', 
            'l.sto', 
            'l.lop_name', 
            'p.program', 
            'p.id_project'
        )->paginate($perPage)->withQueryString(); // appends query string agar filter tidak hilang saat ganti halaman

        // Ambil total segment dari total data asli pagination (Bukan count collection halaman ini saja)
        $totalSegments = $lopsData->total();

        // 3. Tarik data riwayat relasi Project secara massal (Mencegah N+1)
        $projectIds = $lopsData->pluck('id_project')->unique();
        $projects = Project::with(['evidences', 'boqItems'])
            ->whereIn('id_project', $projectIds)
            ->get()
            ->keyBy('id_project');

        // 4. Kalkulasi Data Summary untuk Widget & View Table
        $totalSegments = $lopsData->count();
        $totalKabelPlan = 0;
        $totalKabelActual = 0;
        $totalTiangPlan = 0;
        $totalTiangActual = 0;

        $summaryStatus = ['selesai' => 0, 'sedang' => 0, 'rendah' => 0, 'belum' => 0];
        $tableData = [];

        foreach ($lopsData as $index => $lop) {
            // Akumulasi total data terfilter
            $totalKabelPlan += $lop->kabel_plan;
            $totalKabelActual += $lop->kabel_actual;
            $totalTiangPlan += $lop->tiang_plan;
            $totalTiangActual += $lop->tiang_actual;

            // Hitung Persentase individual baris
            $persenKabel = $lop->kabel_plan > 0 ? ($lop->kabel_actual / $lop->kabel_plan) * 100 : 0;
            $persenTiang = $lop->tiang_plan > 0 ? ($lop->tiang_actual / $lop->tiang_plan) * 100 : 0;

            // Membaca Single Source Progress dari model Project
            $projectProgress = 0;
            if (isset($projects[$lop->id_project])) {
                $summary = $projects[$lop->id_project]->progressSummary();
                $projectProgress = $summary['progress'] ?? 0;
            }

            // Klasifikasi Donut Chart sesuai status progress
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
                'no' => $index + 1,
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

       // ... (proses foreach tableData Anda tetap sama seperti sebelumnya) ...

        $totalKabelPersen = $totalKabelPlan > 0 ? ($totalKabelActual / $totalKabelPlan) * 100 : 0;
        $totalTiangPersen = $totalTiangPlan > 0 ? ($totalTiangActual / $totalTiangPlan) * 100 : 0;

        // TAMBAHKAN 'lopsData' ke dalam compact dan aliaskan sebagai tableDataRaw jika perlu, 
        // atau lempar langsung variabel $lopsData-nya
        return view('admin.dashboard.pm', compact(
            'programs', 'branches', 'totalSegments',
            'totalKabelPlan', 'totalKabelActual', 'totalKabelPersen',
            'totalTiangPlan', 'totalTiangActual', 'totalTiangPersen',
            'summaryStatus', 'tableData',
            'lopsData' // <-- 1. TAMBAHKAN INI DI CONTROLLER
        ));
    }
}