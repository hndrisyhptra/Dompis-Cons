<?php

namespace App\Console\Commands;

use App\Models\Evidence;
use App\Models\ProjectActivityLog;
use App\Models\ProjectAssignment;
use App\Models\Pt2Assignment;
use App\Models\Pt2Evidence;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Reminder ke role PM untuk project (alur biasa & PT2) yang sudah di-assign ke
 * waspang/teknisi tapi tidak ada update progress (upload eviden / aktivitas)
 * lebih dari N jam (default 24 jam / 1 hari, sesuai permintaan).
 *
 * Dijadwalkan otomatis lewat routes/console.php (Schedule::command(...)).
 */
class RemindStaleProjects extends Command
{
    protected $signature = 'telegram:remind-stale-projects {--hours=24}';

    protected $description = 'Kirim reminder Telegram ke role PM untuk project yang di-assign tapi tidak ada update progress lebih dari N jam';

    public function handle(): int
    {
        $thresholdHours = (int) $this->option('hours');
        $threshold = now()->subHours($thresholdHours);

        $staleMain = $this->findStaleMainProjects($threshold);
        $stalePt2 = $this->findStalePt2Projects($threshold);

        if ($staleMain->isEmpty() && $stalePt2->isEmpty()) {
            $this->info('Tidak ada project yang stale (semua sudah update dalam ' . $thresholdHours . ' jam terakhir).');

            return self::SUCCESS;
        }

        $lines = [
            '🔔 <b>Reminder Project Belum Ada Update</b>',
            "Project berikut sudah di-assign namun tidak ada progress terbaru lebih dari {$thresholdHours} jam:",
            '',
        ];

        foreach ($staleMain as $row) {
            $lines[] = "• {$row['label']} — {$row['assignee']} ({$row['role']}) — update terakhir: {$row['last_activity']}";
        }

        foreach ($stalePt2 as $row) {
            $lines[] = "• {$row['label']} — {$row['assignee']} (teknisi, PT2) — update terakhir: {$row['last_activity']}";
        }

        $message = implode("\n", $lines);

        $sent = TelegramService::sendToRole('pm', $message);

        $this->info("Reminder terkirim ke {$sent} user PM. Total project stale: " . ($staleMain->count() + $stalePt2->count()));

        return self::SUCCESS;
    }

    protected function findStaleMainProjects(Carbon $threshold)
    {
        $assignments = ProjectAssignment::with(['project', 'waspang', 'teknisi'])
            ->where(function ($q) {
                $q->whereNotNull('waspang_id')->orWhereNotNull('teknisi_id');
            })
            ->get();

        $result = collect();

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

            if (! $lastUpdate || $lastUpdate->lte($threshold)) {
                $assignee = $assignment->waspang_id ? $assignment->waspang : $assignment->teknisi;

                $result->push([
                    'label' => ($project->project_name ?? '-') . ' (' . ($project->pid ?? '-') . ')',
                    'assignee' => $assignee->name ?? '-',
                    'role' => $assignment->waspang_id ? 'waspang' : 'teknisi',
                    'last_activity' => $lastUpdate ? $lastUpdate->translatedFormat('d M Y H:i') : 'belum ada aktivitas',
                ]);
            }
        }

        return $result;
    }

    protected function findStalePt2Projects(Carbon $threshold)
    {
        $assignments = Pt2Assignment::with(['lop.project', 'teknisi'])
            ->whereNotNull('teknisi_id')
            ->get();

        $result = collect();

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

            if (! $lastUpdate || $lastUpdate->lte($threshold)) {
                $projectName = $lop->project->project_name ?? ('PT2 Project #' . $lop->pt2_project_id);

                $result->push([
                    'label' => $projectName . ' — LOP ' . ($lop->lop_name ?? $lop->id_pt2_lop),
                    'assignee' => $assignment->teknisi->name ?? '-',
                    'last_activity' => $lastUpdate ? $lastUpdate->translatedFormat('d M Y H:i') : 'belum ada aktivitas',
                ]);
            }
        }

        return $result;
    }
}
