<?php

namespace App\Services\Imports;

use App\Models\BoqItem;
use App\Models\Designator;
use App\Models\DesignatorPackagePrice;
use App\Models\ImportProcess;
use App\Models\ImportProcessError;
use App\Models\Lop;
use App\Models\Package as PackageModel;
use App\Models\Project;
use App\Models\Pt2BoqItem;
use App\Models\Pt2Lop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class BoqImportService
{
    private const CHUNK_SIZE = 250;

    private array $designatorCache = [];
    private array $priceCache = [];

    public function process(ImportProcess $import): void
    {
        if ($import->import_type !== ImportProcess::TYPE_BOQ) {
            throw new RuntimeException('Import process bukan tipe BOQ.');
        }

        $options = (array) data_get($import->summary, 'options', []);
        $packageId = (int) ($options['package_id'] ?? 0);
        $mappingBy = (string) ($options['mapping_by'] ?? '');

        if (!$packageId || !in_array($mappingBy, ['pid', 'id_ihld', 'lop_name'], true)) {
            throw new RuntimeException('Metadata import BOQ tidak lengkap.');
        }

        if ($import->project_type === 'pt2' && $mappingBy === 'pid') {
            throw new RuntimeException('Mapping BOQ PT2 berdasarkan PID tidak diizinkan.');
        }

        $disk = $import->disk ?: 'local';

        if (!Storage::disk($disk)->exists($import->stored_file_path)) {
            throw new RuntimeException('File BOQ upload tidak ditemukan.');
        }

        $path = Storage::disk($disk)->path($import->stored_file_path);

        $package = PackageModel::query()
            ->where('id_package', $packageId)
            ->where('customer_id', $import->customer_id)
            ->first();

        if (!$package) {
            throw new RuntimeException('Package tidak valid atau tidak sesuai dengan customer.');
        }

        $import->update([
            'status' => ImportProcess::STATUS_PROCESSING,
            'current_stage' => 'Membaca metadata file BOQ',
            'progress' => 2,
            'started_at' => $import->started_at ?: now(),
            'error_message' => null,
        ]);

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $worksheetInfo = $reader->listWorksheetInfo($path);

        if (empty($worksheetInfo)) {
            throw new RuntimeException('Workbook BOQ tidak memiliki worksheet.');
        }

        $sheetInfo = $worksheetInfo[0];
        $sheetName = trim((string) ($sheetInfo['worksheetName'] ?? ''));
        $highestRow = (int) ($sheetInfo['totalRows'] ?? 0);
        $highestColumnIndex = (int) ($sheetInfo['totalColumns'] ?? 0);

        if ($highestRow < 2 || $highestColumnIndex < 2) {
            throw new RuntimeException('Format BOQ tidak valid. Minimal harus memiliki header row 1 dan data designator mulai row 2.');
        }

        $totalDataRows = max($highestRow - 1, 0);

        $headerReader = IOFactory::createReaderForFile($path);
        $headerReader->setReadDataOnly(true);
        $headerReader->setReadEmptyCells(false);
        $headerReader->setReadFilter(new BoqChunkReadFilter(1, 1));

        $headerSpreadsheet = $headerReader->load($path);
        $headerSheet = $headerSpreadsheet->getActiveSheet();

        $headerMappings = [];
        $headerErrors = [];
        $matchedHeaders = [];
        $unmatchedHeaders = [];
        $existingHeaders = [];

        $matchedLop = 0;
        $unmappedLop = 0;
        $existingBoqHeaders = 0;
        $packageAssigned = 0;
        $packageConflict = 0;
        $totalHeaders = 0;

        for ($col = 2; $col <= $highestColumnIndex; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $headerValue = trim((string) $headerSheet->getCell($columnLetter . '1')->getValue());

            if ($headerValue === '') {
                continue;
            }

            $totalHeaders++;

            $resolved = $this->resolveLop(
                $import->project_type,
                (int) $import->customer_id,
                $mappingBy,
                $headerValue
            );

            if (!$resolved['lop']) {
                $unmappedLop++;
                $unmatchedHeaders[] = $headerValue;

                $headerMappings[$columnLetter] = [
                    'header' => $headerValue,
                    'lop' => null,
                    'blocked' => true,
                ];

                $headerErrors[] = $this->makeErrorRow(
                    $import,
                    1,
                    $headerValue,
                    null,
                    $resolved['code'] ?? 'LOP_NOT_FOUND',
                    $resolved['error'] ?? 'LOP tidak ditemukan.',
                    [
                        'type' => 'header',
                        'header' => $headerValue,
                        'mapping_by' => $mappingBy,
                    ]
                );

                continue;
            }

            $lop = $resolved['lop'];

            if ($lop->package_id && (int) $lop->package_id !== $packageId) {
                $packageConflict++;
                $unmatchedHeaders[] = $headerValue;

                $headerMappings[$columnLetter] = [
                    'header' => $headerValue,
                    'lop' => $lop,
                    'blocked' => true,
                ];

                $headerErrors[] = $this->makeErrorRow(
                    $import,
                    1,
                    $headerValue,
                    $lop,
                    'PACKAGE_CONFLICT',
                    "LOP sudah menggunakan package ID {$lop->package_id}, berbeda dari package upload ID {$packageId}.",
                    [
                        'type' => 'header',
                        'header' => $headerValue,
                        'mapping_by' => $mappingBy,
                        'selected_package_id' => $packageId,
                        'existing_package_id' => (int) $lop->package_id,
                    ]
                );

                continue;
            }

            if (!$lop->package_id) {
                $lop->update(['package_id' => $packageId]);
                $packageAssigned++;
                $lop->package_id = $packageId;
            }

            $matchedLop++;
            $matchedHeaders[] = $headerValue;

            $hasExistingBoq = $import->project_type === 'pt2'
                ? Pt2BoqItem::query()->where('pt2_lop_id', $lop->id_pt2_lop)->exists()
                : BoqItem::query()->where('lop_id', $lop->id_lop)->exists();

            if ($hasExistingBoq) {
                $existingBoqHeaders++;
                $existingHeaders[] = $headerValue;
            }

            $headerMappings[$columnLetter] = [
                'header' => $headerValue,
                'lop' => $lop,
                'blocked' => false,
            ];
        }

        $headerSpreadsheet->disconnectWorksheets();
        unset($headerSpreadsheet);

        if ($headerErrors) {
            ImportProcessError::query()->insert($headerErrors);
        }

        $import->update([
            'total_rows' => $totalDataRows,
            'current_stage' => 'Header BOQ selesai dimapping',
            'progress' => 10,
        ]);

        $createdCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $skippedCount = 0;
        $validCount = 0;
        $invalidCount = count($headerErrors);
        $volumeItems = 0;
        $unmappedDesignator = 0;
        $priceMissing = 0;
        $processedRows = 0;

        for ($startRow = 2; $startRow <= $highestRow; $startRow += self::CHUNK_SIZE) {
            $endRow = min($startRow + self::CHUNK_SIZE - 1, $highestRow);

            $chunkReader = IOFactory::createReaderForFile($path);
            $chunkReader->setReadDataOnly(true);
            $chunkReader->setReadEmptyCells(false);
            $chunkReader->setReadFilter(new BoqChunkReadFilter($startRow, $endRow));

            $spreadsheet = $chunkReader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $chunkErrors = [];

            DB::transaction(function () use (
                $sheet,
                $startRow,
                $endRow,
                $highestColumnIndex,
                $headerMappings,
                $import,
                $packageId,
                &$createdCount,
                &$updatedCount,
                &$unchangedCount,
                &$skippedCount,
                &$validCount,
                &$invalidCount,
                &$volumeItems,
                &$unmappedDesignator,
                &$priceMissing,
                &$chunkErrors
            ) {
                for ($row = $startRow; $row <= $endRow; $row++) {
                    $baseDesignator = strtoupper(trim((string) $sheet->getCell('A' . $row)->getValue()));

                    for ($col = 2; $col <= $highestColumnIndex; $col++) {
                        $columnLetter = Coordinate::stringFromColumnIndex($col);

                        if (!isset($headerMappings[$columnLetter])) {
                            continue;
                        }

                        $mapping = $headerMappings[$columnLetter];
                        $qtyRaw = $sheet->getCell($columnLetter . $row)->getCalculatedValue();

                        if ($qtyRaw === null || $qtyRaw === '' || !is_numeric($qtyRaw)) {
                            continue;
                        }

                        $qty = (float) $qtyRaw;

                        if ($qty <= 0) {
                            continue;
                        }

                        $volumeItems++;

                        if ($mapping['blocked'] || !$mapping['lop']) {
                            $skippedCount++;
                            continue;
                        }

                        $lop = $mapping['lop'];

                        if ($baseDesignator === '') {
                            $invalidCount++;
                            $skippedCount++;
                            $chunkErrors[] = $this->makeErrorRow(
                                $import,
                                $row,
                                $mapping['header'],
                                $lop,
                                'DESIGNATOR_EMPTY',
                                'Designator pada kolom A kosong.',
                                [
                                    'type' => 'item',
                                    'header' => $mapping['header'],
                                    'designator' => null,
                                    'qty' => $qty,
                                ]
                            );
                            continue;
                        }

                        if ($import->project_type !== 'pt2' && floor($qty) != $qty) {
                            $invalidCount++;
                            $skippedCount++;
                            $chunkErrors[] = $this->makeErrorRow(
                                $import,
                                $row,
                                $mapping['header'],
                                $lop,
                                'REGULAR_QTY_DECIMAL',
                                "Quantity Regular harus bilangan bulat. Nilai ditemukan: {$qty}.",
                                [
                                    'type' => 'item',
                                    'header' => $mapping['header'],
                                    'designator' => $baseDesignator,
                                    'qty' => $qty,
                                ]
                            );
                            continue;
                        }

                        $projectCustomerId = $import->project_type === 'pt2'
                            ? 1
                            : (int) Project::query()
                                ->where('id_project', $lop->project_id)
                                ->value('customer_id');

                        $designators = $this->findDesignators($projectCustomerId, $baseDesignator);

                        if ($designators->isEmpty()) {
                            $unmappedDesignator++;
                            $invalidCount++;
                            $skippedCount++;
                            $chunkErrors[] = $this->makeErrorRow(
                                $import,
                                $row,
                                $mapping['header'],
                                $lop,
                                'DESIGNATOR_NOT_FOUND',
                                "Designator/pair code '{$baseDesignator}' tidak ditemukan.",
                                [
                                    'type' => 'item',
                                    'header' => $mapping['header'],
                                    'designator' => $baseDesignator,
                                    'qty' => $qty,
                                ]
                            );
                            continue;
                        }

                        $validCount++;

                        foreach ($designators as $designator) {
                            $price = $this->latestPrice((int) $designator->id_designator, $packageId);
                            $unitPrice = $price ? (float) $price->price : 0.0;

                            if (!$price) {
                                $priceMissing++;
                            }

                            $totalPrice = $qty * $unitPrice;

                            $created = $this->createBoqIfMissing(
                                $import->project_type,
                                $lop,
                                $designator,
                                $qty,
                                $unitPrice,
                                $totalPrice
                            );

                            if ($created) {
                                $createdCount++;
                            } else {
                                $unchangedCount++;
                            }
                        }
                    }
                }
            });

            if ($chunkErrors) {
                foreach (array_chunk($chunkErrors, 500) as $errorBatch) {
                    ImportProcessError::query()->insert($errorBatch);
                }
            }

            $processedRows = min($endRow - 1, $totalDataRows);
            $progress = $totalDataRows > 0
                ? 10 + (int) floor(($processedRows / $totalDataRows) * 85)
                : 95;

            $import->update([
                'current_stage' => "Memproses BOQ row {$startRow}-{$endRow}",
                'progress' => min(95, $progress),
                'processed_rows' => $processedRows,
                'valid_rows' => $validCount,
                'invalid_rows' => $invalidCount,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'unchanged_count' => $unchangedCount,
                'skipped_count' => $skippedCount,
            ]);

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        $summary = [
            'options' => [
                'package_id' => $packageId,
                'package_code' => $package->package_code,
                'package_name' => $package->package_name,
                'mapping_by' => $mappingBy,
            ],
            'sheet_name' => $sheetName,
            'total_headers' => $totalHeaders,
            'matched_lop' => $matchedLop,
            'unmapped_lop' => $unmappedLop,
            'existing_boq_headers' => $existingBoqHeaders,
            'volume_items' => $volumeItems,
            'boq_created' => $createdCount,
            'boq_unchanged' => $unchangedCount,
            'unmapped_designator' => $unmappedDesignator,
            'price_missing' => $priceMissing,
            'package_assigned' => $packageAssigned,
            'package_conflict' => $packageConflict,
            'matched_headers' => array_values($matchedHeaders),
            'unmatched_headers' => array_values($unmatchedHeaders),
            'existing_headers' => array_values($existingHeaders),
        ];

        $import->update([
            'status' => ImportProcess::STATUS_COMPLETED,
            'current_stage' => 'Import BOQ selesai',
            'progress' => 100,
            'processed_rows' => $totalDataRows,
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'unchanged_count' => $unchangedCount,
            'skipped_count' => $skippedCount,
            'summary' => $summary,
            'error_message' => null,
            'finished_at' => now(),
        ]);
    }

    private function resolveLop(string $projectType, int $customerId, string $mappingBy, string $headerValue): array
    {
        if ($projectType === 'pt2') {
            $query = Pt2Lop::query()
                ->select('pt2_lops.*')
                ->join('pt2_projects as pt2p', 'pt2p.id_pt2_project', '=', 'pt2_lops.pt2_project_id')
                ->where('pt2p.customer_id', $customerId);

            if ($mappingBy === 'id_ihld') {
                $query->where('pt2_lops.id_ihld', $headerValue);
            } elseif ($mappingBy === 'lop_name') {
                $query->whereRaw('LOWER(TRIM(pt2_lops.lop_name)) = ?', [mb_strtolower(trim($headerValue))]);
            } else {
                return ['lop' => null, 'code' => 'PT2_PID_MAPPING_NOT_ALLOWED', 'error' => 'Mapping PT2 berdasarkan PID tidak diizinkan.'];
            }

            $matches = $query->limit(2)->get();

            if ($matches->isEmpty()) {
                return ['lop' => null, 'code' => 'LOP_NOT_FOUND', 'error' => "LOP PT2 '{$headerValue}' tidak ditemukan."];
            }

            if ($matches->count() > 1) {
                return ['lop' => null, 'code' => 'LOP_AMBIGUOUS', 'error' => "LOP PT2 '{$headerValue}' ditemukan lebih dari satu. Gunakan ID IHLD."];
            }

            return ['lop' => $matches->first(), 'code' => null, 'error' => null];
        }

        if ($mappingBy === 'pid') {
            $projects = Project::query()
                ->where('customer_id', $customerId)
                ->where(function ($q) use ($headerValue) {
                    $q->where('pid', $headerValue)->orWhere('pid_sap', $headerValue);
                })
                ->limit(2)
                ->get();

            if ($projects->count() !== 1) {
                return [
                    'lop' => null,
                    'code' => $projects->isEmpty() ? 'PROJECT_NOT_FOUND' : 'PROJECT_AMBIGUOUS',
                    'error' => $projects->isEmpty()
                        ? "Project '{$headerValue}' tidak ditemukan."
                        : "Project '{$headerValue}' ditemukan lebih dari satu.",
                ];
            }

            $lops = Lop::query()
                ->where('project_id', $projects->first()->id_project)
                ->limit(2)
                ->get();

            if ($lops->count() !== 1) {
                return [
                    'lop' => null,
                    'code' => $lops->isEmpty() ? 'LOP_NOT_FOUND' : 'LOP_AMBIGUOUS',
                    'error' => $lops->isEmpty()
                        ? "LOP untuk project '{$headerValue}' tidak ditemukan."
                        : "Project '{$headerValue}' memiliki lebih dari satu LOP.",
                ];
            }

            return ['lop' => $lops->first(), 'code' => null, 'error' => null];
        }

        $query = Lop::query()
            ->select('lops.*')
            ->join('projects as p', 'p.id_project', '=', 'lops.project_id')
            ->where('p.customer_id', $customerId);

        if ($mappingBy === 'id_ihld') {
            $query->where('lops.id_ihld', $headerValue);
        } else {
            $query->whereRaw('LOWER(TRIM(lops.lop_name)) = ?', [mb_strtolower(trim($headerValue))]);
        }

        $matches = $query->limit(2)->get();

        if ($matches->isEmpty()) {
            return ['lop' => null, 'code' => 'LOP_NOT_FOUND', 'error' => "LOP '{$headerValue}' tidak ditemukan."];
        }

        if ($matches->count() > 1) {
            return ['lop' => null, 'code' => 'LOP_AMBIGUOUS', 'error' => "LOP '{$headerValue}' ditemukan lebih dari satu. Gunakan ID IHLD."];
        }

        return ['lop' => $matches->first(), 'code' => null, 'error' => null];
    }

    private function findDesignators(int $customerId, string $baseDesignator)
    {
        $key = $customerId . '|' . $baseDesignator;

        if (!array_key_exists($key, $this->designatorCache)) {
            $this->designatorCache[$key] = Designator::query()
                ->where('customer_id', $customerId)
                ->where(function ($query) use ($baseDesignator) {
                    $query->where('pair_code', $baseDesignator)
                        ->orWhere('designator', $baseDesignator);
                })
                ->get();
        }

        return $this->designatorCache[$key];
    }

    private function latestPrice(int $designatorId, int $packageId)
    {
        $key = $designatorId . '|' . $packageId;

        if (!array_key_exists($key, $this->priceCache)) {
            $this->priceCache[$key] = DesignatorPackagePrice::query()
                ->where('designator_id', $designatorId)
                ->where('package_id', $packageId)
                ->orderByDesc('id_price')
                ->first();
        }

        return $this->priceCache[$key];
    }

    private function createBoqIfMissing(string $projectType, $lop, $designator, float $qty, float $unitPrice, float $totalPrice): bool
    {
        if ($projectType === 'pt2') {
            $existing = Pt2BoqItem::query()
                ->where('pt2_lop_id', $lop->id_pt2_lop)
                ->where(function ($q) use ($designator) {
                    $q->where('designator_id', $designator->id_designator)
                        ->orWhere('designator', $designator->designator);
                })
                ->first();

            if ($existing) {
                return false;
            }

            try {
                Pt2BoqItem::query()->create([
                    'pt2_project_id' => $lop->pt2_project_id,
                    'pt2_lop_id' => $lop->id_pt2_lop,
                    'designator_id' => $designator->id_designator,
                    'designator' => $designator->designator,
                    'item_name' => $designator->item_name,
                    'unit' => $designator->unit,
                    'quantity_plan' => $qty,
                    'quantity_actual' => 0,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);

                return true;
            } catch (QueryException $e) {
                if ($this->isDuplicateKey($e)) {
                    return false;
                }
                throw $e;
            }
        }

        $existing = BoqItem::query()
            ->where('lop_id', $lop->id_lop)
            ->where(function ($q) use ($designator) {
                $q->where('designator_id', $designator->id_designator)
                    ->orWhere('designator', $designator->designator);
            })
            ->first();

        if ($existing) {
            return false;
        }

        try {
            BoqItem::query()->create([
                'project_id' => $lop->project_id,
                'lop_id' => $lop->id_lop,
                'designator_id' => $designator->id_designator,
                'designator' => $designator->designator,
                'item_name' => $designator->item_name,
                'unit' => $designator->unit,
                'quantity_plan' => (int) $qty,
                'quantity_actual' => 0,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);

            return true;
        } catch (QueryException $e) {
            if ($this->isDuplicateKey($e)) {
                return false;
            }
            throw $e;
        }
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        return (string) $e->getCode() === '23000'
            || str_contains(strtolower($e->getMessage()), 'duplicate');
    }

    private function makeErrorRow(
        ImportProcess $import,
        ?int $rowNumber,
        string $headerValue,
        $lop,
        string $errorCode,
        string $message,
        array $rowData
    ): array {
        return [
            'import_process_id' => $import->id_import,
            'row_number' => $rowNumber,
            'pid_sap' => $lop?->pid_sap ?? $headerValue,
            'id_ihld' => $lop?->id_ihld,
            'nama_lop' => $lop?->lop_name,
            'error_code' => $errorCode,
            'message' => $message,
            'row_data' => json_encode($rowData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ];
    }
}