<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperationalMonitoringService
{
    private ?Collection $rowsCache = null;

    public function __construct(
        private readonly ApprovalCenterService $approvalCenter,
        private readonly DatabaseSchemaInspector $schema,
    ) {}

    /** @return array<string, mixed> */
    public function dashboard(int $thresholdDays = 7): array
    {
        $thresholdDays = in_array($thresholdDays, [1, 3, 7, 14, 30], true) ? $thresholdDays : 7;
        $rows = $this->rows()->map(function (array $row) use ($thresholdDays): array {
            $row['is_bottleneck'] = $row['is_active'] && $row['stage_age_days'] >= $thresholdDays;

            return $row;
        });

        return [
            'rows' => $rows,
            'summary' => [
                'active_lop_count' => $rows->where('is_active', true)->pluck('group_key')->unique()->count(),
                'bottleneck_count' => $rows->where('is_bottleneck', true)->count(),
                'unassigned_count' => $rows->where('is_unassigned', true)->count(),
                'pending_count' => $rows->sum('pending_count'),
                'pending_admin_count' => $rows->where('pending_count', '>', 0)
                    ->pluck('admin_key')->filter(fn ($key) => ! str_starts_with($key, 'unowned:'))->unique()->count(),
                'branch_count' => $rows->pluck('branch')->filter(fn ($branch) => $branch !== '-')->unique()->count(),
            ],
            'admin_summary' => $this->adminSummary($rows),
            'branch_summary' => $this->branchSummary($rows),
            'available_admins' => $rows->map(fn (array $row) => [
                'key' => $row['admin_key'],
                'name' => $row['admin_name'],
            ])->unique('key')->sortBy('name')->values(),
            'available_branches' => $rows->pluck('branch')->filter(fn ($branch) => $branch !== '-')
                ->unique()->sort()->values(),
            'available_stages' => $rows->map(fn (array $row) => [
                'code' => $row['stage_code'],
                'label' => $row['stage_label'],
            ])->unique('code')->sortBy('label')->values(),
            'threshold_days' => $thresholdDays,
        ];
    }

    public function rows(): Collection
    {
        return $this->rowsCache ??= $this->attachApprovalQueue(
            $this->regularRows()->concat($this->pt2Rows())
        )->sortByDesc(fn (array $row) => [$row['stage_age_days'], $row['idle_days'], $row['pending_count']])
            ->values();
    }

    private function regularRows(): Collection
    {
        if (! $this->schema->hasTables(['lops', 'projects', 'pro_assign'])) {
            return collect();
        }

        $latestAssignmentIds = DB::table('pro_assign')
            ->selectRaw('project_id, MAX(id_proassign) AS assignment_id')
            ->groupBy('project_id');

        $query = DB::table('lops as l')
            ->join('projects as p', 'p.id_project', '=', 'l.project_id')
            ->leftJoinSub($latestAssignmentIds, 'assignment_ids', 'assignment_ids.project_id', '=', 'p.id_project')
            ->leftJoin('pro_assign as pa', 'pa.id_proassign', '=', 'assignment_ids.assignment_id')
            ->leftJoin('users as admin_user', 'admin_user.id_user', '=', 'pa.assigned_by')
            ->leftJoin('users as waspang_user', 'waspang_user.id_user', '=', 'pa.waspang_id')
            ->leftJoin('users as teknisi_user', 'teknisi_user.id_user', '=', 'pa.teknisi_id');

        if ($this->schema->hasColumn('lops', 'is_golive')) {
            $query->where(fn ($active) => $active->whereNull('l.is_golive')->orWhere('l.is_golive', 0));
        }
        if ($this->schema->hasColumn('lops', 'sdi_approval_status')) {
            $query->where(fn ($active) => $active->whereNull('l.sdi_approval_status')->orWhere('l.sdi_approval_status', '!=', 'approved'));
        }
        if ($this->schema->hasColumn('lops', 'status_progress')) {
            $query->where(fn ($active) => $active->whereNull('l.status_progress')
                ->orWhereNotIn(DB::raw('LOWER(l.status_progress)'), ['golive', 'drop']));
        }

        $select = [
            'l.id_lop', 'l.project_id', 'l.lop_name', 'l.branch', 'l.sto',
            'l.created_at', 'l.updated_at',
            'p.pid', 'p.pid_sap', 'p.project_name', 'p.program',
            'p.branch as project_branch', 'p.sto as project_sto', 'p.mitra_name as project_mitra',
            'pa.assigned_by', 'pa.waspang_id', 'pa.teknisi_id',
            'pa.created_at as assignment_created_at', 'pa.updated_at as assignment_updated_at',
            'admin_user.name as assigned_admin_name',
            'waspang_user.name as waspang_name', 'teknisi_user.name as teknisi_name',
        ];

        foreach (['id_ihld', 'mitra_name', 'status_progress', 'nama_admin', 'nik_admin'] as $column) {
            if ($this->schema->hasColumn('lops', $column)) {
                $select[] = 'l.'.$column;
            }
        }

        $baseRows = $query->select($select)->get();
        if ($baseRows->isEmpty()) {
            return collect();
        }

        $lopIds = $baseRows->pluck('id_lop');
        $projectIds = $baseRows->pluck('project_id');
        $historyMap = $this->openStageHistoryMap($lopIds);
        $evidenceMap = $this->latestTimestampMap('evidences', 'project_id', $projectIds);
        $activityByLop = $this->activityTimestampMap('lop_id', $lopIds, false);
        $activityByProject = $this->activityTimestampMap('project_id', $projectIds, false);
        $stageLabels = $this->stageLabels();
        [$adminsByNik, $adminsByName] = $this->adminIdentityMaps();

        return $baseRows->map(function ($row) use ($historyMap, $evidenceMap, $activityByLop, $activityByProject, $stageLabels, $adminsByNik, $adminsByName): array {
            $status = strtolower((string) ($row->status_progress ?? 'inisiasi')) ?: 'inisiasi';
            $importedNik = trim((string) ($row->nik_admin ?? ''));
            $importedName = trim((string) ($row->nama_admin ?? ''));
            $importedAdmin = ($importedNik !== '' ? $adminsByNik->get(mb_strtolower($importedNik)) : null)
                ?? ($importedName !== '' ? $adminsByName->get(mb_strtolower($importedName)) : null);
            $adminId = $row->assigned_by ?: ($importedAdmin?->id_user);
            $adminName = $row->assigned_admin_name ?: ($importedAdmin?->name ?: ($importedName ?: 'Belum ada Admin'));
            $assignees = collect([$row->waspang_name, $row->teknisi_name])->filter()->unique()->values();
            $stageEnteredAt = $this->asCarbon($historyMap->get($row->id_lop))
                ?? $this->asCarbon($row->updated_at)
                ?? $this->asCarbon($row->created_at);
            $lastMovementAt = $this->latestDate([
                $row->updated_at,
                $row->assignment_updated_at,
                $evidenceMap->get($row->project_id),
                $activityByLop->get($row->id_lop),
                $activityByProject->get($row->project_id),
            ]) ?? $stageEnteredAt;

            return $this->normalizeRow([
                'source' => 'pt3',
                'source_label' => 'PT3 / Reguler',
                'group_key' => 'pt3:'.$row->id_lop,
                'project_id' => (int) $row->project_id,
                'lop_id' => (int) $row->id_lop,
                'project_name' => $row->project_name ?: '-',
                'lop_name' => $row->lop_name ?: ($row->project_name ?: '-'),
                'pid' => $row->pid_sap ?: ($row->pid ?: '-'),
                'id_ihld' => $row->id_ihld ?? '-',
                'program' => $row->program ?: '-',
                'branch' => $row->branch ?: ($row->project_branch ?: '-'),
                'sto' => $row->sto ?: ($row->project_sto ?: '-'),
                'mitra' => ($row->mitra_name ?? null) ?: ($row->project_mitra ?: '-'),
                'stage_code' => $status,
                'stage_label' => $stageLabels->get($status) ?: $this->stageLabel($status),
                'stage_entered_at' => $stageEnteredAt,
                'last_movement_at' => $lastMovementAt,
                'admin_id' => $adminId ? (int) $adminId : null,
                'admin_name' => $adminName,
                'assignee_name' => $assignees->implode(', ') ?: 'Belum assign',
                'is_unassigned' => $adminName !== 'Belum ada Admin' && $assignees->isEmpty(),
                'detail_url' => route('admin.projects.timeline', $row->project_id),
            ]);
        });
    }

    private function pt2Rows(): Collection
    {
        if (! $this->schema->hasTables(['pt2_lops', 'pt2_projects', 'pt2_assignments'])) {
            return collect();
        }

        $latestAssignmentIds = DB::table('pt2_assignments')
            ->selectRaw('pt2_lop_id, MAX(id_pt2_assignment) AS assignment_id')
            ->groupBy('pt2_lop_id');

        $query = DB::table('pt2_lops as l')
            ->join('pt2_projects as p', 'p.id_pt2_project', '=', 'l.pt2_project_id')
            ->leftJoinSub($latestAssignmentIds, 'assignment_ids', 'assignment_ids.pt2_lop_id', '=', 'l.id_pt2_lop')
            ->leftJoin('pt2_assignments as pa', 'pa.id_pt2_assignment', '=', 'assignment_ids.assignment_id')
            ->leftJoin('users as admin_user', 'admin_user.id_user', '=', 'pa.assigned_by')
            ->leftJoin('users as teknisi_user', 'teknisi_user.id_user', '=', 'pa.teknisi_id')
            ->where(fn ($active) => $active->whereNull('l.is_golive')->orWhere('l.is_golive', 0))
            ->where(fn ($active) => $active->whereNull('l.sdi_approval_status')->orWhere('l.sdi_approval_status', '!=', 'approved'))
            ->where(fn ($active) => $active->whereNull('l.status_progress')
                ->orWhereNotIn(DB::raw('LOWER(l.status_progress)'), ['golive', 'drop']));

        $baseRows = $query->select([
            'l.id_pt2_lop', 'l.pt2_project_id', 'l.lop_name', 'l.id_ihld', 'l.branch', 'l.sto',
            'l.mitra_name', 'l.status_progress', 'l.created_at', 'l.updated_at',
            'p.pid', 'p.pid_sap', 'p.project_name', 'p.program',
            'p.branch as project_branch', 'p.sto as project_sto', 'p.mitra_name as project_mitra',
            'pa.assigned_by', 'pa.teknisi_id', 'pa.created_at as assignment_created_at',
            'pa.updated_at as assignment_updated_at', 'admin_user.name as assigned_admin_name',
            'teknisi_user.name as teknisi_name',
        ])->get();

        if ($baseRows->isEmpty()) {
            return collect();
        }

        $lopIds = $baseRows->pluck('id_pt2_lop');
        $evidenceStats = $this->pt2EvidenceStats($lopIds);
        $surveyStats = $this->minMaxTimestampMap('surveys_pt2', 'pt2_lop_id', $lopIds);
        $dismantleStats = $this->minMaxTimestampMap('dismantles_pt2', 'pt2_lop_id', $lopIds);
        $mancoreStats = $this->minMaxTimestampMap('mancores_pt2', 'pt2_lop_id', $lopIds);
        $activityMap = $this->activityTimestampMap('lop_id', $lopIds, true);

        return $baseRows->map(function ($row) use ($evidenceStats, $surveyStats, $dismantleStats, $mancoreStats, $activityMap): array {
            $status = $this->normalizePt2Stage((string) ($row->status_progress ?? 'inisiasi'));
            $evidence = $evidenceStats->get($row->id_pt2_lop);
            $survey = $surveyStats->get($row->id_pt2_lop);
            $dismantle = $dismantleStats->get($row->id_pt2_lop);
            $mancore = $mancoreStats->get($row->id_pt2_lop);
            $stageEnteredAt = match ($status) {
                'survey' => $this->earliestDate([$row->assignment_created_at, $survey['first'] ?? null, $evidence->survey_first ?? null]),
                'instalasi' => $this->asCarbon($evidence->instalasi_first ?? null),
                'finishing' => $this->earliestDate([$evidence->finishing_first ?? null, $dismantle['first'] ?? null]),
                'fi_ogp_golive' => $this->earliestDate([$mancore['first'] ?? null, $evidence->fi_ogp_first ?? null]),
                default => $this->asCarbon($row->created_at),
            };
            $stageEnteredAt ??= $this->asCarbon($row->updated_at) ?? $this->asCarbon($row->created_at);
            $lastMovementAt = $this->latestDate([
                $row->updated_at,
                $row->assignment_updated_at,
                $evidence->latest_at ?? null,
                $survey['latest'] ?? null,
                $dismantle['latest'] ?? null,
                $mancore['latest'] ?? null,
                $activityMap->get($row->id_pt2_lop),
            ]) ?? $stageEnteredAt;
            $adminName = $row->assigned_admin_name ?: 'Belum ada Admin';

            return $this->normalizeRow([
                'source' => 'pt2',
                'source_label' => 'PT2',
                'group_key' => 'pt2:'.$row->id_pt2_lop,
                'project_id' => (int) $row->pt2_project_id,
                'lop_id' => (int) $row->id_pt2_lop,
                'project_name' => $row->project_name ?: '-',
                'lop_name' => $row->lop_name ?: ($row->project_name ?: '-'),
                'pid' => $row->pid_sap ?: ($row->pid ?: '-'),
                'id_ihld' => $row->id_ihld ?: '-',
                'program' => $row->program ?: 'PT2',
                'branch' => $row->branch ?: ($row->project_branch ?: '-'),
                'sto' => $row->sto ?: ($row->project_sto ?: '-'),
                'mitra' => $row->mitra_name ?: ($row->project_mitra ?: '-'),
                'stage_code' => $status,
                'stage_label' => $this->stageLabel($status),
                'stage_entered_at' => $stageEnteredAt,
                'last_movement_at' => $lastMovementAt,
                'admin_id' => $row->assigned_by ? (int) $row->assigned_by : null,
                'admin_name' => $adminName,
                'assignee_name' => $row->teknisi_name ?: 'Belum assign',
                'is_unassigned' => $adminName !== 'Belum ada Admin' && ! $row->teknisi_id,
                'detail_url' => route('pt2.timeline', $row->id_pt2_lop),
            ]);
        });
    }

    private function attachApprovalQueue(Collection $rows): Collection
    {
        $queue = $this->approvalCenter->queue();
        $queueByKey = $queue->keyBy('group_key');
        $seen = collect();

        $rows = $rows->map(function (array $row) use ($queueByKey, $seen): array {
            $approval = $queueByKey->get($row['group_key']);
            if (! $approval && $row['source'] === 'pt3') {
                $approval = $queueByKey->get('pt3:project-'.$row['project_id']);
            }

            if ($approval) {
                $seen->push($approval['group_key']);
                $row['pending_count'] = (int) $approval['pending_count'];
                $row['pending_age_hours'] = (int) $approval['age_hours'];
                $row['pending_types'] = $approval['evidence_types']->values()->all();
                $row['review_url'] = $approval['review_url'];
            }

            return $row;
        });

        $missing = $queue->reject(fn (array $item) => $seen->contains($item['group_key']))
            ->map(function (array $item): array {
                $enteredAt = $item['oldest_at'] ?? $item['created_at'] ?? now();

                return $this->normalizeRow([
                    'source' => $item['source'],
                    'source_label' => $item['source_label'],
                    'group_key' => $item['group_key'],
                    'project_id' => (int) $item['project_id'],
                    'lop_id' => $item['lop_id'] ? (int) $item['lop_id'] : null,
                    'project_name' => $item['project_name'],
                    'lop_name' => $item['lop_name'],
                    'pid' => $item['pid'],
                    'id_ihld' => '-',
                    'program' => $item['program'],
                    'branch' => $item['branch'],
                    'sto' => $item['sto'],
                    'mitra' => '-',
                    'stage_code' => (string) $item['stage'],
                    'stage_label' => $this->stageLabel((string) $item['stage']),
                    'stage_entered_at' => $enteredAt,
                    'last_movement_at' => $item['latest_at'] ?? $enteredAt,
                    'admin_id' => $item['admin_id'] ? (int) $item['admin_id'] : null,
                    'admin_name' => $item['admin_name'],
                    'assignee_name' => '-',
                    'is_unassigned' => false,
                    'is_active' => false,
                    'pending_count' => (int) $item['pending_count'],
                    'pending_age_hours' => (int) $item['age_hours'],
                    'pending_types' => $item['evidence_types']->values()->all(),
                    'review_url' => $item['review_url'],
                    'detail_url' => $item['source'] === 'pt2' && $item['lop_id']
                        ? route('pt2.timeline', $item['lop_id'])
                        : route('admin.projects.timeline', $item['project_id']),
                ]);
            });

        return $rows->concat($missing)->values();
    }

    private function normalizeRow(array $row): array
    {
        $stageEnteredAt = $this->asCarbon($row['stage_entered_at'] ?? null) ?? CarbonImmutable::now();
        $lastMovementAt = $this->asCarbon($row['last_movement_at'] ?? null) ?? $stageEnteredAt;
        $adminId = $row['admin_id'] ?? null;
        $adminName = trim((string) ($row['admin_name'] ?? '')) ?: 'Belum ada Admin';
        $isUnowned = str_starts_with(mb_strtolower($adminName), 'belum ada admin');

        return array_merge([
            'is_active' => true,
            'pending_count' => 0,
            'pending_age_hours' => 0,
            'pending_types' => [],
            'review_url' => null,
        ], $row, [
            'stage_entered_at' => $stageEnteredAt,
            'last_movement_at' => $lastMovementAt,
            'stage_age_days' => max(0, (int) $stageEnteredAt->diffInDays(now())),
            'idle_days' => max(0, (int) $lastMovementAt->diffInDays(now())),
            'admin_name' => $adminName,
            'admin_key' => $adminId
                ? 'id:'.$adminId
                : ($isUnowned
                    ? 'unowned:belum-ada-admin'
                    : 'name:'.mb_strtolower($adminName)),
        ]);
    }

    private function adminSummary(Collection $rows): Collection
    {
        return $rows->groupBy('admin_key')->map(function (Collection $items): array {
            $first = $items->first();
            $activeItems = $items->where('is_active', true);

            return [
                'key' => $first['admin_key'],
                'admin_id' => $first['admin_id'],
                'admin_name' => $first['admin_name'],
                'lop_count' => $activeItems->pluck('group_key')->unique()->count(),
                'bottleneck_count' => $items->where('is_bottleneck', true)->count(),
                'unassigned_count' => $items->where('is_unassigned', true)->count(),
                'pending_count' => $items->sum('pending_count'),
                'oldest_days' => (int) ($activeItems->max('stage_age_days') ?? 0),
            ];
        })->sortByDesc(fn (array $row) => [$row['pending_count'], $row['bottleneck_count'], $row['oldest_days']])
            ->values();
    }

    private function branchSummary(Collection $rows): Collection
    {
        return $rows->groupBy(fn (array $row) => $row['branch'] ?: '-')
            ->map(function (Collection $items, string $branch): array {
                $activeItems = $items->where('is_active', true);

                return [
                    'branch' => $branch,
                    'lop_count' => $activeItems->pluck('group_key')->unique()->count(),
                    'bottleneck_count' => $items->where('is_bottleneck', true)->count(),
                    'unassigned_count' => $items->where('is_unassigned', true)->count(),
                    'pending_count' => $items->sum('pending_count'),
                    'oldest_days' => (int) ($activeItems->max('stage_age_days') ?? 0),
                ];
            })->sortByDesc(fn (array $row) => [$row['bottleneck_count'], $row['pending_count'], $row['oldest_days']])
            ->values();
    }

    private function openStageHistoryMap(Collection $lopIds): Collection
    {
        if (! $this->schema->hasTable('lop_stage_histories')) {
            return collect();
        }

        return DB::table('lop_stage_histories')->whereIn('lop_id', $lopIds)
            ->whereNull('completed_at')->selectRaw('lop_id, MAX(entered_at) AS entered_at')
            ->groupBy('lop_id')->pluck('entered_at', 'lop_id');
    }

    private function latestTimestampMap(string $table, string $key, Collection $ids): Collection
    {
        if (! $this->schema->hasTable($table) || $ids->isEmpty()) {
            return collect();
        }

        return DB::table($table)->whereIn($key, $ids)
            ->selectRaw("{$key}, MAX(updated_at) AS latest_at")
            ->groupBy($key)->pluck('latest_at', $key);
    }

    private function minMaxTimestampMap(string $table, string $key, Collection $ids): Collection
    {
        if (! $this->schema->hasTable($table) || $ids->isEmpty()) {
            return collect();
        }

        return DB::table($table)->whereIn($key, $ids)
            ->selectRaw("{$key}, MIN(created_at) AS first_at, MAX(updated_at) AS latest_at")
            ->groupBy($key)->get()->mapWithKeys(fn ($row) => [$row->{$key} => [
                'first' => $row->first_at,
                'latest' => $row->latest_at,
            ]]);
    }

    private function activityTimestampMap(string $key, Collection $ids, bool $pt2): Collection
    {
        if (! $this->schema->hasTable('project_activity_logs') || $ids->isEmpty()) {
            return collect();
        }

        $query = DB::table('project_activity_logs')->whereIn($key, $ids);
        $pt2 ? $query->where('activity_type', 'like', '%pt2%') : $query->where('activity_type', 'not like', '%pt2%');

        return $query->selectRaw("{$key}, MAX(created_at) AS latest_at")
            ->groupBy($key)->pluck('latest_at', $key);
    }

    private function pt2EvidenceStats(Collection $lopIds): Collection
    {
        if (! $this->schema->hasTable('pt2_evidences') || $lopIds->isEmpty()) {
            return collect();
        }

        return DB::table('pt2_evidences')->whereIn('pt2_lop_id', $lopIds)
            ->selectRaw("pt2_lop_id,
                MAX(updated_at) AS latest_at,
                MIN(CASE WHEN LOWER(stage) IN ('persiapan', 'survey') THEN created_at END) AS survey_first,
                MIN(CASE WHEN LOWER(stage) IN ('instalasi', 'progress') THEN created_at END) AS instalasi_first,
                MIN(CASE WHEN LOWER(stage) IN ('finishing', 'finish', 'redaman', 'dismantle') THEN created_at END) AS finishing_first,
                MIN(CASE WHEN LOWER(stage) IN ('mancore', 'fi_ogp_golive') THEN created_at END) AS fi_ogp_first")
            ->groupBy('pt2_lop_id')->get()->keyBy('pt2_lop_id');
    }

    private function stageLabels(): Collection
    {
        if (! $this->schema->hasTable('project_stages')) {
            return collect();
        }

        return DB::table('project_stages')->pluck('label', 'code');
    }

    /** @return array{0: Collection, 1: Collection} */
    private function adminIdentityMaps(): array
    {
        $admins = User::roleCode(['admin', 'superadmin', 'super_tif', 'officer'])
            ->get(['id_user', 'nik', 'name']);

        return [
            $admins->filter(fn (User $user) => filled($user->nik))
                ->keyBy(fn (User $user) => mb_strtolower(trim((string) $user->nik))),
            $admins->keyBy(fn (User $user) => mb_strtolower(trim($user->name))),
        ];
    }

    private function normalizePt2Stage(string $stage): string
    {
        return match (strtolower($stage)) {
            'preparation', 'persiapan' => 'inisiasi',
            'progress' => 'instalasi',
            'finish', 'redaman', 'dismantle' => 'finishing',
            'mancore', 'done', 'complete' => 'fi_ogp_golive',
            default => strtolower($stage) ?: 'inisiasi',
        };
    }

    private function stageLabel(string $stage): string
    {
        return match (strtolower($stage)) {
            'inisiasi', 'preparation', 'persiapan' => 'Inisiasi / Persiapan',
            'survey' => 'Survey',
            'perizinan' => 'Perizinan',
            'persiapan_instalasi', 'material_delivery' => 'Persiapan Instalasi',
            'instalasi', 'progress' => 'Instalasi',
            'pengukuran' => 'Pengukuran',
            'finishing', 'finish', 'redaman', 'dismantle' => 'Finishing',
            'fi_ogp_golive', 'mancore' => 'FI-OGP Golive',
            default => ucfirst(str_replace('_', ' ', $stage ?: 'Lainnya')),
        };
    }

    private function latestDate(array $dates): ?CarbonImmutable
    {
        return collect($dates)->map(fn ($date) => $this->asCarbon($date))->filter()->sortDesc()->first();
    }

    private function earliestDate(array $dates): ?CarbonImmutable
    {
        return collect($dates)->map(fn ($date) => $this->asCarbon($date))->filter()->sort()->first();
    }

    private function asCarbon($value): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::parse($value) : null;
    }
}
