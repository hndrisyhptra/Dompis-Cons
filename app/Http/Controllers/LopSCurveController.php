<?php

namespace App\Http\Controllers;

use App\Models\Lop;
use App\Models\ProjectActivityLog;
use App\Services\LopSCurveService;
use Illuminate\Contracts\View\View;

class LopSCurveController extends Controller
{
    public function show(Lop $lop, LopSCurveService $curve): View
    {
        $lop->load([
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
            'project.evidences.boqItem',
            'project.lops',
            'permitCategory',
            'surveyRounds',
            'stageHistories',
            'measurementChecks',
            'goliveSubmission',
        ]);

        $normalizedProgram = str_replace([' ', '-', '_'], '', mb_strtoupper(trim((string) $lop->project?->program)));

        abort_if(
            $normalizedProgram === 'PT2',
            404,
            'Kurva-S tahap pertama hanya tersedia untuk project reguler/PT3.'
        );

        $activityLogs = ProjectActivityLog::query()
            ->where('project_id', $lop->project_id)
            ->where('lop_id', $lop->id_lop)
            ->oldest('created_at')
            ->get();

        $curveData = $curve->build($lop, $activityLogs);

        return view('lops.s-curve', compact('activityLogs', 'curveData', 'lop'));
    }
}
