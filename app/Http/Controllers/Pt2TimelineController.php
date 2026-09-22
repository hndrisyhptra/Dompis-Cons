<?php

namespace App\Http\Controllers;

use App\Models\ProjectActivityLog;
use App\Models\Pt2Lop;
use App\Services\Pt2TimelineService;
use Illuminate\Contracts\View\View;

class Pt2TimelineController extends Controller
{
    public function show(Pt2Lop $lop, Pt2TimelineService $timeline): View
    {
        $lop->load([
            'project',
            'assignment.teknisi',
            'assignment.assigner',
            'surveys',
            'evidences.uploader',
            'dismantles',
            'mancores',
        ]);

        // ID PT2 dan reguler berada pada namespace tabel berbeda sehingga
        // nilainya bisa bertabrakan. Suffix `_pt2` menjadi discriminator aman
        // untuk activity log lama yang belum mempunyai kolom project_type.
        $logs = ProjectActivityLog::with(['user', 'targetUser'])
            ->where('project_id', $lop->pt2_project_id)
            ->where('lop_id', $lop->id_pt2_lop)
            ->where('activity_type', 'like', '%pt2%')
            ->orderBy('created_at')
            ->get();

        $events = $timeline->buildEvents($lop, $logs);
        $stageDurations = $timeline->buildStageDurations($lop);
        $summary = $lop->progressSummary();

        return view('pt2.timeline', compact(
            'events',
            'lop',
            'stageDurations',
            'summary',
        ));
    }
}
