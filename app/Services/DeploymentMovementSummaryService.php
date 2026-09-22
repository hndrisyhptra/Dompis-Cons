<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeploymentMovementSummaryService
{
    private const NON_OPERATIONAL_ACTIVITY_TYPES = [
        'sync_legacy_status_progress',
        'golive_submission_delete',
    ];

    /**
     * Ringkasan pergerakan harian LOP PT3/Reguler per Branch.
     *
     * Sumber pergerakan adalah project_activity_logs. Log PT2 sengaja
     * dikeluarkan karena beberapa aktivitas lapangan PT2 belum mencatat actor
     * secara konsisten; menampilkannya di sini akan membuat kolom "oleh siapa"
     * terlihat meyakinkan padahal tidak lengkap.
     *
     * @param  array<string, string>  $branchToRegion
     * @return array<string, mixed>
     */
    public function build(CarbonImmutable $date, array $branchToRegion): array
    {
        $date = $date->startOfDay();
        $end = $date->addDay();
        $branchExpression = "UPPER(TRIM(COALESCE(NULLIF(l.branch, ''), NULLIF(p.branch, ''), 'TANPA BRANCH')))";

        $lopScope = DB::table('lops as l')
            ->leftJoin('projects as p', 'p.id_project', '=', 'l.project_id')
            ->selectRaw("{$branchExpression} as branch");

        // Grouping dilakukan di query luar agar tetap kompatibel dengan
        // MySQL/MariaDB yang mengaktifkan ONLY_FULL_GROUP_BY.
        $lopTotals = DB::query()
            ->fromSub($lopScope, 'lop_scope')
            ->select('branch')
            ->selectRaw('COUNT(*) as total_lops')
            ->groupBy('branch')
            ->get();

        $allLopsByBranch = DB::table('lops as l')
            ->leftJoin('projects as p', 'p.id_project', '=', 'l.project_id')
            ->leftJoin('project_stages as ps', 'ps.code', '=', 'l.status_progress')
            ->select([
                'l.id_lop',
                'l.project_id',
                'l.pid_sap',
                'l.lop_name',
                'l.sto',
                'l.status_progress',
                'p.project_name',
                'p.program',
                'ps.label as status_label',
            ])
            ->selectRaw("{$branchExpression} as branch")
            ->get()
            ->groupBy(fn ($row) => strtoupper(trim((string) $row->branch)));

        $activityScope = $this->regularActivityQuery()
            ->where('pal.created_at', '<', $end->toDateTimeString())
            ->selectRaw("{$branchExpression} as branch, pal.created_at");

        $lastActivities = DB::query()
            ->fromSub($activityScope, 'activity_scope')
            ->select('branch')
            ->selectRaw('MAX(created_at) as last_activity_at')
            ->groupBy('branch')
            ->pluck('last_activity_at', 'branch');

        $dailyLogs = $this->regularActivityQuery()
            ->leftJoin('users as u', 'u.id_user', '=', 'pal.user_id')
            ->leftJoin('project_stages as ps', 'ps.code', '=', 'l.status_progress')
            ->where('pal.created_at', '>=', $date->toDateTimeString())
            ->where('pal.created_at', '<', $end->toDateTimeString())
            ->orderByDesc('pal.created_at')
            ->get([
                'pal.id_project_activity',
                'pal.project_id',
                'pal.lop_id',
                'pal.user_id',
                'pal.activity_type',
                'pal.title',
                'pal.description',
                'pal.stage',
                'pal.status_before',
                'pal.status_after',
                'pal.meta',
                'pal.created_at',
                'l.lop_name',
                'l.pid_sap',
                'l.branch as lop_branch',
                'l.sto',
                'l.status_progress',
                'p.project_name',
                'p.program',
                'p.branch as project_branch',
                'u.name as actor_name',
                'u.username as actor_username',
                'ps.label as status_label',
            ]);

        $stageDefinitions = $this->stageDefinitions();
        $issueStages = $this->issueStageMap($dailyLogs);

        $dailyLogs->each(function ($log) use ($issueStages, $stageDefinitions): void {
            $stageCode = $this->activityStageCode($log, $issueStages, $stageDefinitions);
            $stage = $stageDefinitions->get($stageCode);

            $log->activity_stage_code = $stageCode;
            $log->activity_stage_label = $stage['label'] ?? 'Aktivitas Lainnya';
        });

        $logsByBranch = $dailyLogs->groupBy(fn ($row) => $this->branchName($row));
        $branches = $lopTotals
            ->map(function ($row) use ($allLopsByBranch, $branchToRegion, $date, $lastActivities, $logsByBranch): array {
                $branch = strtoupper(trim((string) $row->branch));
                $logs = $logsByBranch->get($branch, collect());
                $lopDetails = $this->lopDetails($logs);
                $allLops = $this->lopPositionDetails($allLopsByBranch->get($branch, collect()), $lopDetails);
                $lastActivity = $lastActivities->get($branch);
                $lastActivityAt = $lastActivity
                    ? CarbonImmutable::parse($lastActivity, config('app.timezone'))
                    : null;
                $totalLops = (int) $row->total_lops;
                $movedLops = $lopDetails->count();
                $actors = $logs
                    ->filter(fn ($log) => $log->user_id !== null)
                    ->unique('user_id')
                    ->count();

                return [
                    'region' => $branchToRegion[$branch] ?? 'LAINNYA',
                    'branch' => $branch,
                    'total_lops' => $totalLops,
                    'moved_lops' => $movedLops,
                    'not_moved_lops' => max(0, $totalLops - $movedLops),
                    'movement_percent' => $totalLops > 0
                        ? round(($movedLops / $totalLops) * 100, 1)
                        : 0,
                    'activity_count' => $logs->count(),
                    'actor_count' => $actors,
                    'movement_status' => $movedLops > 0 ? 'active' : 'inactive',
                    'last_activity_at' => $lastActivityAt?->toIso8601String(),
                    'last_activity_label' => $lastActivityAt
                        ? $lastActivityAt->locale('id')->translatedFormat('d M Y H:i')
                        : 'Belum ada aktivitas tercatat',
                    'inactive_days' => $lastActivityAt
                        ? (int) $lastActivityAt->startOfDay()->diffInDays($date)
                        : null,
                    'lops' => $lopDetails->values()->all(),
                    'all_lops' => $allLops->all(),
                ];
            })
            // Prioritaskan area tanpa pergerakan, lalu nama Region/Branch.
            ->sortBy(fn (array $row) => sprintf(
                '%d|%s|%s',
                $row['movement_status'] === 'inactive' ? 0 : 1,
                $row['region'],
                $row['branch'],
            ))
            ->values();

        $activeBranches = $branches->where('movement_status', 'active')->count();
        $latestRecordedActivity = $lastActivities
            ->filter()
            ->map(fn ($value) => CarbonImmutable::parse($value, config('app.timezone')))
            ->sortDesc()
            ->first();

        return [
            'date' => $date->toDateString(),
            'date_label' => $date->locale('id')->translatedFormat('l, d F Y'),
            'is_today' => $date->isToday(),
            'previous_date' => $date->subDay()->toDateString(),
            'next_date' => $date->isToday() ? null : $date->addDay()->toDateString(),
            'scope' => 'PT 3 / Reguler',
            'kpis' => [
                'total_branches' => $branches->count(),
                'active_branches' => $activeBranches,
                'inactive_branches' => $branches->count() - $activeBranches,
                'moved_lops' => $dailyLogs->pluck('lop_id')->unique()->count(),
                'activity_count' => $dailyLogs->count(),
                'active_actors' => $dailyLogs->filter(fn ($log) => $log->user_id !== null)->pluck('user_id')->unique()->count(),
            ],
            'latest_recorded_activity_label' => $latestRecordedActivity
                ? $latestRecordedActivity->locale('id')->translatedFormat('d M Y H:i')
                : 'Belum ada aktivitas tercatat',
            'branches' => $branches->all(),
            'stage_groups' => $this->stageGroups($dailyLogs, $stageDefinitions),
            'data_quality' => [
                'activities_without_actor' => $dailyLogs->whereNull('user_id')->count(),
                'pt2_included' => false,
            ],
        ];
    }

    private function regularActivityQuery(): Builder
    {
        return DB::table('project_activity_logs as pal')
            ->join('lops as l', 'l.id_lop', '=', 'pal.lop_id')
            ->leftJoin('projects as p', 'p.id_project', '=', 'l.project_id')
            // Hindari log PT2 yang memakai tabel activity generik dengan ID
            // numerik yang bisa kebetulan sama dengan ID LOP reguler.
            ->whereRaw("pal.activity_type NOT REGEXP '_pt2$'")
            ->whereNotIn('pal.activity_type', [
                'lop_golive_pt2',
                'approve_evidence_pt2',
                'reject_evidence_pt2',
            ])
            // Log maintenance/backfill bukan pekerjaan harian lapangan.
            ->whereNotIn('pal.activity_type', self::NON_OPERATIONAL_ACTIVITY_TYPES)
            // Jika project_id dicatat, pasangan Project-LOP harus konsisten.
            ->where(function ($query) {
                $query->whereNull('pal.project_id')
                    ->orWhereColumn('pal.project_id', 'l.project_id');
            });
    }

    private function branchName(object $row): string
    {
        $branch = $row->lop_branch ?: $row->project_branch ?: 'TANPA BRANCH';

        return strtoupper(trim((string) $branch));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function lopDetails(Collection $logs): Collection
    {
        return $logs
            ->groupBy('lop_id')
            ->map(function (Collection $lopLogs): array {
                $latest = $lopLogs->sortByDesc('created_at')->first();
                $actors = $lopLogs
                    ->map(fn ($log) => [
                        'id' => $log->user_id,
                        'name' => $this->actorName($log),
                        'username' => $log->actor_username,
                    ])
                    ->unique(fn (array $actor) => $actor['id'] ?? 'system')
                    ->values();

                $activities = $lopLogs
                    ->sortByDesc('created_at')
                    ->map(fn ($log) => [
                        'id' => (int) $log->id_project_activity,
                        'time' => CarbonImmutable::parse($log->created_at, config('app.timezone'))->format('H:i'),
                        'title' => $log->title,
                        'description' => $log->description,
                        'type' => $log->activity_type,
                        'category' => $this->activityCategory($log->activity_type),
                        'stage_code' => $log->activity_stage_code ?? 'activity_other',
                        'stage_label' => $log->activity_stage_label ?? 'Aktivitas Lainnya',
                        'actor' => $this->actorName($log),
                    ])
                    ->values();

                return [
                    'lop_id' => (int) $latest->lop_id,
                    'project_id' => $latest->project_id ? (int) $latest->project_id : null,
                    'pid_sap' => $latest->pid_sap ?: '-',
                    'lop_name' => $latest->lop_name ?: '-',
                    'project_name' => $latest->project_name ?: '-',
                    'program' => $latest->program ?: '-',
                    'sto' => $latest->sto ?: '-',
                    'status_progress' => $latest->status_progress ?: '-',
                    'status_label' => $latest->status_label ?: $latest->status_progress ?: '-',
                    'activity_count' => $lopLogs->count(),
                    'last_activity_time' => CarbonImmutable::parse($latest->created_at, config('app.timezone'))->format('H:i'),
                    'last_activity_title' => $latest->title,
                    'last_actor' => $this->actorName($latest),
                    'actors' => $actors->all(),
                    'activities' => $activities->all(),
                ];
            })
            ->sortByDesc('last_activity_time')
            ->values();
    }

    /**
     * Seluruh LOP untuk matrix posisi. LOP yang bergerak memakai rincian log
     * harian lengkap; LOP tanpa aktivitas tetap dikirim dengan identitas dan
     * posisi terkini agar angka "tidak bergerak" dapat dibuka oleh pengguna.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function lopPositionDetails(Collection $allLops, Collection $movedLops): Collection
    {
        $movedById = $movedLops->keyBy('lop_id');

        return $allLops
            ->map(function ($lop) use ($movedById): array {
                $lopId = (int) $lop->id_lop;
                $moved = $movedById->get($lopId);

                if ($moved) {
                    return $moved + ['movement_status' => 'active'];
                }

                return [
                    'lop_id' => $lopId,
                    'project_id' => $lop->project_id ? (int) $lop->project_id : null,
                    'pid_sap' => $lop->pid_sap ?: '-',
                    'lop_name' => $lop->lop_name ?: '-',
                    'project_name' => $lop->project_name ?: '-',
                    'program' => $lop->program ?: '-',
                    'sto' => $lop->sto ?: '-',
                    'status_progress' => $lop->status_progress ?: '-',
                    'status_label' => $lop->status_label ?: $lop->status_progress ?: '-',
                    'movement_status' => 'inactive',
                    'activity_count' => 0,
                    'last_activity_time' => null,
                    'last_activity_title' => null,
                    'last_actor' => null,
                    'actors' => [],
                    'activities' => [],
                ];
            })
            ->sortBy(fn (array $lop) => sprintf('%d|%s', $lop['movement_status'] === 'active' ? 0 : 1, $lop['lop_name']))
            ->values();
    }

    private function activityCategory(string $type): string
    {
        return match (true) {
            str_contains($type, 'survey') => 'Survey',
            str_contains($type, 'boq'), str_contains($type, 'quantity') => 'BOQ',
            str_contains($type, 'evidence') => 'Eviden',
            str_contains($type, 'kronologi'), str_contains($type, 'permit') => 'Perizinan',
            str_contains($type, 'kendala'), str_contains($type, 'resume') => 'Kendala',
            str_contains($type, 'assign') => 'Assignment',
            str_contains($type, 'stage'), str_contains($type, 'golive'), str_contains($type, 'completed') => 'Status',
            default => 'Aktivitas',
        };
    }

    private function actorName(object $log): string
    {
        return $log->actor_name ?: $log->actor_username ?: 'Sistem/tidak tercatat';
    }

    /**
     * Master staging aktif yang dipakai untuk laporan harian. DRM dikeluarkan
     * karena sudah tidak menjadi bagian flow aktif; histori DRM dibaca sebagai
     * Perizinan, konsisten dengan pembacaan status di Project::progressSummary().
     *
     * @return Collection<string, array<string, mixed>>
     */
    private function stageDefinitions(): Collection
    {
        $stages = DB::table('project_stages')
            ->where('is_active', true)
            ->where('code', '!=', 'drm')
            ->orderByRaw('CASE WHEN sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sequence')
            ->orderBy('id')
            ->get(['code', 'label', 'phase_group', 'sequence', 'color'])
            ->mapWithKeys(fn ($stage) => [
                $stage->code => [
                    'code' => $stage->code,
                    'label' => $stage->label,
                    'phase_group' => $stage->phase_group ?: $stage->code,
                    'sequence' => $stage->sequence !== null ? (int) $stage->sequence : null,
                    'color' => $stage->color ?: 'slate',
                ],
            ]);

        $stages->put('activity_other', [
            'code' => 'activity_other',
            'label' => 'Aktivitas Lainnya',
            'phase_group' => 'other',
            'sequence' => null,
            'color' => 'slate',
        ]);

        return $stages;
    }

    /**
     * Ambil stage kendala lama dari project_issues. Log baru sudah menulis
     * kolom stage secara langsung, tetapi fallback ini menjaga histori resume.
     *
     * @return Collection<int, string|null>
     */
    private function issueStageMap(Collection $logs): Collection
    {
        $issueIds = $logs
            ->map(fn ($log) => $this->activityMeta($log)['issue_id'] ?? null)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($issueIds->isEmpty()) {
            return collect();
        }

        return DB::table('project_issues')
            ->whereIn('id_project_issues', $issueIds->all())
            ->pluck('stage_code', 'id_project_issues');
    }

    /**
     * Tentukan stage tempat aktivitas benar-benar terjadi. Kolom stage pada
     * activity log adalah sumber utama; fallback tidak memakai status LOP
     * saat ini sampai pilihan terakhir agar laporan tanggal lampau tidak mudah
     * bergeser ketika LOP sudah maju ke tahap berikutnya.
     */
    private function activityStageCode(object $log, Collection $issueStages, Collection $stageDefinitions): string
    {
        $validCodes = $stageDefinitions->keys()->all();
        $stage = strtolower(trim((string) ($log->stage ?? '')));

        // Data sebelum refactor mencatat dua eviden Step 2 sebagai
        // stage="persiapan". Dalam flow sekarang keduanya berada di
        // Persiapan Instalasi, bukan sub-step Persiapan yang baru.
        if ($stage === 'persiapan') {
            return 'persiapan_instalasi';
        }

        if ($stage === 'drm') {
            return 'perizinan';
        }

        if (in_array($stage, $validCodes, true)) {
            return $stage;
        }

        $meta = $this->activityMeta($log);
        $issueId = isset($meta['issue_id']) && is_numeric($meta['issue_id'])
            ? (int) $meta['issue_id']
            : null;
        $issueStage = $issueId ? strtolower(trim((string) $issueStages->get($issueId))) : '';

        if ($issueStage === 'drm') {
            return 'perizinan';
        }

        if (in_array($issueStage, $validCodes, true)) {
            return $issueStage;
        }

        $statusAfter = strtolower(trim((string) ($log->status_after ?? '')));
        if ($statusAfter === 'drm') {
            return 'perizinan';
        }

        if (in_array($statusAfter, $validCodes, true)) {
            return $statusAfter;
        }

        $type = strtolower((string) $log->activity_type);
        $mappedStage = match (true) {
            str_contains($type, 'survey') => 'survey',
            str_contains($type, 'assign') => 'inisiasi',
            str_contains($type, 'golive_submission') => 'fi_ogp_golive',
            str_contains($type, 'golive_verification') => 'golive',
            $type === 'lop_golive', $type === 'project_golive' => 'golive',
            $type === 'project_completed' => 'finishing',
            default => null,
        };

        if ($mappedStage && in_array($mappedStage, $validCodes, true)) {
            return $mappedStage;
        }

        $currentStage = strtolower(trim((string) ($log->status_progress ?? '')));
        if ($currentStage === 'drm') {
            return 'perizinan';
        }

        return in_array($currentStage, $validCodes, true) ? $currentStage : 'activity_other';
    }

    /** @return array<string, mixed> */
    private function activityMeta(object $log): array
    {
        if (is_array($log->meta ?? null)) {
            return $log->meta;
        }

        if (! is_string($log->meta ?? null) || trim($log->meta) === '') {
            return [];
        }

        $decoded = json_decode($log->meta, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Katalog Step dan Sub-step untuk membentuk distribusi posisi LOP pada UI.
     * Daftar Branch/LOP tidak disalin ke sini karena sudah tersedia pada key
     * branches; ini menjaga payload tetap ringkas setelah tampilan diubah
     * menjadi Branch -> Step -> Sub-step -> LOP.
     *
     * @param  Collection<string, array<string, mixed>>  $stageDefinitions
     * @return array<int, array<string, mixed>>
     */
    private function stageGroups(Collection $logs, Collection $stageDefinitions): array
    {
        $definitions = $stageDefinitions
            ->filter(fn (array $stage, string $code) => $code !== 'activity_other' || $logs->contains('activity_stage_code', $code));

        return $definitions
            ->groupBy('phase_group')
            ->map(function (Collection $stages, string $phaseGroup) use ($logs): array {
                $step = $this->stepDefinition($phaseGroup);
                $stageCodes = $stages->pluck('code')->all();
                $stepLogs = $logs->whereIn('activity_stage_code', $stageCodes);

                $substeps = $stages
                    ->sortBy(fn (array $stage) => sprintf('%05d|%s', $stage['sequence'] ?? 99999, $stage['label']))
                    ->map(function (array $stage) use ($logs): array {
                        $stageLogs = $logs->where('activity_stage_code', $stage['code']);

                        return $stage + $this->movementMetrics($stageLogs);
                    })
                    ->values();

                return $step + $this->movementMetrics($stepLogs) + [
                    'substeps' => $substeps->all(),
                ];
            })
            ->sortBy('order')
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function movementMetrics(Collection $logs): array
    {
        return [
            'moved_lops' => $logs->pluck('lop_id')->unique()->count(),
            'activity_count' => $logs->count(),
            'actor_count' => $logs->whereNotNull('user_id')->pluck('user_id')->unique()->count(),
        ];
    }

    /** @return array{code: string, label: string, description: string, order: int} */
    private function stepDefinition(string $phaseGroup): array
    {
        return match ($phaseGroup) {
            'persiapan' => ['code' => 'persiapan', 'label' => 'Step 1 · Persiapan', 'description' => 'Inisiasi, Survey, Perizinan, dan Material Delivery', 'order' => 1],
            'persiapan_instalasi' => ['code' => 'persiapan_instalasi', 'label' => 'Step 2 · Persiapan Instalasi', 'description' => 'Kesiapan barang dan dokumen sebelum instalasi', 'order' => 2],
            'instalasi' => ['code' => 'instalasi', 'label' => 'Step 3 · Instalasi', 'description' => 'Aktivitas pembangunan dan kuantitas aktual', 'order' => 3],
            'pengukuran' => ['code' => 'pengukuran', 'label' => 'Step 4 · Pengukuran', 'description' => 'OTDR, OPM, kedalaman, dan hasil ukur lainnya', 'order' => 4],
            'finishing' => ['code' => 'finishing', 'label' => 'Step 5 · Finishing', 'description' => 'Eviden penyelesaian dan review akhir', 'order' => 5],
            'golive' => ['code' => 'golive', 'label' => 'Step 6 · FI-OGP & Golive', 'description' => 'Dokumen FI-OGP, verifikasi SDI, dan Golive', 'order' => 6],
            'pause' => ['code' => 'pause', 'label' => 'Status Khusus', 'description' => 'Aktivitas pada LOP Hold atau Drop', 'order' => 7],
            default => ['code' => 'other', 'label' => 'Aktivitas Lainnya', 'description' => 'Aktivitas yang belum memiliki konteks staging yang lengkap', 'order' => 8],
        };
    }
}
