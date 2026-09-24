<?php

namespace App\Services;

use App\Models\BoqItem;
use App\Models\Lop;
use App\Models\ProjectActivityLog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class LopSCurveService
{
    private const LONG_PERMIT_CATEGORIES = [
        'PERIZINAN PU NASIONAL',
        'PERIZINAN PU PROVINSI',
        'PERIZINAN PU KABUPATEN',
        'PERIZINAN PU KOTA',
        'PERIZINAN INSTANSI',
    ];

    private const MATARAM_4_DAY_STOS = ['GER', 'MBG', 'MTR', 'PRY', 'SEL', 'SGG', 'SWE'];

    private const MATARAM_14_DAY_STOS = ['ALA', 'BIM', 'DMP', 'EMP', 'KMP', 'MLK', 'SAP', 'SBW', 'SIL', 'TET', 'TLW'];

    private const KUPANG_4_DAY_STOS = ['ATB', 'BAA', 'BEN', 'KEF', 'KPN', 'NKN', 'OSP', 'SOE', 'TNA'];

    private const KUPANG_OUTER_14_DAY_STOS = ['SEB', 'WGP', 'WKB', 'BJW', 'END', 'KLH', 'LBO', 'LRT', 'LWB', 'MAU', 'MMR', 'REO', 'RTE', 'WWR'];

    private const THREE_DAY_BRANCHES = [
        'SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG',
        'YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG',
        'DENPASAR',
    ];

    private const PRODUCTIVITY = [
        'KABEL' => 1000,
        'TIANG' => 10,
        'GALIAN' => 100,
    ];

    public function __construct(private SurveyPreparationService $surveyPreparation) {}

    public function build(Lop $lop, Collection $activityLogs): array
    {
        $warnings = collect();
        $startDate = $this->asDate($lop->start_tgl)?->startOfDay();
        $permitCategory = mb_strtoupper(trim((string) $lop->permitCategory?->name));
        $delivery = $this->deliveryRule($lop->branch, $lop->sto);

        if (! $startDate) {
            $warnings->push('Start Date (start_tgl) belum diisi. Kurva target tidak dapat dihitung.');
        }

        if ($permitCategory === '') {
            $warnings->push('Kategori Perizinan belum dipilih. Durasi Perizinan belum dapat ditentukan.');
        }

        if ($delivery['days'] === null) {
            $warnings->push('Kombinasi Branch/STO belum mempunyai aturan Material Delivery.');
        }

        $permitDays = $permitCategory === ''
            ? null
            : (in_array($permitCategory, self::LONG_PERMIT_CATEGORIES, true) ? 21 : 7);

        $boq = $this->boqSummary($lop);
        $installationDays = array_sum($boq['days']);

        if ($boq['groups']->isEmpty()) {
            $warnings->push('BOQ belum memiliki designator kategori KABEL, TIANG, atau GALIAN. Durasi Instalasi menjadi 0 hari.');
        }

        if (($lop->project?->lops->count() ?? 0) > 1
            && ($lop->project?->evidences ?? collect())->whereNull('boq_item_id')->isNotEmpty()) {
            $warnings->push('Project memiliki beberapa LOP. Eviden lama tanpa referensi BOQ hanya dihitung jika activity log-nya menunjuk LOP ini.');
        }

        $ready = $startDate !== null && $permitDays !== null && $delivery['days'] !== null;
        $surveyDays = 7;
        $goliveDays = 3;
        $totalDays = $ready
            ? $surveyDays + $permitDays + $delivery['days'] + $installationDays + $goliveDays
            : null;

        $schedule = $ready
            ? $this->targetSchedule(
                $startDate,
                $surveyDays,
                $permitDays,
                $delivery['days'],
                $installationDays,
                $goliveDays,
                $this->asDate($lop->goliveSubmission?->fi_completed_at),
            )
            : $this->emptySchedule();

        $realization = $ready
            ? $this->realizationSeries($lop, $activityLogs, $boq, $schedule)
            : collect();

        return [
            'ready' => $ready,
            'warnings' => $warnings->values(),
            'inputs' => [
                'start_date' => $startDate,
                'permit_category' => $permitCategory ?: null,
                'permit_days' => $permitDays,
                'delivery_days' => $delivery['days'],
                'delivery_rule' => $delivery['rule'],
                'boq_source' => $boq['source'],
                'volumes' => $boq['volumes'],
                'installation_days' => $installationDays,
                'total_days' => $totalDays,
            ],
            'target' => $schedule,
            'realization' => $realization,
            'milestones' => $this->milestones($lop, $activityLogs, $schedule),
        ];
    }

    /** @return array{days: ?int, rule: string} */
    public function deliveryRule(?string $branch, ?string $sto): array
    {
        $branch = mb_strtoupper(trim((string) $branch));
        $sto = mb_strtoupper(trim((string) $sto));

        if (in_array($branch, self::THREE_DAY_BRANCHES, true)) {
            return ['days' => 3, 'rule' => 'Region Jatim/Jateng/Bali · seluruh STO'];
        }

        if ($branch === 'MATARAM' && in_array($sto, self::MATARAM_4_DAY_STOS, true)) {
            return ['days' => 4, 'rule' => 'Branch Mataram · STO inner'];
        }

        if ($branch === 'MATARAM' && in_array($sto, self::MATARAM_14_DAY_STOS, true)) {
            return ['days' => 14, 'rule' => 'Branch Mataram · STO outer'];
        }

        if ($branch === 'KUPANG' && in_array($sto, self::KUPANG_4_DAY_STOS, true)) {
            return ['days' => 4, 'rule' => 'Branch Kupang · STO inner'];
        }

        if (in_array($branch, ['KUPANG', 'FLORES'], true) && in_array($sto, self::KUPANG_OUTER_14_DAY_STOS, true)) {
            return ['days' => 14, 'rule' => 'Kupang Outer/Flores · STO outer'];
        }

        return ['days' => null, 'rule' => 'Belum dipetakan'];
    }

    private function boqSummary(Lop $lop): array
    {
        $items = $lop->boqItems ?? collect();
        $hasCompletedSurvey = $lop->surveyRounds
            ->contains(fn ($round) => $round->status === 'completed');
        $source = $hasCompletedSurvey ? 'BOQ Survey' : 'BOQ Plan';
        $grouped = $this->surveyPreparation->groupBoqItems($items);
        $itemsById = $items->keyBy('id_boq');

        $groups = $grouped
            ->map(function (array $group) use ($hasCompletedSurvey, $itemsById) {
                $rawItems = collect($group['item_ids'])
                    ->map(fn ($id) => $itemsById->get($id))
                    ->filter();
                $representative = $rawItems
                    ->filter(function (BoqItem $item) {
                        $designator = $item->designatorData ?? $item->designatorDataByCode;

                        return in_array(
                            mb_strtoupper(trim((string) $designator?->progress_category)),
                            array_keys(self::PRODUCTIVITY),
                            true
                        );
                    })
                    ->sortBy(fn (BoqItem $item) => $this->isMaterial($item) ? 0 : 1)
                    ->first()
                    ?? $rawItems->sortBy(fn (BoqItem $item) => $this->isMaterial($item) ? 0 : 1)->first();
                $designator = $representative?->designatorData ?? $representative?->designatorDataByCode;
                $category = mb_strtoupper(trim((string) $designator?->progress_category));
                $volume = $hasCompletedSurvey
                    ? (float) ($group['quantity_survey'] ?? 0)
                    : (float) ($group['quantity_plan'] ?? 0);

                return [
                    'key' => $group['key'],
                    'item_ids' => $group['item_ids'],
                    'category' => $category,
                    'volume' => max(0, $volume),
                    'designator' => $group['designator'],
                ];
            })
            ->whereIn('category', array_keys(self::PRODUCTIVITY))
            ->values();

        $volumes = collect(array_keys(self::PRODUCTIVITY))
            ->mapWithKeys(fn (string $category) => [
                $category => (float) $groups->where('category', $category)->sum('volume'),
            ])->all();
        $days = collect(self::PRODUCTIVITY)
            ->mapWithKeys(fn (int $productivity, string $category) => [
                $category => (int) round($volumes[$category] / $productivity, 0, PHP_ROUND_HALF_UP),
            ])->all();

        return compact('source', 'groups', 'volumes', 'days');
    }

    private function targetSchedule(
        CarbonImmutable $start,
        int $surveyDays,
        int $permitDays,
        int $deliveryDays,
        int $installationDays,
        int $goliveDays,
        ?CarbonImmutable $fiCompletedAt,
    ): array {
        $total = $surveyDays + $permitDays + $deliveryDays + $installationDays + $goliveDays;
        $surveyEnd = $start->addDays($surveyDays);
        $permitEnd = $surveyEnd->addDays($permitDays);
        $preparationEnd = $permitEnd->addDays($deliveryDays);
        $plannedFiEnd = $preparationEnd->addDays($installationDays);
        $baseProgress = $this->percent($total - $goliveDays, $total);
        $goliveStart = $fiCompletedAt?->startOfDay();
        $goliveTarget = $goliveStart?->addDays($goliveDays);

        $targetSeries = collect([
            $this->point($start, 0, 'Mulai'),
            $this->point($surveyEnd, $this->percent($surveyDays, $total), 'Target Survey'),
            $this->point($permitEnd, $this->percent($surveyDays + $permitDays, $total), 'Target Perizinan'),
            $this->point($preparationEnd, $this->percent($surveyDays + $permitDays + $deliveryDays, $total), 'Target Persiapan Instalasi'),
        ]);

        if ($goliveStart) {
            $fiTargetPoint = $goliveStart->lessThan($plannedFiEnd) ? $goliveStart : $plannedFiEnd;
            $targetSeries->push($this->point($fiTargetPoint, $baseProgress, 'Target Instalasi s.d. FI-OGP'));

            if ($goliveStart->greaterThan($plannedFiEnd)) {
                $targetSeries->push($this->point($goliveStart, $baseProgress, 'Syarat FI-OGP lengkap'));
            }

            $targetSeries->push($this->point($goliveTarget, 100, 'Target Golive'));
        } else {
            $targetSeries->push($this->point($plannedFiEnd, $baseProgress, 'Target Instalasi s.d. FI-OGP'));
        }

        return [
            'series' => $this->normalizeSeries($targetSeries),
            'start_date' => $start,
            'survey_end' => $surveyEnd,
            'permit_end' => $permitEnd,
            'preparation_end' => $preparationEnd,
            'planned_fi_end' => $plannedFiEnd,
            'fi_completed_at' => $goliveStart,
            'golive_target' => $goliveTarget,
            'base_progress' => $baseProgress,
            'total_days' => $total,
            'durations' => [
                'survey' => $surveyDays,
                'perizinan' => $permitDays,
                'persiapan_instalasi' => $deliveryDays,
                'instalasi_terpadu' => $installationDays,
                'golive' => $goliveDays,
            ],
        ];
    }

    private function realizationSeries(Lop $lop, Collection $logs, array $boq, array $schedule): Collection
    {
        $total = $schedule['total_days'];
        $durations = $schedule['durations'];
        $events = collect();
        $start = $schedule['start_date'];

        $this->addIncrement($events, $this->surveyCompletedAt($lop, $logs), $durations['survey'], 'Survey selesai');
        $this->addIncrement($events, $this->asDate($lop->perizinan_completed_at), $durations['perizinan'], 'Perizinan selesai');

        $deliveryEvidence = $this->scopedProjectEvidences($lop, $logs)
            ->where('stage', 'material_delivery')
            ->sortBy('created_at')
            ->first();
        $deliveryHalf = $durations['persiapan_instalasi'] / 2;
        $this->addIncrement($events, $this->asDate($deliveryEvidence?->created_at), $deliveryHalf, 'Material Delivery diunggah');
        $this->addIncrement($events, $this->stageCompletedAt($lop, $logs, 'persiapan_instalasi', 'instalasi'), $deliveryHalf, 'Persiapan Instalasi selesai');

        $installationQuarter = $durations['instalasi_terpadu'] / 4;
        $this->addGroupedEvidenceEvents($events, $lop, $boq['groups'], 'instalasi', $installationQuarter);
        $this->addMeasurementEvents($events, $lop, $logs, $installationQuarter);
        $this->addFinishingEvents($events, $lop, $logs, $boq['groups'], $installationQuarter);
        $this->addFiEvents($events, $lop, $logs, $installationQuarter);

        $goliveAt = $this->asDate($lop->golive_at);
        if ($goliveAt) {
            $events->push([
                'date' => $goliveAt,
                'absolute' => 100.0,
                'label' => 'Golive',
            ]);
        }

        $progress = 0.0;
        $series = collect([$this->point($start, 0, 'Mulai')]);
        $events->sortBy('date')->each(function (array $event) use (&$progress, $series, $start, $total) {
            $progress = isset($event['absolute'])
                ? (float) $event['absolute']
                : min(100.0, $progress + $this->percent((float) $event['increment'], $total));
            $date = $event['date']->lessThan($start) ? $start : $event['date'];
            $series->push($this->point($date, $progress, $event['label']));
        });

        return $this->normalizeSeries($series);
    }

    private function addGroupedEvidenceEvents(Collection $events, Lop $lop, Collection $groups, string $stage, float $weight): void
    {
        if ($groups->isEmpty() || $weight <= 0) {
            return;
        }

        $increment = $weight / $groups->count();
        $evidences = $lop->project?->evidences ?? collect();

        foreach ($groups as $group) {
            $evidence = $evidences
                ->where('stage', $stage)
                ->where('status', 'approved')
                ->whereIn('boq_item_id', $group['item_ids'])
                ->sortBy('updated_at')
                ->last();
            $this->addIncrement($events, $this->asDate($evidence?->updated_at ?? $evidence?->created_at), $increment, 'Progress '.$stage);
        }
    }

    private function addMeasurementEvents(Collection $events, Lop $lop, Collection $logs, float $weight): void
    {
        if ($weight <= 0) {
            return;
        }

        $keys = ['otdr', 'file_sor', 'opm', 'kedalaman', 'eviden_lainnya'];
        $aliases = ['file_sor' => ['file_sor', 'otdr_sor'], 'eviden_lainnya' => ['eviden_lainnya', 'lainnya']];
        $increment = $weight / count($keys);
        $evidences = $this->scopedProjectEvidences($lop, $logs);

        foreach ($keys as $key) {
            $check = $lop->measurementChecks->firstWhere('item_key', $key);
            $evidence = $evidences
                ->where('stage', 'pengukuran')
                ->where('status', 'approved')
                ->whereIn('evidence_type', $aliases[$key] ?? [$key])
                ->sortBy('updated_at')
                ->last();
            $date = $check?->is_not_applicable
                ? $this->asDate($check->updated_at)
                : $this->asDate($evidence?->updated_at ?? $evidence?->created_at);
            $this->addIncrement($events, $date, $increment, 'Pengukuran: '.$key);
        }
    }

    private function addFinishingEvents(Collection $events, Lop $lop, Collection $logs, Collection $groups, float $weight): void
    {
        if ($weight <= 0) {
            return;
        }

        $required = $groups->filter(function (array $group) use ($lop) {
            $items = $lop->boqItems->whereIn('id_boq', $group['item_ids']);

            return $items->contains(function (BoqItem $item) {
                $designator = $item->designatorData ?? $item->designatorDataByCode;

                return (bool) $designator?->requires_finishing_evidence;
            });
        })->values();

        if ($required->isNotEmpty()) {
            $this->addGroupedEvidenceEvents($events, $lop, $required, 'finishing', $weight);

            return;
        }

        $evidence = $this->scopedProjectEvidences($lop, $logs)
            ->where('stage', 'finishing')
            ->where('status', 'approved')
            ->sortBy('updated_at')
            ->last();
        $this->addIncrement($events, $this->asDate($evidence?->updated_at ?? $evidence?->created_at), $weight, 'Finishing selesai');
    }

    private function addFiEvents(Collection $events, Lop $lop, Collection $logs, float $weight): void
    {
        if ($weight <= 0) {
            return;
        }

        $categories = collect();
        $logs->whereIn('activity_type', ['golive_submission_draft_upload', 'golive_submission_upload'])
            ->sortBy('created_at')
            ->each(function ($log) use ($categories, $events, $weight) {
                foreach ((array) data_get($log->meta, 'uploaded_categories', []) as $category) {
                    if ($categories->contains($category)) {
                        continue;
                    }
                    $categories->push($category);
                    $this->addIncrement($events, $this->asDate($log->created_at), $weight / 4, 'FI-OGP: '.$category);
                }
            });

        if ($categories->isEmpty() && $lop->goliveSubmission?->fi_completed_at) {
            $this->addIncrement($events, $this->asDate($lop->goliveSubmission->fi_completed_at), $weight, 'Syarat FI-OGP lengkap');
        }
    }

    private function milestones(Lop $lop, Collection $logs, array $schedule): Collection
    {
        if (! $schedule['start_date']) {
            return collect();
        }

        return collect([
            ['label' => 'Survey', 'target' => $schedule['survey_end'], 'actual' => $this->surveyCompletedAt($lop, $logs)],
            ['label' => 'Perizinan', 'target' => $schedule['permit_end'], 'actual' => $this->asDate($lop->perizinan_completed_at)],
            ['label' => 'Persiapan Instalasi', 'target' => $schedule['preparation_end'], 'actual' => $this->stageCompletedAt($lop, $logs, 'persiapan_instalasi', 'instalasi')],
            ['label' => 'Instalasi s.d. FI-OGP', 'target' => $schedule['planned_fi_end'], 'actual' => $this->asDate($lop->goliveSubmission?->fi_completed_at)],
            ['label' => 'Golive', 'target' => $schedule['golive_target'], 'actual' => $this->asDate($lop->golive_at)],
        ])->map(function (array $milestone) {
            $variance = $milestone['actual'] && $milestone['target']
                ? $milestone['target']->diffInDays($milestone['actual'], false)
                : null;

            return $milestone + ['variance_days' => $variance];
        });
    }

    private function surveyCompletedAt(Lop $lop, Collection $logs): ?CarbonImmutable
    {
        $roundDate = $lop->surveyRounds
            ->where('status', 'completed')
            ->sortByDesc('round_number')
            ->first()?->finished_at;

        return $this->asDate($roundDate)
            ?? $this->asDate($logs->whereIn('activity_type', ['survey_finalized', 'survey_redesign_approved'])->sortBy('created_at')->last()?->created_at)
            ?? $this->stageCompletedAt($lop, $logs, 'survey', 'perizinan');
    }

    private function stageCompletedAt(Lop $lop, Collection $logs, string $stage, string $nextStage): ?CarbonImmutable
    {
        $historyDate = $lop->stageHistories
            ->where('stage_code', $stage)
            ->sortByDesc('completed_at')
            ->first()?->completed_at;
        $logDate = $logs
            ->filter(fn (ProjectActivityLog $log) => $log->status_before === $stage || $log->status_after === $nextStage)
            ->sortBy('created_at')
            ->last()?->created_at;

        return $this->asDate($historyDate) ?? $this->asDate($logDate);
    }

    private function isMaterial(BoqItem $item): bool
    {
        $designator = $item->designatorData ?? $item->designatorDataByCode;

        return mb_strtolower(trim((string) $designator?->type)) === 'material'
            || str_starts_with(mb_strtoupper(trim((string) $item->designator)), 'M-');
    }

    private function scopedProjectEvidences(Lop $lop, Collection $logs): Collection
    {
        $project = $lop->project;
        $evidences = $project?->evidences ?? collect();
        $isSingleLopProject = ($project?->lops->count() ?? 0) <= 1;
        $loggedEvidenceIds = $logs->pluck('evidence_id')->filter()->map(fn ($id) => (int) $id)->flip();

        return $evidences->filter(function ($evidence) use ($isSingleLopProject, $loggedEvidenceIds, $lop) {
            if ((int) $evidence->boqItem?->lop_id === (int) $lop->id_lop) {
                return true;
            }

            if ($loggedEvidenceIds->has((int) $evidence->id_evidence)) {
                return true;
            }

            return $isSingleLopProject && $evidence->boq_item_id === null;
        })->values();
    }

    private function addIncrement(Collection $events, ?CarbonImmutable $date, float $increment, string $label): void
    {
        if (! $date || $increment <= 0) {
            return;
        }

        $events->push(compact('date', 'increment', 'label'));
    }

    private function normalizeSeries(Collection $series): Collection
    {
        return $series
            ->sortBy('date')
            ->groupBy(fn (array $point) => $point['date']->format('Y-m-d'))
            ->map(function (Collection $points) {
                $last = $points->last();

                return [
                    'date' => $last['date']->format('Y-m-d'),
                    'progress' => round((float) $points->max('progress'), 2),
                    'label' => $points->pluck('label')->filter()->unique()->implode(' · '),
                ];
            })
            ->values();
    }

    private function point(CarbonImmutable $date, float $progress, string $label): array
    {
        return compact('date', 'progress', 'label');
    }

    private function percent(float $part, float $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 4) : 0.0;
    }

    private function asDate(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value);
        }

        return filled($value) ? CarbonImmutable::parse((string) $value) : null;
    }

    private function emptySchedule(): array
    {
        return [
            'series' => collect(),
            'start_date' => null,
            'survey_end' => null,
            'permit_end' => null,
            'preparation_end' => null,
            'planned_fi_end' => null,
            'fi_completed_at' => null,
            'golive_target' => null,
            'base_progress' => null,
            'total_days' => null,
            'durations' => [],
        ];
    }
}
