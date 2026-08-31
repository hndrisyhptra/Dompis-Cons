<?php

namespace App\Jobs;

use App\Models\GisCadExport;
use App\Services\Gis\GisToCadExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessGisCadExportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Sengaja 1 attempt saja (sama seperti ProcessPidImportJob) - kalau
     * generate DXF berhenti di tengah, retry otomatis bisa membuat file
     * output/BOM setengah jadi jadi membingungkan. User bisa klik
     * "Generate Ulang" secara manual dari UI (confirmAndQueue()).
     */
    public int $tries = 1;

    /**
     * Maksimal waktu proses 1 export. Harus SELALU >= config('services.gis_cad.timeout')
     * (timeout Python worker) + sedikit buffer, dan retry_after di config
     * queue juga harus lebih besar dari ini.
     */
    public int $timeout = 360;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $exportId
    ) {
        /**
         * Proses berat (parsing + transformasi koordinat + subprocess
         * Python) diarahkan ke queue khusus "gis-cad", terpisah dari queue
         * "imports" yang sudah ada supaya tidak saling antre.
         */
        $this->onQueue('gis-cad');
    }

    public function handle(GisToCadExportService $service): void
    {
        $export = GisCadExport::query()->findOrFail($this->exportId);

        if ($export->isFinished()) {
            return;
        }

        Log::info('ProcessGisCadExportJob started', [
            'id_gis_cad_export' => $export->id_gis_cad_export,
            'uuid' => $export->uuid,
            'source_type' => $export->source_type,
            'template' => $export->template,
        ]);

        $service->process($export);

        Log::info('ProcessGisCadExportJob completed', [
            'id_gis_cad_export' => $export->id_gis_cad_export,
            'uuid' => $export->uuid,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $message = $exception?->getMessage() ?? 'Queue job gagal tanpa detail exception.';

        GisCadExport::query()
            ->where('id_gis_cad_export', $this->exportId)
            ->where('status', '!=', GisCadExport::STATUS_COMPLETED)
            ->update([
                'status' => GisCadExport::STATUS_FAILED,
                'current_stage' => 'Background generate DXF gagal',
                'error_message' => mb_substr($message, 0, 65000),
                'finished_at' => now(),
            ]);

        Log::error('ProcessGisCadExportJob failed', [
            'id_gis_cad_export' => $this->exportId,
            'error' => $message,
        ]);
    }
}
