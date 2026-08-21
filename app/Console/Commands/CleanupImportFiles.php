<?php

namespace App\Console\Commands;

use App\Models\ImportProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupImportFiles extends Command
{
    protected $signature = 'imports:cleanup 
                            {--days=30}
                            {--dry-run}';

    protected $description = 'Cleanup old import files PID and BOQ';

    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $expiredDate = Carbon::now()->subDays($days);

        $processes = ImportProcess::query()
            ->whereIn('status', [
                ImportProcess::STATUS_COMPLETED,
                ImportProcess::STATUS_FAILED,
                ImportProcess::STATUS_CANCELLED,
            ])
            ->whereNotNull('stored_file_path')
            ->whereNotNull('finished_at')
            ->where('finished_at', '<', $expiredDate)
            ->get();


        if ($processes->isEmpty()) {

            $this->info('Tidak ada file import yang perlu dibersihkan.');

            return Command::SUCCESS;
        }


        $deleted = 0;


        foreach ($processes as $process) {

            $disk = $process->disk ?: config('filesystems.default');


            if (!Storage::disk($disk)
                ->exists($process->stored_file_path)) {

                continue;
            }


            if ($dryRun) {

                $this->line(
                    '[DRY RUN] ' .
                    $process->stored_file_path
                );

                continue;
            }


            Storage::disk($disk)
                ->delete($process->stored_file_path);


            $deleted++;
        }


        if ($dryRun) {

            $this->info(
                "Dry run selesai. File ditemukan: {$processes->count()}"
            );

        } else {

            $this->info(
                "Cleanup selesai. File terhapus: {$deleted}"
            );

        }


        return Command::SUCCESS;
    }
}