<?php

namespace App\Console\Commands;

use App\Models\Evidence;
use App\Models\ProjectActivityLog;
use App\Models\ProjectAssignment;
use App\Models\Pt2Assignment;
use App\Models\Pt2Evidence;
use App\Models\TelegramWebhookEvent;
use App\Services\TelegramWebhookEventService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Publish event pengingat (untuk role PM) ke tabel telegram_webhook_events,
 * untuk project (alur biasa & PT2) yang sudah di-assign ke waspang/teknisi
 * tapi tidak ada update progress (upload eviden / aktivitas) lebih dari N jam
 * (default 24 jam / 1 hari). Dijadwalkan otomatis lewat routes/console.php.
 */
class PublishStaleProjectReminders extends Command
{
    protected $signature = 'webhook:publish-stale-project-reminders {--hours=24}';

    protected $description = 'Publish event pengingat ke telegram_webhook_events untuk project yang di-assign tapi tidak ada update progress lebih dari N jam';

    public function handle(): int
    {
        $thresholdHours = (int) $this->option('hours');
        $threshold = now()->subHours($thresholdHours);

        $published = 0;
        $published += $this->publishStaleMainProjects($threshold, $thresholdHours);
        $published += $this->publishStalePt2Projects($threshold, $thresholdHours);

        $this->info("Selesai. {$published} event pengingat project stale dipublish ke telegram_webhook_events.");

        return self::SUCCESS;
    }

    /**
     * Guard supaya tidak menerbitkan event yang sama berulang kali dalam satu hari
     * (kalau command ini dijalankan lebih dari sekali, atau project tetap stale
     * berhari-hari -- 1 event baru per hari per project sudah cukup untuk "reminder").
     */
    protected function alreadyPublishedToday(int $projectId, ?int $lopId = null): bool
    {
        return TelegramWebhookEvent::where('event_type', 'project_stale_reminder')
            ->where('project_id', $projectId)
            ->when($lopId, fn ($q) => $q->where('lop_id', $lopId))
            ->whereDate('created_at', now()->toDateString())
            ->exists();
    }

    protected function publishStaleMainProjects(Carbon $threshold, int $thresholdHours): int
    {
        $assignments = ProjectAssignment::with(['project', 'waspang', 'teknisi'])
            ->where(function ($q) {
                $q->whereNotNull('waspang_id')->orWhereNotNull('teknisi_id');
            })
            ->get();

        $published = 0;

        foreach ($assignments as $assignment) {
            $project = $assignment->project;

            if (! $project || $project->status_project === 'close') {
                continue;
            }

            $lastEvidence = Evidence::where('project_id', $project->id_project)->max('created_at');
            $lastActivity = ProjectActivityLog::where('project_id', $project->id_project)->max('created_at');

            $lastUpdate = collect([$lastEvidence, $lastActivity, $assignment->created_at])
                ->filter()
                ->map(fn ($d) => Carbon::parse($d))
                ->sort()
                ->last();

            if ($lastUpdate && $lastUpdate->gt($threshold)) {
                continue;
            }

            if ($this->alreadyPublishedToday($project->id_project)) {
                continue;
            }

            $assignee = $assignment->waspang_id ? $assignment->waspang : $assignment->teknisi;
            $role = $assignment->waspang_id ? 'waspang' : 'teknisi';

            TelegramWebhookEventService::publishToRole(
                'pm',
                'project_stale_reminder',
                'Project Belum Ada Update',
                "Project {$project->project_name} (" . ($project->pid ?? '-') . ") yang di-assign ke {$role} " . ($assignee->name ?? '-') . " belum ada update progress lebih dari {$thresholdHours} jam.",
                [
                    'project_id' => $project->id_project,
                    'project_name' => $project->project_name,
                    'pid' => $project->pid,
                    'assignee_name' => $assignee->name ?? null,
                    'assignee_role' => $role,
                    'last_update_at' => $lastUpdate?->toIso8601String(),
                    'threshold_hours' => $thresholdHours,
                ],
                ['project_id' => $project->id_project]
            );

            $published++;
        }

        return $published;
    }

    protected function publishStalePt2Projects(Carbon $threshold, int $thresholdHours): int
    {
        $assignments = Pt2Assignment::with(['lop.project', 'teknisi'])
            ->whereNotNull('teknisi_id')
            ->get();

        $published = 0;

        foreach ($assignments as $assignment) {
            $lop = $assignment->lop;

            if (! $lop || (bool) ($lop->is_golive ?? false)) {
                continue;
            }

            $lastEvidence = Pt2Evidence::where('pt2_lop_id', $lop->id_pt2_lop)->max('created_at');
            $lastActivity = ProjectActivityLog::where('lop_id', $lop->id_pt2_lop)->max('created_at');

            $lastUpdate = collect([$lastEvidence, $lastActivity, $assignment->created_at])
                ->filter()
                ->map(fn ($d) => Carbon::parse($d))
                ->sort()
                ->last();

            if ($lastUpdate && $lastUpdate->gt($threshold)) {
                continue;
            }

            if ($this->alreadyPublishedToday($assignment->pt2_project_id, $lop->id_pt2_lop)) {
                continue;
            }

            $projectName = $lop->project->project_name ?? ('PT2 Project #' . $lop->pt2_project_id);

            TelegramWebhookEventService::publishToRole(
                'pm',
                'project_stale_reminder',
                'Project PT2 Belum Ada Update',
                "Project PT2 {$projectName} — LOP " . ($lop->lop_name ?? $lop->id_pt2_lop) . ' yang di-assign ke teknisi ' . ($assignment->teknisi->name ?? '-') . " belum ada update progress lebih dari {$thresholdHours} jam.",
                [
                    'pt2_project_id' => $lop->pt2_project_id,
                    'pt2_lop_id' => $lop->id_pt2_lop,
                    'project_name' => $projectName,
                    'lop_name' => $lop->lop_name,
                    'assignee_name' => $assignment->teknisi->name ?? null,
                    'assignee_role' => 'teknisi',
                    'last_update_at' => $lastUpdate?->toIso8601String(),
                    'threshold_hours' => $thresholdHours,
                ],
                ['project_id' => $assignment->pt2_project_id, 'lop_id' => $lop->id_pt2_lop]
            );

            $published++;
        }

        return $published;
    }
}
