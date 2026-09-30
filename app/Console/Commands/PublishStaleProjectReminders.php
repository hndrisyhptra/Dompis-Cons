<?php

namespace App\Console\Commands;

use App\Models\ProjectActivityLog;
use App\Models\ProjectAssignment;
use App\Models\TelegramWebhookEvent;
use App\Services\LopSCurveService;
use App\Services\TelegramWebhookEventService;
use Illuminate\Console\Command;

/**
 * Publish event pengingat ke tabel telegram_webhook_events untuk LOP PT3
 * yang melewati due date milestone Kurva-S sesuai tahap aktif.
 * Penerima: Admin assigner, Waspang, dan role PM.
 */
class PublishStaleProjectReminders extends Command
{
    protected $signature = 'webhook:publish-stale-project-reminders {--grace-days=0 : Tambahan toleransi hari kalender setelah due date Kurva-S}';

    protected $description = 'Publish reminder PT3 yang melewati due date milestone Kurva-S ke Admin assigner, Waspang, dan PM';

    public function handle(LopSCurveService $curve): int
    {
        $graceDays = max(0, (int) $this->option('grace-days'));

        [$published, $skipped] = $this->publishOverduePt3Projects($curve, $graceDays);

        $this->info("Selesai. {$published} event reminder due date PT3 dipublish; {$skipped} LOP dilewati karena Kurva-S belum dapat dihitung.");

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

    /** @return array{0: int, 1: int} jumlah event dan jumlah LOP tanpa due date */
    protected function publishOverduePt3Projects(LopSCurveService $curve, int $graceDays): array
    {
        $assignments = ProjectAssignment::with(['project.lop', 'admin', 'waspang'])
            ->whereNotNull('waspang_id')
            ->get();

        $published = 0;
        $skipped = 0;

        foreach ($assignments as $assignment) {
            $project = $assignment->project;
            $lop = $project?->lop;

            if (! $project || ! $lop || in_array($lop->status_progress, ['golive', 'drop', 'hold'], true) || (bool) $lop->is_golive) {
                continue;
            }

            $lop->loadMissing([
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
            $activityLogs = ProjectActivityLog::query()
                ->where('project_id', $lop->project_id)
                ->where('lop_id', $lop->id_lop)
                ->oldest('created_at')
                ->get();
            $due = $curve->currentStageDue($lop, $activityLogs);

            if (! $due['ready'] || ! $due['due_date']) {
                $skipped++;

                continue;
            }

            $dueDate = $due['due_date']->startOfDay();
            $deadline = $dueDate->endOfDay()->addDays($graceDays);

            if (now()->lessThanOrEqualTo($deadline) || $this->alreadyPublishedToday($project->id_project, $lop->id_lop)) {
                continue;
            }

            $overdueDays = (int) $dueDate->diffInDays(now()->startOfDay());
            $payload = [
                'flow' => 'PT3',
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'project_name' => $project->project_name,
                'lop_name' => $lop->lop_name,
                'pid' => $project->pid,
                'branch' => $lop->branch,
                'sto' => $lop->sto,
                'stage_code' => $due['stage_code'],
                'target_label' => $due['target_label'],
                'due_date' => $dueDate->format('Y-m-d'),
                'overdue_days' => $overdueDays,
                'grace_days' => $graceDays,
                'due_source' => 's_curve',
            ];
            $title = 'LOP Melewati Due Date Kurva-S';
            $message = "{$project->project_name} — LOP {$lop->lop_name} pada tahap {$due['target_label']} melewati due date Kurva-S {$dueDate->format('d-m-Y')} selama {$overdueDays} hari.";

            $userRecipients = collect([$assignment->admin, $assignment->waspang])
                ->filter()
                ->unique('id_user');

            foreach ($userRecipients as $recipient) {
                TelegramWebhookEventService::publishToUser(
                    $recipient,
                    'project_stale_reminder',
                    $title,
                    $message,
                    $payload,
                    ['project_id' => $project->id_project, 'lop_id' => $lop->id_lop],
                );
                $published++;
            }

            TelegramWebhookEventService::publishToRole(
                'pm',
                'project_stale_reminder',
                $title,
                $message,
                $payload,
                ['project_id' => $project->id_project, 'lop_id' => $lop->id_lop],
            );
            $published++;
        }

        return [$published, $skipped];
    }
}
