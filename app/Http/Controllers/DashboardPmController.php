<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Lop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardPmController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil list unik untuk filter dropdown (Cepat karena di-index)
        $programs = Project::whereNotNull('program')->distinct()->pluck('program');
        $branches = Project::whereNotNull('branch')->distinct()->pluck('branch');

        // 2. Gunakan Query Builder dengan Raw SQL Aggregation (Super Cepat!)
        // 2. Gunakan Query Builder dengan Raw SQL Aggregation (Menggunakan tabel 'designators')
        $query = DB::table('lops as l')
            ->join('projects as p', 'l.project_id', '=', 'p.id_project')
            ->leftJoin('boq_items as b', 'l.id_lop', '=', 'b.lop_id')
            ->leftJoin('designators as d', 'b.designator_id', '=', 'd.id_designator') // <-- Perbaikan di baris ini
            ->select([
                'l.id_lop',
                'l.branch',
                'l.sto',
                'l.lop_name',
                'p.program',
                'p.id_project',
                
                // Agregasi KABEL langsung di DB (Menggunakan LOWER agar kebal Case-Sensitive)
                DB::raw("SUM(CASE WHEN LOWER(d.progress_category) = 'kabel' THEN b.quantity_plan ELSE 0 END) as kabel_plan"),
                DB::raw("SUM(CASE WHEN LOWER(d.progress_category) = 'kabel' THEN b.quantity_actual ELSE 0 END) as kabel_actual"),
                
                // Agregasi TIANG langsung di DB
                DB::raw("SUM(CASE WHEN LOWER(d.progress_category) = 'tiang' THEN b.quantity_plan ELSE 0 END) as tiang_plan"),
                DB::raw("SUM(CASE WHEN LOWER(d.progress_category) = 'tiang' THEN b.quantity_actual ELSE 0 END) as tiang_actual"),
            ]);

        // Filter Pencarian Text
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('l.lop_name', 'LIKE', "%{$search}%")
                  ->orWhere('l.sto', 'LIKE', "%{$search}%")
                  ->orWhere('l.branch', 'LIKE', "%{$search}%")
                  ->orWhere('p.program', 'LIKE', "%{$search}%");
            });
        }

        // Filter Dropdown
        if ($request->filled('program')) {
            $query->where('p.program', $request->program);
        }
        if ($request->filled('branch')) {
            $query->where('l.branch', $request->branch);
        }

        // Grouping berdasarkan LOP ID agar agregasi tidak duplikat
        $lopsData = $query->groupBy('l.id_lop', 'l.branch', 'l.sto', 'l.lop_name', 'p.program', 'p.id_project')->get();

        // 3. Tarik data ringkasan progress step project secara massal (untuk donut chart)
        // Agar tidak N+1 memanggil progressSummary() di dalam loop, kita optimasi penarikan project
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
            // Kalkulasi nilai total regional
            $totalKabelPlan += $lop->kabel_plan;
            $totalKabelActual += $lop->kabel_actual;
            $totalTiangPlan += $lop->tiang_plan;
            $totalTiangActual += $lop->tiang_actual;

            // Hitung Persentase per baris
            $persenKabel = $lop->kabel_plan > 0 ? round(($lop->kabel_actual / $lop->kabel_plan) * 100, 2) : 0;
            $persenTiang = $lop->tiang_plan > 0 ? round(($lop->tiang_actual / $lop->tiang_plan) * 100, 2) : 0;

            // Ambil progress dari memory collection (Bukan hit database ulang)
            $projectProgress = 0;
            if (isset($projects[$lop->id_project])) {
                $summary = $projects[$lop->id_project]->progressSummary();
                $projectProgress = $summary['progress'] ?? 0;
            }

            // Klasifikasi Donut Chart
            if ($projectProgress >= 100) { $summaryStatus['selesai']++; }
            elseif ($projectProgress >= 50) { $summaryStatus['sedang']++; }
            elseif ($projectProgress >= 1) { $summaryStatus['rendah']++; }
            else { $summaryStatus['belum']++; }

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

        $totalKabelPersen = $totalKabelPlan > 0 ? round(($totalKabelActual / $totalKabelPlan) * 100, 2) : 0;
        $totalTiangPersen = $totalTiangPlan > 0 ? round(($totalTiangActual / $totalTiangPlan) * 100, 2) : 0;

        return view('admin.dashboard.pm', compact(
            'programs', 'branches', 'totalSegments',
            'totalKabelPlan', 'totalKabelActual', 'totalKabelPersen',
            'totalTiangPlan', 'totalTiangActual', 'totalTiangPersen',
            'summaryStatus', 'tableData'
        ));
    }
}