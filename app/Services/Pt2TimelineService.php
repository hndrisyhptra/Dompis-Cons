<?php

namespace App\Services;

use App\Models\ProjectActivityLog;
use App\Models\Pt2Evidence;
use App\Models\Pt2Lop;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class Pt2TimelineService
{
    private const STAGES = [
        'inisiasi' => 'Inisiasi',
        'survey' => 'Survey',
        'instalasi' => 'Instalasi',
        'finishing' => 'Finishing',
        'fi_ogp_golive' => 'FI-OGP Golive',
        'golive' => 'Golive',
    ];

    public function buildEvents(Pt2Lop $lop, Collection $logs): Collection
    {
        $events = collect();
        $project = $lop->project;
        $teknisi = $lop->assignment?->teknisi;

        $events->push($this->event(
            $lop->created_at ?? $project?->created_at,
            'project_created_pt2',
            'Project PT 2 Dibuat (PID/BOQ)',
            'PID '.($project?->pid ?? '-').' · LOP '.($lop->lop_name ?? '-').' dimasukkan ke sistem.',
            null,
            'Inisiasi',
        ));

        $hasAssignmentLog = $logs->contains(
            fn (ProjectActivityLog $log) => str_contains($log->activity_type, 'assign')
        );

        if ($lop->assignment && ! $hasAssignmentLog) {
            $events->push($this->event(
                $lop->assignment->created_at,
                'assign_teknisi_pt2',
                'Teknisi Ditugaskan (PT 2)',
                'LOP ditugaskan kepada '.($teknisi?->name ?? 'teknisi').'.',
                $lop->assignment->assigner,
                'Survey',
            ));
        }

        foreach ($lop->surveys->sortBy('created_at') as $survey) {
            $description = 'Mode survey: '.($survey->mode ?: '-').'.';
            if ($survey->kesimpulan) {
                $description .= ' Kesimpulan: '.$survey->kesimpulan;
            }
            if ($survey->has_kendala) {
                $description .= ' Kendala: '.($survey->kendala_note ?: '-');
            }

            $events->push($this->event(
                $survey->created_at,
                'survey_pt2',
                'Survey PT 2 Disimpan',
                $description,
                $teknisi,
                'Survey',
            ));

            if ($survey->updated_at && $survey->created_at && $survey->updated_at->gt($survey->created_at)) {
                $events->push($this->event(
                    $survey->updated_at,
                    'survey_review_pt2',
                    'Status Survey: '.ucfirst((string) $survey->pm_approval_status),
                    $survey->kendala_note,
                    null,
                    'Survey',
                ));
            }
        }

        $evidenceById = $lop->evidences->keyBy('id_pt2_evidence');

        $lop->evidences
            ->sortBy('created_at')
            ->groupBy(fn (Pt2Evidence $evidence) => implode('|', [
                strtolower((string) $evidence->stage),
                (string) $evidence->uploaded_by,
                optional($evidence->created_at)->format('YmdHi'),
            ]))
            ->each(function (Collection $items) use ($events) {
                /** @var Pt2Evidence $first */
                $first = $items->first();
                $stage = $this->stageLabel($first->stage);
                $types = $items->pluck('evidence_type')->filter()->unique()->implode(', ');

                $events->push($this->event(
                    $first->created_at,
                    'upload_evidence_pt2',
                    'Upload Eviden '.$stage.' (PT 2)',
                    $items->count().' file diunggah'.($types !== '' ? ': '.$types : '.'),
                    $first->uploader,
                    $stage,
                    $items->map(fn (Pt2Evidence $evidence) => $this->photo($evidence))->values(),
                ));
            });

        foreach ($logs->sortBy('created_at') as $log) {
            $photo = $log->evidence_id ? $evidenceById->get($log->evidence_id) : null;
            $photos = $photo ? collect([$this->photo($photo)]) : collect();
            $stageLabel = $this->stageLabel($log->stage);

            if ($log->activity_type === 'lop_golive_pt2' && $lop->golive_evidence_path) {
                $stageLabel = 'Golive';
                $photos->push([
                    'path' => $lop->golive_evidence_path,
                    'label' => 'Capture UIM',
                    'status' => 'approved',
                ]);
            }

            $events->push($this->event(
                $log->created_at,
                $log->activity_type,
                $log->title,
                $log->description,
                $log->user,
                $stageLabel,
                $photos,
            ));
        }

        if ($lop->dismantles->isNotEmpty()) {
            $items = $lop->dismantles->sortBy('created_at');
            $description = $items->map(
                fn ($item) => ($item->category ?? 'Item').' · '.($item->item_name ?? '-').' ('.($item->qty ?? 0).')'
            )->implode("\n");

            $events->push($this->event(
                $items->first()->created_at,
                'dismantle_pt2',
                'Data Dismantle Disimpan',
                $description,
                $teknisi,
                'Finishing',
            ));
        }

        if ($lop->mancores->isNotEmpty()) {
            $mancore = $lop->mancores->sortBy('created_at')->first();
            $events->push($this->event(
                $mancore->created_at,
                'mancore_pt2',
                'Data Mancore Disimpan',
                'ODP '.($mancore->odp_label ?? '-').' · ODC '.($mancore->odc_label ?? '-').' · Distribusi '.($mancore->distribusi_core ?? '-').' · Feeder '.($mancore->feeder_core ?? '-').'.',
                $teknisi,
                'FI-OGP Golive',
            ));
        }

        $hasGoliveLog = $logs->contains(fn (ProjectActivityLog $log) => $log->activity_type === 'lop_golive_pt2');

        if (! $hasGoliveLog && ($lop->golive_at || $lop->is_golive || $lop->sdi_approval_status === 'approved')) {
            $photos = collect();
            if ($lop->golive_evidence_path) {
                $photos->push([
                    'path' => $lop->golive_evidence_path,
                    'label' => 'Capture UIM',
                    'status' => 'approved',
                ]);
            }

            $events->push($this->event(
                $lop->golive_at ?? $lop->updated_at,
                'lop_golive_pt2',
                'LOP PT 2 Go-Live',
                'SDI menyelesaikan verifikasi dan mengunggah eviden UIM.',
                $logs->firstWhere('activity_type', 'lop_golive_pt2')?->user,
                'Golive',
                $photos,
            ));
        }

        return $events
            ->filter(fn (array $event) => $event['dt'] !== null)
            ->sortBy('dt')
            ->values();
    }

    public function buildStageDurations(Pt2Lop $lop): Collection
    {
        $milestones = collect($this->milestones($lop))
            ->map(fn ($date) => $this->asDate($date))
            ->all();
        $currentStage = $this->currentStage($lop);
        $stageCodes = array_keys(self::STAGES);

        return collect($stageCodes)->map(function (string $code, int $index) use ($milestones, $currentStage, $stageCodes) {
            $enteredAt = $milestones[$code] ?? null;
            $isCurrent = $currentStage === $code;
            $completedAt = null;

            if ($enteredAt && ! $isCurrent) {
                foreach (array_slice($stageCodes, $index + 1) as $nextCode) {
                    if ($milestones[$nextCode] ?? null) {
                        $completedAt = $milestones[$nextCode];
                        break;
                    }
                }
            }

            $durationSeconds = null;
            if ($enteredAt && ($completedAt || $isCurrent)) {
                $durationSeconds = (int) $enteredAt->diffInSeconds($completedAt ?? now());
            }

            return [
                'code' => $code,
                'label' => self::STAGES[$code],
                'entered_at' => $enteredAt,
                'completed_at' => $completedAt,
                'is_current' => $isCurrent,
                'duration_seconds' => $durationSeconds,
                'visited' => $enteredAt !== null,
            ];
        });
    }

    private function milestones(Pt2Lop $lop): array
    {
        $surveyDates = collect([
            $lop->assignment?->created_at,
            $lop->surveys->min('created_at'),
            $lop->evidences->whereIn('stage', ['persiapan', 'survey'])->min('created_at'),
        ])->filter();

        $finishingDates = collect([
            $lop->evidences->whereIn('stage', ['finishing', 'finish', 'redaman', 'dismantle'])->min('created_at'),
            $lop->dismantles->min('created_at'),
        ])->filter();

        return [
            'inisiasi' => $lop->created_at ?? $lop->project?->created_at,
            'survey' => $surveyDates->sort()->first(),
            'instalasi' => $lop->evidences->whereIn('stage', ['instalasi', 'progress'])->min('created_at'),
            'finishing' => $finishingDates->sort()->first(),
            'fi_ogp_golive' => $lop->mancores->min('created_at'),
            'golive' => $lop->golive_at,
        ];
    }

    private function currentStage(Pt2Lop $lop): string
    {
        if ($lop->is_golive || $lop->sdi_approval_status === 'approved') {
            return 'golive';
        }

        return match (strtolower((string) $lop->status_progress)) {
            'preparation', 'persiapan' => 'inisiasi',
            'progress' => 'instalasi',
            'finish', 'redaman', 'dismantle' => 'finishing',
            'mancore', 'done', 'complete' => 'fi_ogp_golive',
            default => strtolower((string) $lop->status_progress) ?: 'inisiasi',
        };
    }

    private function event(
        ?CarbonInterface $date,
        string $type,
        string $title,
        ?string $description,
        mixed $user,
        ?string $stage,
        ?Collection $photos = null,
    ): array {
        return [
            'dt' => $date,
            'type' => $type,
            'title' => $title,
            'desc' => $description,
            'user' => $user,
            'stage' => $stage,
            'photos' => $photos ?? collect(),
        ];
    }

    private function photo(Pt2Evidence $evidence): array
    {
        return [
            'path' => $evidence->file_path,
            'label' => $evidence->evidence_type ?: ($evidence->stage ?: 'Eviden'),
            'status' => $evidence->status,
        ];
    }

    private function stageLabel(?string $stage): ?string
    {
        if (! $stage) {
            return null;
        }

        return match (strtolower($stage)) {
            'preparation', 'persiapan', 'survey' => 'Survey',
            'progress', 'instalasi' => 'Instalasi',
            'finish', 'redaman', 'dismantle', 'finishing' => 'Finishing',
            'mancore', 'fi_ogp_golive' => 'FI-OGP Golive',
            'golive' => 'Golive',
            default => ucfirst(str_replace('_', ' ', $stage)),
        };
    }

    private function asDate(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $value);
    }
}
