<?php

namespace App\Jobs;

use App\Models\ImportProcess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class TestImportQueueJob implements ShouldQueue
{
    use Queueable;

    /*
    |--------------------------------------------------------------------------
    | RETRY / TIMEOUT TEST
    |--------------------------------------------------------------------------
    */

    public int $tries = 1;

    public int $timeout = 60;


    public function __construct(
        public int $importId
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | PROCESS
    |--------------------------------------------------------------------------
    */

    public function handle(): void
    {
        $import = ImportProcess::findOrFail(
            $this->importId
        );

        /*
        |--------------------------------------------------------------------------
        | START
        |--------------------------------------------------------------------------
        */

        $import->update([
            'status' => ImportProcess::STATUS_PROCESSING,

            'current_stage' =>
                'Background queue test sedang berjalan',

            'progress' => 25,

            'started_at' =>
                $import->started_at ?: now(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | SIMULASI PEKERJAAN BERAT
        |--------------------------------------------------------------------------
        |
        | Sengaja 5 detik untuk membuktikan proses
        | berjalan di worker, bukan request browser.
        |
        */

        sleep(5);


        /*
        |--------------------------------------------------------------------------
        | COMPLETE
        |--------------------------------------------------------------------------
        */

        $import->update([
            'status' =>
                ImportProcess::STATUS_COMPLETED,

            'current_stage' =>
                'Background queue test berhasil',

            'progress' => 100,

            'processed_rows' => 10,

            'valid_rows' => 10,

            'created_count' => 10,

            'summary' => [
                'test' => true,
                'message' =>
                    'Queue background berhasil diproses.',
            ],

            'finished_at' => now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FAILED
    |--------------------------------------------------------------------------
    */

    public function failed(
        ?Throwable $exception
    ): void {

        ImportProcess::where(
            'id_import',
            $this->importId
        )->update([
            'status' =>
                ImportProcess::STATUS_FAILED,

            'current_stage' =>
                'Background queue test gagal',

            'error_message' =>
                $exception?->getMessage()
                ?? 'Unknown queue error',

            'finished_at' => now(),
        ]);
    }
}