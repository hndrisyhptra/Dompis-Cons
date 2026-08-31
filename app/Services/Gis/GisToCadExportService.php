<?php

namespace App\Services\Gis;

use App\Models\GisCadExport;
use App\Models\SiteSurvey;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

/**
 * Orkestrator utama fitur "GIS to CAD Generator".
 *
 * Alur:
 * 1. ingestUpload() / ingestFromSurvey() -> parse sumber data, simpan
 *    dataset ternormalisasi (JSON) ke storage, buat 1 baris GisCadExport
 *    berstatus DRAFT.
 * 2. (UI Phase 3/4) User review/koreksi tipe titik yang confidence-nya
 *    'low' + pilih template -> updateDataset() kalau ada koreksi.
 * 3. confirmAndQueue() -> status jadi QUEUED, dispatch job ke queue
 *    'gis-cad'.
 * 4. Job memanggil process() -> status PROCESSING -> panggil
 *    PythonDxfWorkerService -> status COMPLETED (+ path DXF/BOM) atau
 *    FAILED (+ error_message, di-log).
 *
 * Semua baca tabel site_survey_* bersifat read-only (lihat
 * SiteSurveyDatasetService) - fitur survey existing tidak disentuh.
 */
class GisToCadExportService
{
    /**
     * Layer standar FTTx sesuai spesifikasi. Dipakai untuk validasi
     * template 'standard_fttx' & untuk BOM recap.
     */
    public const STANDARD_LAYERS = [
        'tiang' => 'FTTX_POLE',
        'odp' => 'FTTX_ODP',
        'odc' => 'FTTX_ODC',
        'otb' => 'FTTX_OTB',
        'jc' => 'FTTX_JC',
        'ending_site' => 'FTTX_ENDING_SITE',
        'cable' => 'FTTX_CABLE',
    ];

