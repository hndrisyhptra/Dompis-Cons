<?php

namespace App\Jobs;

use App\Models\ImportProcess;
use App\Services\Imports\PidImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPidImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Untuk tahap awal kita sengaja hanya 1 attempt.
     *
     * Import sudah idempotent untuk data utama, tetapi retry otomatis
     * setelah proses berhenti di tengah dapat membuat statistik hasil
     * import / detail error menjadi membingungkan.
     *
     * Setelah mekanisme resume/retry manual dibuat, nilai ini dapat
     * dinaikkan dengan aman.
     */
    public int $tries = 1;

    /**
     * Maksimal waktu proses satu file.
     *
     * Pastikan retry_after pada connection queue SELALU lebih besar
     * daripada timeout ini.
     */
    public int $timeout = 900;

    /**
     * Jika worker timeout, tandai job sebagai failed.
     */
    public bool $failOnTimeout = true;

    public function __construct(
        public int $importId
    ) {
        /**
         * Semua import berat diarahkan ke queue khusus "imports".
         *
         * Jadi saat dispatch cukup:
         * ProcessPidImportJob::dispatch($id);
         */
        $this->onQueue('imports');
    }

    /**
     * Execute queued import.
     */
    public function handle(PidImportService $service): void
    {
        $import = ImportProcess::query()
            ->findOrFail($this->importId);

        /**
         * Guard:
         * kalau job ter-dispatch ganda setelah import sudah selesai /
         * dibatalkan, jangan proses file lagi.
         */
        if (in_array(
            $import->status,
            [
                ImportProcess::STATUS_COMPLETED,
                ImportProcess::STATUS_CANCELLED,
            ],
            true
        )) {
            return;
        }

        Log::info('ProcessPidImportJob started', [
            'id_import' => $import->id_import,
            'uuid' => $import->uuid,
            'file' => $import->original_file_name,
            'project_type' => $import->project_type,
        ]);

        /**
         * Seluruh business logic ada di service.
         * Exception dari service sengaja dibiarkan naik ke queue supaya
         * Laravel mencatat job sebagai failed.
         */
        $service->process($import);

        Log::info('ProcessPidImportJob completed', [
            'id_import' => $import->id_import,
            'uuid' => $import->uuid,
        ]);
    }

    /**
     * Dipanggil Laravel saat job dinyatakan gagal.
     */
    public function failed(?Throwable $exception): void
    {
        $message = $exception?->getMessage()
            ?? 'Queue job gagal tanpa detail exception.';

        ImportProcess::query()
            ->where('id_import', $this->importId)
            ->whereNotIn('status', [
                ImportProcess::STATUS_COMPLETED,
                ImportProcess::STATUS_CANCELLED,
            ])
            ->update([
                'status' => ImportProcess::STATUS_FAILED,
                'current_stage' => 'Background import PID gagal',
                'error_message' => mb_substr($message, 0, 65000),
                'finished_at' => now(),
            ]);

        Log::error('ProcessPidImportJob failed', [
            'id_import' => $this->importId,
            'error' => $message,
        ]);
    }
}