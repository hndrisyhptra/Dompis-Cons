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

        $logsByBranch = $dailyLogs->groupBy(fn ($row) => $this->branchName($row));
        $branches = $lopTotals
            ->map(function ($row) use ($branchToRegion, $date, $lastActivities, $logsByBranch): array {
                $branch = strtoupper(trim((string) $row->branch));
                $logs = $logsByBranch->get($branch, collect());
                $lopDetails = $this->lopDetails($logs);
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
}