    public function __construct(
        private readonly KmlKmzReaderService $kmlReader,
        private readonly SiteSurveyDatasetService $surveyDataset,
        private readonly PythonDxfWorkerService $worker,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 1: INGEST (parse sumber data -> dataset draft)
    |--------------------------------------------------------------------------
    */

    public function ingestUpload(UploadedFile $file, User $user, ?int $projectId = null): GisCadExport
    {
        $dataset = $this->kmlReader->readUploadedFile($file);

        $uuid = (string) Str::uuid();
        $folder = 'gis-cad/' . $uuid;

        $originalExtension = strtolower($file->getClientOriginalExtension() ?: 'kml');
        $storedFileName = 'source.' . $originalExtension;
        $uploadedPath = $file->storeAs($folder, $storedFileName, 'public');

        $datasetPath = $this->storeDataset($folder, $dataset);

        return GisCadExport::create([
            'uuid' => $uuid,
            'source_type' => GisCadExport::SOURCE_KML_UPLOAD,
            'project_id' => $projectId,
            'template' => GisCadExport::TEMPLATE_STANDARD_FTTX,
            'original_file_name' => $file->getClientOriginalName(),
            'uploaded_file_path' => $uploadedPath,
            'dataset_path' => $datasetPath,
            'disk' => 'public',
            'status' => GisCadExport::STATUS_DRAFT,
            'current_stage' => 'Menunggu review klasifikasi titik',
            'points_count' => count($dataset['points']),
            'polylines_count' => count($dataset['polylines']),
            'requested_by' => $user->id_user,
        ]);
    }

    public function ingestFromSurvey(SiteSurvey $survey, User $user): GisCadExport
    {
        $dataset = $this->surveyDataset->build($survey);

        $uuid = (string) Str::uuid();
        $folder = 'gis-cad/' . $uuid;

        $datasetPath = $this->storeDataset($folder, $dataset);

        return GisCadExport::create([
            'uuid' => $uuid,
            'source_type' => GisCadExport::SOURCE_SITE_SURVEY,
            'site_survey_id' => $survey->id,
            'project_id' => $survey->project_id,
            'template' => GisCadExport::TEMPLATE_STANDARD_FTTX,
            'dataset_path' => $datasetPath,
            'disk' => 'public',
            'status' => GisCadExport::STATUS_DRAFT,
            'current_stage' => 'Menunggu konfirmasi template export',
            'points_count' => count($dataset['points']),
            'polylines_count' => count($dataset['polylines']),
            'requested_by' => $user->id_user,
        ]);
    }

    private function storeDataset(string $folder, array $dataset): string
    {
        $path = $folder . '/dataset.json';

        Storage::disk('public')->put(
            $path,
            json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $path;
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 2: REVIEW (baca / update dataset sebelum di-generate)
    |--------------------------------------------------------------------------
    */

    public function readDataset(GisCadExport $export): array
    {
        if (!$export->dataset_path || !Storage::disk($export->disk)->exists($export->dataset_path)) {
            throw new RuntimeException('Dataset untuk export ini tidak ditemukan (mungkin sudah dihapus).');
        }

        $raw = Storage::disk($export->disk)->get($export->dataset_path);
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Dataset export ini korup / tidak bisa dibaca.');
        }

        return $decoded;
    }

    /**
     * Simpan koreksi manual dari layar review (mis. user mengubah tipe
     * titik dari 'unknown' jadi 'odp', atau menghapus titik yang salah
     * ke-parse). Dipanggil oleh controller di Phase 3.
     *
     * @param  array<int, array{ref: string, type: string, name?: string}>  $pointOverrides
     * @param  string[]  $removeRefs  ref titik/polyline yang mau dibuang dari dataset
     */
    public function updateDataset(GisCadExport $export, array $pointOverrides = [], array $removeRefs = []): void
    {
        if (!$export->isDraft()) {
            throw new RuntimeException('Dataset hanya bisa diedit selama status masih draft.');
        }

        $dataset = $this->readDataset($export);

        $overridesByRef = collect($pointOverrides)->keyBy('ref');
        $removeSet = array_flip($removeRefs);

        $dataset['points'] = collect($dataset['points'] ?? [])
            ->reject(fn ($p) => isset($removeSet[$p['ref']]))
            ->map(function ($p) use ($overridesByRef) {
                $override = $overridesByRef->get($p['ref']);
                if ($override) {
                    $p['type'] = $override['type'] ?? $p['type'];
                    $p['name'] = $override['name'] ?? $p['name'];
                    $p['confidence'] = 'confirmed';
                }

                return $p;
            })
            ->values()
            ->all();

        $dataset['polylines'] = collect($dataset['polylines'] ?? [])
            ->reject(fn ($r) => isset($removeSet[$r['ref']]))
            ->values()
            ->all();

        $this->storeDataset(dirname($export->dataset_path), $dataset);

        $export->update([
            'points_count' => count($dataset['points']),
            'polylines_count' => count($dataset['polylines']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3: QUEUE (siap di-generate, dispatch job)
    |--------------------------------------------------------------------------
    */

    public function confirmAndQueue(GisCadExport $export, string $template = GisCadExport::TEMPLATE_STANDARD_FTTX): void
    {
        if (!$export->isDraft() && !$export->isFailed()) {
            throw new RuntimeException('Export ini sudah pernah di-generate / sedang diproses.');
        }

        $export->update([
            'template' => $template,
            'status' => GisCadExport::STATUS_QUEUED,
            'current_stage' => 'Menunggu antrean proses generate DXF',
            'error_message' => null,
        ]);

        \App\Jobs\ProcessGisCadExportJob::dispatch($export->id_gis_cad_export);
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 4: PROCESS (dipanggil dari dalam Job, boleh berjalan lama)
    |--------------------------------------------------------------------------
    */

    public function process(GisCadExport $export): void
    {
        $export->update([
            'status' => GisCadExport::STATUS_PROCESSING,
            'current_stage' => 'Transformasi koordinat & generate DXF',
            'started_at' => now(),
        ]);

        try {
            $dataset = $this->readDataset($export);
            $dataset['template'] = $export->template;

            $folder = dirname($export->dataset_path);
            $disk = Storage::disk($export->disk);
            $absoluteFolder = $disk->path($folder);

            // Worker Python butuh path absolut di filesystem, bukan path
            // relatif disk Laravel.
            $datasetForWorkerPath = $absoluteFolder . '/dataset_final.json';
            file_put_contents(
                $datasetForWorkerPath,
                json_encode($dataset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $dxfRelativePath = $folder . '/output.dxf';
            $dxfAbsolutePath = $absoluteFolder . '/output.dxf';

            $result = $this->worker->generate($datasetForWorkerPath, $dxfAbsolutePath, $export->template);

            $bomRelativePath = $this->buildBomExcel($dataset, $folder);

            $export->update([
                'status' => GisCadExport::STATUS_COMPLETED,
                'current_stage' => 'Selesai',
                'dxf_path' => $dxfRelativePath,
                'bom_path' => $bomRelativePath,
                'points_count' => $result['points_written'] ?? $export->points_count,
                'polylines_count' => $result['polylines_written'] ?? $export->polylines_count,
                'utm_zone' => $result['utm_zone'] ?? null,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('GisCadExport: proses generate gagal', [
                'id_gis_cad_export' => $export->id_gis_cad_export,
                'uuid' => $export->uuid,
                'error' => $e->getMessage(),
            ]);

            $export->update([
                'status' => GisCadExport::STATUS_FAILED,
                'current_stage' => 'Gagal generate DXF',
                'error_message' => mb_substr($e->getMessage(), 0, 65000),
                'finished_at' => now(),
            ]);

            // Dilempar ulang supaya job Laravel juga mencatat failure
            // (konsisten dengan pola ProcessPidImportJob/ProcessBoqImportJob).
            throw $e;
        }
    }

    /**
     * BOM sederhana: rekap jumlah object per layer. Bukan BOQ material
     * lengkap (di luar scope) - ini cuma rekap cepat sesuai "opsional jika
     * mudah" di requirement.
     */
    private function buildBomExcel(array $dataset, string $folder): string
    {
        $counts = [];

        foreach ($dataset['points'] ?? [] as $point) {
            $type = $point['type'] ?? 'unknown';
            $layer = self::STANDARD_LAYERS[$type] ?? ('FTTX_' . strtoupper($type));
            $counts[$layer] = ($counts[$layer] ?? 0) + 1;
        }

        $cableCount = count($dataset['polylines'] ?? []);
        $cableLength = collect($dataset['polylines'] ?? [])
            ->sum(fn ($line) => $this->polylineLengthMeters($line['coordinates'] ?? []));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BOM Ringkas');

        $sheet->fromArray(['No', 'Layer', 'Tipe Object', 'Jumlah', 'Satuan'], null, 'A1');

        $row = 2;
        $no = 1;
        foreach ($counts as $layer => $count) {
            $sheet->fromArray([$no, $layer, 'Point/Block', $count, 'unit'], null, 'A' . $row);
            $row++;
            $no++;
        }

        if ($cableCount > 0) {
            $sheet->fromArray([$no, self::STANDARD_LAYERS['cable'], 'Polyline (Jalur Kabel)', $cableCount, 'segmen'], null, 'A' . $row);
            $row++;
            $no++;
            $sheet->fromArray(['', self::STANDARD_LAYERS['cable'], 'Estimasi Panjang Kabel', round($cableLength, 1), 'meter'], null, 'A' . $row);
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = $folder . '/bom.xlsx';
        $absolutePath = Storage::disk('public')->path($path);

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);

        return $path;
    }

    private function polylineLengthMeters(array $coordinates): float
    {
        return \App\Models\SiteSurveyRoute::calculateDistanceMeters($coordinates);
    }
}
