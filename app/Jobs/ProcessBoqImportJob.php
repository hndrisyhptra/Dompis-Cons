<?php

namespace App\Jobs;

use App\Models\ImportProcess;
use App\Services\Imports\BoqImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBoqImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $importId
    ) {
        $this->onQueue('imports');
    }

    public function handle(
        BoqImportService $service
    ): void {
        $import = ImportProcess::query()
            ->findOrFail($this->importId);

        if (
            $import->import_type !== ImportProcess::TYPE_BOQ
        ) {
            Log::warning(
                'ProcessBoqImportJob dilewati karena import_type bukan BOQ.',
                [
                    'import_id' => $this->importId,
                    'import_type' => $import->import_type,
                ]
            );

            return;
        }

        if (
            in_array(
                $import->status,
                [
                    ImportProcess::STATUS_COMPLETED,
                    ImportProcess::STATUS_CANCELLED,
                ],
                true
            )
        ) {
            return;
        }

        Log::info(
            'Background import BOQ dimulai.',
            [
                'import_id' => $import->id_import,
                'uuid' => $import->uuid,
                'file' => $import->original_file_name,
            ]
        );

        $service->process($import);

        Log::info(
            'Background import BOQ selesai.',
            [
                'import_id' => $import->id_import,
                'uuid' => $import->uuid,
            ]
        );
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $message = $exception?->getMessage()
            ?? 'Queue job BOQ gagal tanpa detail exception.';

        ImportProcess::query()
            ->where(
                'id_import',
                $this->importId
            )
            ->whereNotIn(
                'status',
                [
                    ImportProcess::STATUS_COMPLETED,
                    ImportProcess::STATUS_CANCELLED,
                ]
            )
            ->update([
                'status' => ImportProcess::STATUS_FAILED,
                'current_stage' => 'Background import BOQ gagal',
                'error_message' => mb_substr(
                    $message,
                    0,
                    65000
                ),
                'finished_at' => now(),
            ]);

        Log::error(
            'ProcessBoqImportJob gagal.',
            [
                'import_id' => $this->importId,
                'message' => $message,
            ]
        );
    }
}