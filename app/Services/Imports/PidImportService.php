<?php

namespace App\Services\Imports;

use App\Models\ImportLog;
use App\Models\ImportProcess;
use App\Models\ImportProcessError;
use App\Models\Lop;
use App\Models\Project;
use App\Models\Pt2Lop;
use App\Models\Pt2Project;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class PidImportService
{
    private const CHUNK_SIZE = 500;

    /**
     * Proses satu file PID berdasarkan record import_processes.
     *
     * Method ini TIDAK bergantung pada Request / Session / Auth.
     * Nanti Job cukup memanggil method ini.
     */
    public function process(ImportProcess $import): array
    {
        if ($import->import_type !== ImportProcess::TYPE_PID) {
            throw new RuntimeException('Import process bukan tipe PID.');
        }

        $isPt2 = $import->project_type === 'pt2';
        $customerId = $isPt2 ? 1 : $import->customer_id;

        if (!$isPt2 && !$customerId) {
            return $this->failValidation(
                $import,
                'Customer wajib tersedia untuk import PID Regular/External.'
            );
        }

        $disk = $import->disk ?: 'local';

        if (!Storage::disk($disk)->exists($import->stored_file_path)) {
            throw new RuntimeException(
                'File import tidak ditemukan: ' . $import->stored_file_path
            );
        }

        $filePath = Storage::disk($disk)->path($import->stored_file_path);

        $counters = [
            'project_created' => 0,
            'project_updated' => 0,
            'lop_created' => 0,
            'lop_updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
        ];

        // Tracker tetap hidup lintas chunk supaya duplicate dalam file tetap terdeteksi.
        $seenKeys = [];

        try {
            $import->update([
                'status' => ImportProcess::STATUS_PROCESSING,
                'current_stage' => 'Membaca informasi spreadsheet',
                'progress' => 2,
                'error_message' => null,
                'started_at' => $import->started_at ?: now(),
                'finished_at' => null,
            ]);

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);

            // Tidak perlu load seluruh workbook untuk mengetahui dimensi worksheet.
            $worksheetInfo = $reader->listWorksheetInfo($filePath);
            $activeInfo = $worksheetInfo[0] ?? null;

            if (!$activeInfo) {
                return $this->failValidation(
                    $import,
                    'Spreadsheet tidak memiliki worksheet yang dapat dibaca.'
                );
            }

            $highestRow = (int) ($activeInfo['totalRows'] ?? 0);
            $highestColumn = (string) ($activeInfo['lastColumnLetter'] ?? 'A');
            $totalRows = max($highestRow - 1, 0);

            $import->update([
                'total_rows' => $totalRows,
                'current_stage' => 'Validasi header',
                'progress' => 5,
            ]);

            if ($highestRow < 2) {
                return $this->failValidation(
                    $import,
                    'Spreadsheet tidak memiliki data untuk diimport.'
                );
            }

            $headers = $this->readHeaders(
                $reader,
                $filePath,
                $highestColumn
            );

            $requiredHeaders = ['pid_sap', 'id_ihld', 'nama_lop'];
            $missingHeaders = array_values(
                array_diff($requiredHeaders, array_values($headers))
            );

            if (!empty($missingHeaders)) {
                ImportProcessError::create([
                    'import_process_id' => $import->id_import,
                    'error_code' => 'missing_headers',
                    'message' => 'Header wajib tidak ditemukan: ' . implode(', ', $missingHeaders),
                    'row_data' => [
                        'missing_headers' => $missingHeaders,
                    ],
                ]);

                return $this->failValidation(
                    $import,
                    'Header wajib tidak ditemukan: ' . implode(', ', $missingHeaders)
                );
            }

            $filter = new PidChunkReadFilter();
            $reader->setReadFilter($filter);

            for ($startRow = 2; $startRow <= $highestRow; $startRow += self::CHUNK_SIZE) {
                $endRow = min(
                    $startRow + self::CHUNK_SIZE - 1,
                    $highestRow
                );

                $filter->setRows($startRow, self::CHUNK_SIZE);

                $spreadsheet = $reader->load($filePath);
                $sheet = $spreadsheet->getActiveSheet();

                $validChunkRows = [];
                $errorsToInsert = [];

                for ($rowNumber = $startRow; $rowNumber <= $endRow; $rowNumber++) {
                    $rawRow = [];

                    foreach ($headers as $columnLetter => $headerName) {
                        $rawRow[$headerName] = $sheet
                            ->getCell($columnLetter . $rowNumber)
                            ->getValue();
                    }

                    // Abaikan row yang benar-benar kosong.
                    if ($this->isEmptyRow($rawRow)) {
                        continue;
                    }

                    $parsed = $this->parseRow(
                        $rawRow,
                        $rowNumber,
                        $isPt2,
                        $seenKeys
                    );

                    if (!empty($parsed['errors'])) {
                        $counters['invalid_rows']++;
                        $counters['skipped']++;

                        $errorsToInsert[] = [
                            'import_process_id' => $import->id_import,
                            'row_number' => $rowNumber,
                            'pid_sap' => $parsed['row']['pid_sap'] ?? null,
                            'id_ihld' => $parsed['row']['id_ihld'] ?? null,
                            'nama_lop' => $parsed['row']['nama_lop'] ?? null,
                            'error_code' => $parsed['error_code'] ?? 'invalid_data',
                            'message' => implode(', ', $parsed['errors']),
                            'row_data' => json_encode(
                                $this->jsonSafeRow($rawRow),
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            ),
                            'created_at' => now(),
                        ];

                        continue;
                    }

                    $counters['valid_rows']++;
                    $validChunkRows[] = $parsed['row'];
                }

                if (!empty($errorsToInsert)) {
                    DB::table('import_processes_errors')->insert($errorsToInsert);
                }

                if (!empty($validChunkRows)) {
                    DB::transaction(function () use (
                        $validChunkRows,
                        $isPt2,
                        $customerId,
                        &$counters
                    ) {
                        $this->persistChunk(
                            $validChunkRows,
                            $isPt2,
                            (int) $customerId,
                            $counters
                        );
                    }, 3);
                }

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $sheet);

                $processedRows = min($endRow - 1, $totalRows);

                // Sisakan 5% terakhir untuk finalizing.
                $progress = $totalRows > 0
                    ? min(95, 10 + (int) floor(($processedRows / $totalRows) * 85))
                    : 95;

                $this->updateImportProgress(
                    $import,
                    $counters,
                    $processedRows,
                    $progress,
                    "Memproses row {$processedRows} / {$totalRows}"
                );

                // Bersihkan cycle/unused objects dari PhpSpreadsheet per chunk.
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            $import->update([
                'current_stage' => 'Finalisasi hasil import',
                'progress' => 98,
            ]);

            $summary = [
                'project_created' => $counters['project_created'],
                'project_updated' => $counters['project_updated'],
                'lop_created' => $counters['lop_created'],
                'lop_updated' => $counters['lop_updated'],
                'unchanged' => $counters['unchanged'],
                'skipped' => $counters['skipped'],
            ];

            $totalCreated = $counters['project_created'] + $counters['lop_created'];
            $totalUpdated = $counters['project_updated'] + $counters['lop_updated'];

            $import->update([
                'status' => ImportProcess::STATUS_COMPLETED,
                'current_stage' => 'Import PID selesai',
                'progress' => 100,
                'processed_rows' => $totalRows,
                'valid_rows' => $counters['valid_rows'],
                'invalid_rows' => $counters['invalid_rows'],
                'created_count' => $totalCreated,
                'updated_count' => $totalUpdated,
                'unchanged_count' => $counters['unchanged'],
                'skipped_count' => $counters['skipped'],
                'summary' => $summary,
                'error_message' => null,
                'finished_at' => now(),
            ]);

            // Pertahankan tabel history existing untuk kompatibilitas halaman lama.
            ImportLog::create([
                'type' => 'pid',
                'file_name' => $import->original_file_name,
                'uploaded_by' => $import->uploaded_by,
                'total_rows' => $totalRows,
                'imported' => $totalCreated,
                'updated' => $totalUpdated,
                'skipped' => $counters['skipped'],
                'status' => 'success',
            ]);

            return [
                'success' => true,
                'status' => 'completed',
                'total_rows' => $totalRows,
                'valid_rows' => $counters['valid_rows'],
                'invalid_rows' => $counters['invalid_rows'],
                'created_count' => $totalCreated,
                'updated_count' => $totalUpdated,
                'unchanged_count' => $counters['unchanged'],
                'skipped_count' => $counters['skipped'],
                'summary' => $summary,
            ];
        } catch (Throwable $e) {
            Log::error('PidImportService gagal', [
                'id_import' => $import->id_import,
                'file' => $import->original_file_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $import->update([
                'status' => ImportProcess::STATUS_FAILED,
                'current_stage' => 'Import PID gagal',
                'error_message' => mb_substr($e->getMessage(), 0, 65000),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    private function persistChunk(
        array $rows,
        bool $isPt2,
        int $customerId,
        array &$counters
    ): void {
        $projectClass = $isPt2 ? Pt2Project::class : Project::class;
        $lopClass = $isPt2 ? Pt2Lop::class : Lop::class;
        $projectPk = $isPt2 ? 'id_pt2_project' : 'id_project';
        $lopProjectFk = $isPt2 ? 'pt2_project_id' : 'project_id';

        $projectGroups = [];

        foreach ($rows as $row) {
            $projectKey = $this->key($row['pid_sap']);

            if (!isset($projectGroups[$projectKey])) {
                $projectGroups[$projectKey] = $row;
                continue;
            }

            // PT2 dapat memiliki banyak LOP; ambil metadata project yang belum terisi.
            if ($isPt2) {
                foreach (['pid', 'project_name', 'program'] as $field) {
                    if (
                        empty($projectGroups[$projectKey][$field])
                        && !empty($row[$field])
                    ) {
                        $projectGroups[$projectKey][$field] = $row[$field];
                    }
                }

                if (
                    !$projectGroups[$projectKey]['status_explicit']
                    && $row['status_explicit']
                ) {
                    $projectGroups[$projectKey]['status_project'] = $row['status_project'];
                    $projectGroups[$projectKey]['status_explicit'] = true;
                }
            }
        }

        $pidSaps = collect($projectGroups)
            ->pluck('pid_sap')
            ->filter()
            ->unique()
            ->values();

        $pids = collect($projectGroups)
            ->pluck('pid')
            ->filter()
            ->unique()
            ->values();

        $existingProjects = $projectClass::query()
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($pidSaps, $pids) {
                if ($pidSaps->isNotEmpty()) {
                    $query->whereIn('pid_sap', $pidSaps);
                }

                if ($pids->isNotEmpty()) {
                    if ($pidSaps->isNotEmpty()) {
                        $query->orWhereIn('pid', $pids);
                    } else {
                        $query->whereIn('pid', $pids);
                    }
                }
            })
            ->get();

        $projectByPidSap = [];
        $projectByPid = [];

        foreach ($existingProjects as $project) {
            if (!empty($project->pid_sap)) {
                $projectByPidSap[$this->key($project->pid_sap)] = $project;
            }

            if (!empty($project->pid)) {
                $projectByPid[$this->key($project->pid)] = $project;
            }
        }

        $resolvedProjects = [];

        foreach ($projectGroups as $projectKey => $row) {
            $project = $projectByPidSap[$this->key($row['pid_sap'])] ?? null;

            if (!$project && !empty($row['pid'])) {
                $project = $projectByPid[$this->key($row['pid'])] ?? null;
            }

            if (!$project) {
                $payload = $isPt2
                    ? $this->newPt2ProjectPayload($row, $customerId)
                    : $this->newRegularProjectPayload($row, $customerId);

                $project = $projectClass::create($payload);
                $counters['project_created']++;
            } else {
                $payload = $isPt2
                    ? $this->existingPt2ProjectPayload($row)
                    : $this->existingRegularProjectPayload($row);

                $project->fill($this->nonBlankPayload($payload));

                if ($project->isDirty()) {
                    $project->save();
                    $counters['project_updated']++;
                }
            }

            $resolvedProjects[$projectKey] = $project;
        }

        $projectIds = collect($resolvedProjects)
            ->map(fn ($project) => $project->{$projectPk})
            ->filter()
            ->unique()
            ->values();

        $existingLops = $lopClass::query()
            ->whereIn($lopProjectFk, $projectIds)
            ->get();

        $regularLopByProject = [];
        $pt2LopByProjectIhld = [];

        foreach ($existingLops as $lop) {
            $projectId = $lop->{$lopProjectFk};

            if ($isPt2) {
                if (!empty($lop->id_ihld)) {
                    $pt2LopByProjectIhld[
                        $projectId . '|ihld|' . $this->key($lop->id_ihld)
                    ] = $lop;
                }
            } else {
                $regularLopByProject[$projectId] = $lop;
            }
        }

        foreach ($rows as $row) {
            $project = $resolvedProjects[$this->key($row['pid_sap'])] ?? null;

            if (!$project) {
                $counters['skipped']++;
                continue;
            }

            $projectId = $project->{$projectPk};

            if ($isPt2) {
                $lopKey = $projectId . '|ihld|' . $this->key($row['id_ihld']);
                $lop = $pt2LopByProjectIhld[$lopKey] ?? null;
            } else {
                $lop = $regularLopByProject[$projectId] ?? null;
            }

            $payload = [
                $lopProjectFk => $projectId,
                'id_ihld' => $row['id_ihld'],
                'lop_name' => $row['nama_lop'],
                'pid_sap' => $row['pid_sap'],
                'tematik' => $row['tematik'],
                'sto' => $row['sto'],
                'branch' => $row['branch'],
                'batch' => $row['batch'],
                'no_sp' => $row['no_sp'],
                'tgl_sp' => $row['tgl_sp'],
                'tgl_toc' => $row['tgl_toc'],
                'mitra_name' => $row['mitra_name'],
            ];

            if (!$isPt2) {
                $payload['program_sap'] = $row['program'];
                $payload['mapping_status'] = 'auto_matched';
            }

            $payload = $this->nonBlankPayload($payload);
            $payload[$lopProjectFk] = $projectId;

            if ($lop) {
                // Tidak menyentuh status_progress, BOQ, evidence, assignment, Go-Live, dll.
                $lop->fill($payload);

                if ($lop->isDirty()) {
                    $lop->save();
                    $counters['lop_updated']++;
                } else {
                    $counters['unchanged']++;
                }
            } else {
                $payload['status_progress'] = 'preparation';
                $lop = $lopClass::create($payload);
                $counters['lop_created']++;

                if ($isPt2) {
                    $pt2LopByProjectIhld[
                        $projectId . '|ihld|' . $this->key($lop->id_ihld)
                    ] = $lop;
                } else {
                    $regularLopByProject[$projectId] = $lop;
                }
            }
        }
    }

    private function parseRow(
        array $data,
        int $rowNumber,
        bool $isPt2,
        array &$seenKeys
    ): array {
        $pid = $this->cleanValue($data['pid'] ?? null);
        $pidSap = $this->cleanValue($data['pid_sap'] ?? null);
        $idIhld = $this->cleanValue($data['id_ihld'] ?? null);
        $namaLop = $this->cleanValue($data['nama_lop'] ?? null);
        $projectName = $this->cleanValue($data['project_name'] ?? null);
        $branch = $this->cleanValue($data['branch'] ?? null);
        $sto = $this->cleanValue($data['sto'] ?? null);

        $errors = [];
        $errorCode = 'invalid_data';

        if (!$pidSap) {
            $errors[] = 'PID SAP wajib diisi';
            $errorCode = 'missing_required_field';
        }

        if (!$idIhld) {
            $errors[] = 'ID IHLD wajib diisi';
            $errorCode = 'missing_required_field';
        }

        if (!$namaLop) {
            $errors[] = 'Nama LOP wajib diisi';
            $errorCode = 'missing_required_field';
        }

        if ($pidSap) {
            // Regular: 1 PID = 1 LOP. PT2: 1 PID boleh banyak LOP, unik per IHLD.
            $trackerKey = $isPt2
                ? $this->key($pidSap) . '|ihld|' . $this->key($idIhld)
                : $this->key($pidSap);

            if (isset($seenKeys[$trackerKey])) {
                $errors[] = 'Duplikat di file pada row ' . $seenKeys[$trackerKey];
                $errorCode = $isPt2 ? 'duplicate_lop' : 'duplicate_data';
            } else {
                $seenKeys[$trackerKey] = $rowNumber;
            }
        }

        [$tglSp, $tglSpValid] = $this->cleanDate($data['tgl_sp'] ?? null);
        [$tglToc, $tglTocValid] = $this->cleanDate($data['tgl_toc'] ?? null);

        if (!$tglSpValid) {
            $errors[] = 'Tanggal SP tidak valid';
            $errorCode = 'invalid_date';
        }

        if (!$tglTocValid) {
            $errors[] = 'Tanggal TOC tidak valid';
            $errorCode = 'invalid_date';
        }

        $rawExecution = $this->cleanValue($data['execution_type'] ?? null);
        $rawStatus = $this->cleanValue($data['status_project'] ?? null);

        $executionType = in_array(
            $rawExecution,
            ['kemitraan', 'swakelola', 'turnkey'],
            true
        ) ? $rawExecution : 'kemitraan';

        $statusProject = in_array(
            $rawStatus,
            ['init', 'active', 'close', 'bast', 'drop'],
            true
        ) ? $rawStatus : 'active';

        return [
            'row' => [
                'row' => $rowNumber,
                'pid' => $pid,
                'pid_sap' => $pidSap,
                'pid_for_project' => $pid ?: $pidSap,
                'project_name' => $projectName,
                'nama_lop' => $namaLop,
                'id_ihld' => $idIhld,
                'program' => $this->normalizeProgram(
                    $this->cleanValue($data['program'] ?? null),
                    $isPt2
                ),
                'branch' => $branch,
                'sto' => $sto,
                'mitra_name' => $this->cleanValue($data['mitra_name'] ?? null),
                'tematik' => $this->cleanValue($data['tematik'] ?? null),
                'batch' => $this->cleanValue($data['batch'] ?? null),
                'no_sp' => $this->cleanValue($data['no_sp'] ?? null),
                'tgl_sp' => $tglSp,
                'tgl_toc' => $tglToc,
                'execution_type' => $executionType,
                'status_project' => $statusProject,
                'execution_explicit' => !empty($rawExecution),
                'status_explicit' => !empty($rawStatus),
            ],
            'errors' => $errors,
            'error_code' => $errorCode,
        ];
    }

    private function readHeaders(
        $reader,
        string $filePath,
        string $highestColumn
    ): array {
        $filter = new PidChunkReadFilter();
        $filter->setRows(2, 1);

        $reader->setReadFilter($filter);

        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $headers = [];

        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
            $header = strtolower(
                trim((string) $sheet->getCell($columnLetter . '1')->getValue())
            );

            if ($header !== '') {
                $headers[$columnLetter] = $header;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);

        return $headers;
    }

    private function updateImportProgress(
        ImportProcess $import,
        array $counters,
        int $processedRows,
        int $progress,
        string $stage
    ): void {
        $import->update([
            'current_stage' => $stage,
            'progress' => $progress,
            'processed_rows' => $processedRows,
            'valid_rows' => $counters['valid_rows'],
            'invalid_rows' => $counters['invalid_rows'],
            'created_count' => $counters['project_created'] + $counters['lop_created'],
            'updated_count' => $counters['project_updated'] + $counters['lop_updated'],
            'unchanged_count' => $counters['unchanged'],
            'skipped_count' => $counters['skipped'],
            'summary' => [
                'project_created' => $counters['project_created'],
                'project_updated' => $counters['project_updated'],
                'lop_created' => $counters['lop_created'],
                'lop_updated' => $counters['lop_updated'],
                'unchanged' => $counters['unchanged'],
                'skipped' => $counters['skipped'],
            ],
        ]);
    }

    private function failValidation(
        ImportProcess $import,
        string $message
    ): array {
        $import->update([
            'status' => ImportProcess::STATUS_FAILED,
            'current_stage' => 'Validasi import gagal',
            'error_message' => $message,
            'finished_at' => now(),
        ]);

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $message,
        ];
    }

    private function newPt2ProjectPayload(array $row, int $customerId): array
    {
        return $this->nonBlankPayload([
            'customer_id' => $customerId,
            'pid' => $row['pid_for_project'],
            'pid_sap' => $row['pid_sap'],
            'project_name' => $row['project_name'] ?: 'PT 2 - ' . $row['pid_sap'],
            'program' => 'PT 2',
            // Legacy metadata saja; filter PT2 tetap harus membaca pt2_lops.branch/sto.
            'branch' => $row['branch'],
            'sto' => $row['sto'],
            'mitra_name' => $row['mitra_name'],
            'status_project' => $row['status_project'],
        ]);
    }

    private function existingPt2ProjectPayload(array $row): array
    {
        $payload = [
            'pid' => $row['pid_for_project'],
            'pid_sap' => $row['pid_sap'],
            'program' => 'PT 2',
        ];

        // project_name hanya berubah jika memang dikirim eksplisit di file.
        if (!empty($row['project_name'])) {
            $payload['project_name'] = $row['project_name'];
        }

        if ($row['status_explicit']) {
            $payload['status_project'] = $row['status_project'];
        }

        return $payload;
    }

    private function newRegularProjectPayload(array $row, int $customerId): array
    {
        return $this->nonBlankPayload([
            'customer_id' => $customerId,
            'pid' => $row['pid_for_project'],
            'pid_sap' => $row['pid_sap'],
            'project_name' => $row['project_name'] ?: $row['nama_lop'],
            'program' => $row['program'],
            'branch' => $row['branch'],
            'sto' => $row['sto'],
            'mitra_name' => $row['mitra_name'],
            'execution_type' => $row['execution_type'],
            'status_project' => $row['status_project'],
        ]);
    }

    private function existingRegularProjectPayload(array $row): array
    {
        $payload = [
            'pid' => $row['pid_for_project'],
            'pid_sap' => $row['pid_sap'],
            'project_name' => $row['project_name'] ?: $row['nama_lop'],
            'program' => $row['program'],
            'branch' => $row['branch'],
            'sto' => $row['sto'],
            'mitra_name' => $row['mitra_name'],
        ];

        if ($row['execution_explicit']) {
            $payload['execution_type'] = $row['execution_type'];
        }

        if ($row['status_explicit']) {
            $payload['status_project'] = $row['status_project'];
        }

        return $payload;
    }

    private function normalizeProgram(?string $program, bool $isPt2): ?string
    {
        if ($isPt2) {
            return 'PT 2';
        }

        if (!$program) {
            return null;
        }

        $normalized = strtoupper(
            preg_replace('/[^a-zA-Z0-9]/', '', $program)
        );

        return match ($normalized) {
            'PT2', 'PT02' => 'PT 2',
            'NODEB', 'NODE0B' => 'NODE B',
            default => trim($program),
        };
    }

    private function nonBlankPayload(array $payload): array
    {
        return array_filter(
            $payload,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    private function cleanValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_float($value) && floor($value) === $value) {
            $value = (string) (int) $value;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Return: [tanggal Y-m-d|null, valid bool]
     */
    private function cleanDate(mixed $value): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [null, true];
        }

        try {
            if (is_numeric($value)) {
                return [
                    ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d'),
                    true,
                ];
            }
        } catch (Throwable) {
            return [null, false];
        }

        $value = trim((string) $value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if (
                $date !== false
                && ($errors === false
                    || (($errors['warning_count'] ?? 0) === 0
                        && ($errors['error_count'] ?? 0) === 0))
            ) {
                return [$date->format('Y-m-d'), true];
            }
        }

        return [null, false];
    }

    private function key(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->cleanValue($value) !== null) {
                return false;
            }
        }

        return true;
    }

    private function jsonSafeRow(array $row): array
    {
        $safe = [];

        foreach ($row as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $safe[$key] = $value->format(DATE_ATOM);
            } elseif (is_scalar($value) || $value === null) {
                $safe[$key] = $value;
            } else {
                $safe[$key] = (string) $value;
            }
        }

        return $safe;
    }
}