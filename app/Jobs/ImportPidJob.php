<?php

namespace App\Jobs;

use App\Models\ImportLog;
use App\Models\Project;
use App\Models\Lop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportPidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(
        public int $importLogId,
        public string $filePath
    ) {}

    public function handle(): void
    {
        $log = ImportLog::findOrFail($this->importLogId);

        $log->update([
            'status' => 'processing',
            'progress' => 5,
            'started_at' => now(),
            'message' => 'Reading file...',
        ]);

        try {
            $fullPath = storage_path('app/private/' . $this->filePath);

            if (!file_exists($fullPath)) {
                $fullPath = storage_path('app/' . $this->filePath);
            }

            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            $headers = [];

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);

                $headers[$col] = strtolower(
                    trim((string) $sheet->getCell($columnLetter . '1')->getValue())
                );
            }

            $requiredHeaders = [
                'pid_sap',
                'nama_lop',
            ];

            $missingHeaders = [];

            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    $missingHeaders[] = $requiredHeader;
                }
            }

            $totalRows = max($highestRow - 1, 0);

            if (!empty($missingHeaders)) {
               $log->update([
                    'status' => 'failed',
                    'progress' => 100,
                    'total_rows' => $totalRows,
                    'valid_rows' => 0,
                    'invalid_rows' => 0,
                    'message' => 'Header wajib tidak ditemukan: ' . implode(', ', $missingHeaders),
                    'errors' => [
                        [
                            'row' => '-',
                            'pid' => '-',
                            'pid_sap' => '-',
                            'nama_lop' => '-',
                            'reason' => 'Header wajib tidak ditemukan: ' . implode(', ', $missingHeaders),
                        ],
                    ],
                    'finished_at' => now(),
                ]);

                return;
            }

            $projectImported = 0;
            $projectUpdated = 0;
            $lopImported = 0;
            $lopUpdated = 0;
            $skipped = 0;
            $validRows = 0;

            $invalidRows = [];
            $pidSapTracker = [];

            $log->update([
                'total_rows' => $totalRows,
                'progress' => 10,
                'message' => 'Validating data...',
            ]);

            DB::beginTransaction();

            for ($row = 2; $row <= $highestRow; $row++) {

                $data = [];

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($col);
                    $headerName = $headers[$col] ?? null;

                    if (!$headerName) {
                        continue;
                    }

                    $data[$headerName] = $sheet->getCell($columnLetter . $row)->getValue();
                }

                $pid = $this->cleanValue($data['pid'] ?? null);
                $pidSap = $this->cleanValue($data['pid_sap'] ?? null);
                $namaLop = $this->cleanValue($data['nama_lop'] ?? null);
                $idIhld = $this->cleanValue($data['id_ihld'] ?? null);

                $rowErrors = [];

                if (!$pidSap) {
                    $rowErrors[] = 'PID SAP wajib diisi';
                }

                if (!$namaLop) {
                    $rowErrors[] = 'Nama LOP wajib diisi';
                }

                if ($pidSap) {
                    $pidSapKey = strtolower(trim($pidSap));

                    if (isset($pidSapTracker[$pidSapKey])) {
                        $rowErrors[] = 'PID SAP duplikat di file, sama dengan row ' . $pidSapTracker[$pidSapKey];
                    } else {
                        $pidSapTracker[$pidSapKey] = $row;
                    }
                }

                if (!empty($rowErrors)) {
                    $skipped++;

                    $invalidRows[] = [
                        'row' => $row,
                        'pid' => $pid,
                        'pid_sap' => $pidSap,
                        'nama_lop' => $namaLop,
                        'reason' => implode(', ', $rowErrors),
                    ];

                    continue;
                }

                $validRows++;

                $executionType = $this->cleanValue($data['execution_type'] ?? 'kemitraan') ?: 'kemitraan';
                $statusProject = $this->cleanValue($data['status_project'] ?? 'active') ?: 'active';

                if (!in_array($executionType, ['kemitraan', 'swakelola', 'turnkey'])) {
                    $executionType = 'kemitraan';
                }

                if (!in_array($statusProject, ['init', 'active', 'close', 'bast'])) {
                    $statusProject = 'active';
                }

                $pidForProject = $pid ?: $pidSap;

                $projectPayload = [
                    'pid' => $pidForProject,
                    'pid_sap' => $pidSap,
                    'project_name' => $namaLop,
                    'program' => $this->cleanValue($data['program'] ?? null),
                    'execution_type' => $executionType,
                    'status_project' => $statusProject,
                ];

                $project = Project::where('pid_sap', $pidSap)->first();

                if (!$project && $pid) {
                    $project = Project::where('pid', $pid)->first();
                }

                if ($project) {
                    $project->update($projectPayload);
                    $projectUpdated++;
                } else {
                    $project = Project::create($projectPayload);
                    $projectImported++;
                }

                $lopPayload = [
                    'project_id' => $project->id_project,
                    'id_ihld' => $idIhld,
                    'lop_name' => $namaLop,
                    'pid_sap' => $pidSap,
                    'program_sap' => $this->cleanValue($data['program'] ?? null),
                    'tematik' => $this->cleanValue($data['tematik'] ?? null),
                    'sto' => $this->cleanValue($data['sto'] ?? null),
                    'branch' => $this->cleanValue($data['branch'] ?? null),
                    'batch' => $this->cleanValue($data['batch'] ?? null),
                    'no_sp' => $this->cleanValue($data['no_sp'] ?? null),
                    'tgl_sp' => $this->cleanDate($data['tgl_sp'] ?? null),
                    'tgl_toc' => $this->cleanDate($data['tgl_toc'] ?? null),
                    'mitra_name' => $this->cleanValue($data['mitra_name'] ?? null),
                    'mapping_status' => 'auto_matched',
                    'status_progress' => 'preparation',
                ];

                $lop = null;

                if ($idIhld) {
                    $lop = Lop::where('project_id', $project->id_project)
                        ->where('id_ihld', $idIhld)
                        ->first();
                }

                if (!$lop) {
                    $lop = Lop::where('project_id', $project->id_project)
                        ->whereRaw('LOWER(TRIM(lop_name)) = ?', [
                            strtolower(trim($namaLop))
                        ])
                        ->first();
                }

                if ($lop) {
                    $lop->update($lopPayload);
                    $lopUpdated++;
                } else {
                    Lop::create($lopPayload);
                    $lopImported++;
                }

                if ($row % 25 === 0) {
                    $progress = 10 + round((($row - 1) / max($totalRows, 1)) * 85);

                    $log->update([
                    'progress' => min($progress, 95),
                    'valid_rows' => $validRows,
                    'invalid_rows' => count($invalidRows),

                    'imported' => $projectImported,
                    'updated' => $projectUpdated,
                    'skipped' => $skipped,

                    'project_imported' => $projectImported,
                    'project_updated' => $projectUpdated,
                    'lop_imported' => $lopImported,
                    'lop_updated' => $lopUpdated,

                    'message' => "Processing row {$row} dari {$highestRow}",
                ]);
                }
            }

            DB::commit();

            $log->update([
            'status' => 'success',
            'progress' => 100,

            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'invalid_rows' => count($invalidRows),

            'imported' => $projectImported,
            'updated' => $projectUpdated,
            'skipped' => $skipped,

            'project_imported' => $projectImported,
            'project_updated' => $projectUpdated,
            'lop_imported' => $lopImported,
            'lop_updated' => $lopUpdated,

            'errors' => array_slice($invalidRows, 0, 10),
            'invalid_rows' => count($invalidRows),

            'message' => "Import selesai. Project Baru {$projectImported}, Update Project {$projectUpdated}, LOP Baru {$lopImported}, Update LOP {$lopUpdated}, Skip {$skipped}.",
            'finished_at' => now(),
        ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            $log->update([
                'status' => 'failed',
                'progress' => 100,
                'message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    private function cleanValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function cleanDate($value)
    {
        $value = $this->cleanValue($value);

        if (!$value) {
            return null;
        }

        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }
}