<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Lop;
use App\Models\BoqItem;
use App\Models\Designator;
use App\Models\Evidence;
use App\Models\ProjectAssignment;
use App\Models\ImportLog;
use App\Models\Customer;
use App\Models\Pt2Project;
use App\Models\Pt2Lop;
use App\Models\SurveyPt2;
use App\Models\Pt2Evidence;
use App\Models\DismantlePt2;
use App\Models\MancorePt2;
use App\Models\Package as PackageModel;
use App\Models\DesignatorPackagePrice;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use App\Jobs\ProcessPidImportJob;
use App\Jobs\ProcessBoqImportJob;
use App\Models\ImportProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use Throwable;


class ImportController extends Controller
{
    public function pidIndex()
    {
        // Master customer untuk dropdown Exbis. Query disiapkan controller, bukan Blade.
        $customers = Customer::query()
            ->where('id_customer', '!=', 1)
            ->active()
            ->orderBy('customer_name')
            ->get();

        // 5 upload PID terbaru dari SEMUA user.
        $importProcesses = ImportProcess::query()
            ->with('uploader')
            ->where('import_type', ImportProcess::TYPE_PID)
            ->latest('id_import')
            ->limit(5)
            ->get();

        $lastProcess = $importProcesses->first();

        // Queue health portable untuk driver database maupun redis.
        $queuedCount = ImportProcess::query()
            ->where('import_type', ImportProcess::TYPE_PID)
            ->where('status', ImportProcess::STATUS_QUEUED)
            ->count();

        $processingCount = ImportProcess::query()
            ->where('import_type', ImportProcess::TYPE_PID)
            ->where('status', ImportProcess::STATUS_PROCESSING)
            ->count();

        $oldestQueued = ImportProcess::query()
            ->where('import_type', ImportProcess::TYPE_PID)
            ->where('status', ImportProcess::STATUS_QUEUED)
            ->oldest('created_at')
            ->first(['id_import', 'created_at']);

        $queuedTooLong = $oldestQueued?->created_at?->lt(now()->subMinutes(5)) ?? false;

        if ($processingCount > 0) {
            $queueHealth = [
                'state' => 'processing',
                'label' => 'Memproses',
                'description' => 'Background worker sedang memproses import PID.',
            ];
        } elseif ($queuedCount > 0 && $queuedTooLong) {
            $queueHealth = [
                'state' => 'warning',
                'label' => 'Perlu Dicek',
                'description' => 'Ada import PID menunggu lebih dari 5 menit. Periksa queue worker.',
            ];
        } elseif ($queuedCount > 0) {
            $queueHealth = [
                'state' => 'waiting',
                'label' => 'Menunggu',
                'description' => 'Import PID sudah masuk antrean dan menunggu worker.',
            ];
        } else {
            $queueHealth = [
                'state' => 'normal',
                'label' => 'Normal',
                'description' => 'Tidak ada antrean PID yang tertahan.',
            ];
        }

        $queueHealth['queued_count'] = $queuedCount;
        $queueHealth['processing_count'] = $processingCount;
        $queueHealth['driver'] = config('queue.default');

        return view('admin.import.pid', compact(
            'customers',
            'importProcesses',
            'lastProcess',
            'queueHealth'
        ));
    }


    public function importPid(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:102400', // 100 MB (KB)
            ],
            'project_type' => [
                'required',
                'in:internal,external,pt2',
            ],
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id_customer',
            ],
        ]);

        $projectType = $validated['project_type'];

        // Internal dan PT2 selalu customer internal = 1.
        if (in_array($projectType, ['internal', 'pt2'], true)) {
            $customerId = 1;
        } else {
            $customerId = isset($validated['customer_id'])
                ? (int) $validated['customer_id']
                : null;

            if (!$customerId || $customerId === 1) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Customer Exbis wajib dipilih.',
                ]);
            }
        }

        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();

        $storedPath = null;
        $import = null;

        try {
            $storedPath = $file->store('imports/pid', 'local');

            $currentUserId = auth()->user()->id_user
                ?? auth()->id();

            $import = ImportProcess::query()->create([
                'uuid' => (string) Str::uuid(),
                'import_type' => ImportProcess::TYPE_PID,
                'project_type' => $projectType,
                'customer_id' => $customerId,
                'original_file_name' => $originalFileName,
                'stored_file_path' => $storedPath,
                'disk' => 'local',
                'status' => ImportProcess::STATUS_QUEUED,
                'current_stage' => 'Menunggu background worker',
                'progress' => 0,
                'total_rows' => 0,
                'processed_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'unchanged_count' => 0,
                'skipped_count' => 0,
                'summary' => [],
                'uploaded_by' => $currentUserId,
            ]);

            ProcessPidImportJob::dispatch($import->id_import);

            return redirect()
                ->route('admin.import.pid', [
                    'import_uuid' => $import->uuid,
                ])
                ->with(
                    'success',
                    "File {$originalFileName} berhasil diterima dan masuk antrean import."
                );
        } catch (\Throwable $e) {
            if ($import) {
                $import->update([
                    'status' => ImportProcess::STATUS_FAILED,
                    'current_stage' => 'Gagal menyiapkan background import',
                    'error_message' => mb_substr($e->getMessage(), 0, 65000),
                    'finished_at' => now(),
                ]);
            } elseif ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            report($e);

            return back()
                ->withInput()
                ->with('error', 'File gagal masuk antrean import: ' . $e->getMessage());
        }
    }


    public function importPidStatus(string $uuid)
    {
        /*
        * Tidak dibatasi berdasarkan uploaded_by.
        * Authorization mengikuti middleware/permission pada group route Admin Import PID.
        * Dengan demikian admin dapat membuka 5 history upload terakhir dari semua user.
        */
        $import = ImportProcess::query()
            ->with('uploader')
            ->where('uuid', $uuid)
            ->where('import_type', ImportProcess::TYPE_PID)
            ->firstOrFail();

        $errors = [];

        if ($import->isFinished()) {
            $errors = $import->errors()
                ->orderBy('row_number')
                ->limit(10)
                ->get([
                    'row_number',
                    'pid_sap',
                    'id_ihld',
                    'nama_lop',
                    'error_code',
                    'message',
                ])
                ->map(fn ($error) => [
                    'row_number' => $error->row_number,
                    'pid_sap' => $error->pid_sap,
                    'id_ihld' => $error->id_ihld,
                    'nama_lop' => $error->nama_lop,
                    'error_code' => $error->error_code,
                    'message' => $error->message,
                ])
                ->values();
        }

        $uploaderName = $import->uploader?->name
            ?? $import->uploader?->full_name
            ?? $import->uploader?->username
            ?? $import->uploader?->email
            ?? ($import->uploaded_by ? 'User #' . $import->uploaded_by : '-');

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $import->uuid,
                'file_name' => $import->original_file_name,
                'import_type' => $import->import_type,
                'project_type' => $import->project_type,
                'customer_id' => $import->customer_id,

                'status' => $import->status,
                'stage' => $import->current_stage,
                'progress' => min(100, max(0, (int) $import->progress)),

                'total_rows' => (int) $import->total_rows,
                'processed_rows' => (int) $import->processed_rows,
                'valid_rows' => (int) $import->valid_rows,
                'invalid_rows' => (int) $import->invalid_rows,

                'created_count' => (int) $import->created_count,
                'updated_count' => (int) $import->updated_count,
                'unchanged_count' => (int) $import->unchanged_count,
                'skipped_count' => (int) $import->skipped_count,

                'summary' => $import->summary ?? [],
                'error_message' => $import->error_message,

                'uploaded_by' => $import->uploaded_by,
                'uploader' => [
                    'id' => $import->uploaded_by,
                    'name' => $uploaderName,
                    'email' => $import->uploader?->email,
                ],

                'started_at' => optional($import->started_at)?->format('Y-m-d H:i:s'),
                'finished_at' => optional($import->finished_at)?->format('Y-m-d H:i:s'),

                'errors' => $errors,

                // Tombol ini hanya dipakai Blade jika invalid_rows > 0.
                'error_download_url' => (int) $import->invalid_rows > 0
                    ? route('admin.import.pid.errors.download', $import->uuid)
                    : null,
            ],
        ])->header(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0'
        );
    }


    public function downloadPidImportErrors(string $uuid)
    {
        /*
        * Authorization mengikuti middleware/permission Admin Import PID yang sama.
        * Karena history bersifat global, admin dapat download error upload user lain.
        */
        $import = ImportProcess::query()
            ->where('uuid', $uuid)
            ->where('import_type', ImportProcess::TYPE_PID)
            ->firstOrFail();

        if ((int) $import->invalid_rows <= 0) {
            abort(404, 'Import ini tidak memiliki row invalid.');
        }

        $downloadName = 'pid-import-errors-'
            . $import->uuid
            . '.csv';

        return response()->streamDownload(function () use ($import) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM agar Excel Windows membaca teks Indonesia dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Row',
                'PID SAP',
                'ID IHLD',
                'Nama LOP',
                'Error Code',
                'Keterangan',
            ]);

            $import->errors()
                ->orderBy('row_number')
                ->chunkById(
                    500,
                    function ($errors) use ($handle) {
                        foreach ($errors as $error) {
                            fputcsv($handle, [
                                $error->row_number,
                                $error->pid_sap,
                                $error->id_ihld,
                                $error->nama_lop,
                                $error->error_code,
                                $error->message,
                            ]);
                        }
                    },
                    'id_error'
                );

            fclose($handle);
        }, $downloadName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function dataPid(\Illuminate\Http\Request $request)
    {
        $regions = $this->pidRegions();
        $dataType = $request->input('type', 'regular');

        if (!in_array($dataType, ['regular', 'pt2'], true)) {
            $dataType = 'regular';
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50], true)) {
            $perPage = 10;
        }

        $matrixData = [];
        $grandTotals = [];

        if ($dataType === 'pt2') {
            /*
            |--------------------------------------------------------------------------
            | PT2 PROJECT QUERY - PROJECT PARENT, LOP CHILD
            |--------------------------------------------------------------------------
            */
            $base = \Illuminate\Support\Facades\DB::table('pt2_projects as p');
            $this->applyPt2PidFilters($base, $request, $regions);

            $programs = \Illuminate\Support\Facades\DB::table('pt2_projects')
                ->whereNotNull('program')
                ->where('program', '!=', '')
                ->distinct()
                ->orderBy('program')
                ->pluck('program');

            $totalPid = (clone $base)->count('p.id_pt2_project');

            $pidMatchBoq = (clone $base)
                ->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('pt2_boq_items as b')
                        ->whereColumn('b.pt2_project_id', 'p.id_pt2_project');
                })
                ->count('p.id_pt2_project');

            $projectActive = (clone $base)
                ->where('p.status_project', 'active')
                ->count('p.id_pt2_project');

            $projectDrop = (clone $base)
                ->where('p.status_project', 'drop')
                ->count('p.id_pt2_project');

            $filteredProjectIds = (clone $base)
                ->select('p.id_pt2_project');

            $totalLop = \Illuminate\Support\Facades\DB::query()
                ->fromSub($filteredProjectIds, 'fp')
                ->join('pt2_lops as l', 'l.pt2_project_id', '=', 'fp.id_pt2_project')
                ->count();

            $projects = (clone $base)
                ->select([
                    'p.id_pt2_project as id_project',
                    'p.pid',
                    'p.pid_sap',
                    'p.project_name',
                    'p.program',
                    'p.status_project',
                    'p.status',
                    'p.is_golive',
                    'p.sdi_approval_status',
                    'p.branch as project_branch',
                    'p.sto as project_sto',
                    'p.mitra_name as project_mitra',
                ])
                ->selectRaw("'pt2' as source_type")
                ->orderByDesc('p.id_pt2_project')
                ->paginate($perPage)
                ->withQueryString();

            $pageProjectIds = collect($projects->items())
                ->pluck('id_project')
                ->filter()
                ->values();

            $lopGroups = collect();

            if ($pageProjectIds->isNotEmpty()) {
                $assignmentSub = \Illuminate\Support\Facades\DB::table('pt2_assignments')
                    ->select('pt2_lop_id')
                    ->selectRaw('COUNT(*) as assignment_count')
                    ->groupBy('pt2_lop_id');

                $boqSub = \Illuminate\Support\Facades\DB::table('pt2_boq_items')
                    ->select('pt2_lop_id')
                    ->selectRaw('COUNT(*) as boq_count')
                    ->groupBy('pt2_lop_id');

                $lopGroups = \Illuminate\Support\Facades\DB::table('pt2_lops as l')
                    ->leftJoinSub($assignmentSub, 'a', fn ($join) => $join->on('a.pt2_lop_id', '=', 'l.id_pt2_lop'))
                    ->leftJoinSub($boqSub, 'b', fn ($join) => $join->on('b.pt2_lop_id', '=', 'l.id_pt2_lop'))
                    ->whereIn('l.pt2_project_id', $pageProjectIds)
                    ->orderBy('l.pt2_project_id')
                    ->orderBy('l.id_pt2_lop')
                    ->get([
                        'l.id_pt2_lop',
                        'l.pt2_project_id',
                        'l.id_ihld',
                        'l.lop_name',
                        'l.pid_sap',
                        'l.branch',
                        'l.sto',
                        'l.batch',
                        'l.status_progress',
                        'l.package_id',
                        'l.is_golive',
                        'l.sdi_approval_status',
                        \Illuminate\Support\Facades\DB::raw('COALESCE(a.assignment_count, 0) as assignment_count'),
                        \Illuminate\Support\Facades\DB::raw('COALESCE(b.boq_count, 0) as boq_count'),
                    ])
                    ->groupBy('pt2_project_id');
            }
        } else {
            /*
            |--------------------------------------------------------------------------
            | REGULAR - 1 PID = 1 LOP
            |--------------------------------------------------------------------------
            */
            $base = \Illuminate\Support\Facades\DB::table('projects as p');
            $this->applyRegularPidFilters($base, $request, $regions);

            $programs = \Illuminate\Support\Facades\DB::table('projects')
                ->whereNotNull('program')
                ->where('program', '!=', '')
                ->whereRaw("UPPER(TRIM(program)) <> 'PT 2'")
                ->distinct()
                ->orderBy('program')
                ->pluck('program');

            $totalPid = (clone $base)->count('p.id_project');

            $pidMatchBoq = (clone $base)
                ->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('boq_items as b')
                        ->whereColumn('b.project_id', 'p.id_project');
                })
                ->count('p.id_project');

            $projectActive = (clone $base)
                ->where('p.status_project', 'active')
                ->count('p.id_project');

            $projectDrop = (clone $base)
                ->where('p.status_project', 'drop')
                ->count('p.id_project');

            $totalLop = (clone $base)
                ->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('lops as l')
                        ->whereColumn('l.project_id', 'p.id_project');
                })
                ->count('p.id_project');

            $projects = (clone $base)
                ->select([
                    'p.id_project',
                    'p.pid',
                    'p.pid_sap',
                    'p.project_name',
                    'p.program',
                    'p.execution_type',
                    'p.status_project',
                    'p.branch as project_branch',
                    'p.sto as project_sto',
                    'p.mitra_name as project_mitra',
                ])
                ->selectRaw("'regular' as source_type")
                ->orderByDesc('p.id_project')
                ->paginate($perPage)
                ->withQueryString();

            $pageProjectIds = collect($projects->items())
                ->pluck('id_project')
                ->filter()
                ->values();

            $lopGroups = collect();

            if ($pageProjectIds->isNotEmpty()) {
                $lopGroups = \Illuminate\Support\Facades\DB::table('lops')
                    ->whereIn('project_id', $pageProjectIds)
                    ->orderBy('id_lop')
                    ->get([
                        'id_lop',
                        'project_id',
                        'id_ihld',
                        'lop_name',
                        'pid_sap',
                        'program_sap',
                        'tematik',
                        'sto',
                        'branch',
                        'batch',
                        'no_sp',
                        'tgl_sp',
                        'tgl_toc',
                        'mitra_name',
                        'status_progress',
                    ])
                    ->groupBy('project_id');
            }

            /* Matrix Regular-only. Tidak load Project::all(). */
            [$matrixData, $grandTotals] = $this->buildRegularPidMatrix($regions, $programs);
        }

        return view('admin.import.data-pid', compact(
            'dataType',
            'regions',
            'programs',
            'projects',
            'lopGroups',
            'totalPid',
            'totalLop',
            'pidMatchBoq',
            'projectActive',
            'projectDrop',
            'matrixData',
            'grandTotals'
        ));
    }

    public function exportPid(\Illuminate\Http\Request $request)
    {
        $regions = $this->pidRegions();
        $dataType = $request->input('type', 'regular');

        if (!in_array($dataType, ['regular', 'pt2'], true)) {
            $dataType = 'regular';
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($dataType === 'pt2') {
            $sheet->setTitle('Data PID PT2');

            $base = \Illuminate\Support\Facades\DB::table('pt2_projects as p');
            $this->applyPt2PidFilters($base, $request, $regions);

            $assignmentSub = \Illuminate\Support\Facades\DB::table('pt2_assignments')
                ->select('pt2_lop_id')
                ->selectRaw('COUNT(*) as assignment_count')
                ->groupBy('pt2_lop_id');

            $boqSub = \Illuminate\Support\Facades\DB::table('pt2_boq_items')
                ->select('pt2_lop_id')
                ->selectRaw('COUNT(*) as boq_count')
                ->groupBy('pt2_lop_id');

            $rows = (clone $base)
                ->leftJoin('pt2_lops as l', 'l.pt2_project_id', '=', 'p.id_pt2_project')
                ->leftJoinSub($assignmentSub, 'a', fn ($join) => $join->on('a.pt2_lop_id', '=', 'l.id_pt2_lop'))
                ->leftJoinSub($boqSub, 'b', fn ($join) => $join->on('b.pt2_lop_id', '=', 'l.id_pt2_lop'))
                ->orderByDesc('p.id_pt2_project')
                ->orderBy('l.id_pt2_lop')
                ->get([
                    'p.pid',
                    'p.pid_sap',
                    'p.project_name',
                    'p.program',
                    'p.status_project',
                    'p.status',
                    'p.is_golive as project_golive',
                    'p.sdi_approval_status as project_sdi_approval',
                    'l.id_ihld',
                    'l.lop_name',
                    'l.pid_sap as lop_pid_sap',
                    'l.branch',
                    'l.sto',
                    'l.batch',
                    'l.status_progress',
                    'l.package_id',
                    'l.is_golive as lop_golive',
                    'l.sdi_approval_status as lop_sdi_approval',
                    \Illuminate\Support\Facades\DB::raw('COALESCE(a.assignment_count, 0) as assignment_count'),
                    \Illuminate\Support\Facades\DB::raw('COALESCE(b.boq_count, 0) as boq_count'),
                ]);

            $headers = [
                'PID', 'PID SAP', 'Nama Project', 'Program', 'Status Project', 'Status',
                'Go Live Project', 'SDI Approval Project',
                'ID IHLD', 'Nama LOP', 'PID SAP LOP', 'Branch', 'STO', 'Batch',
                'Status Progress', 'Package ID', 'Go Live LOP', 'SDI Approval LOP',
                'Jumlah Assignment', 'Jumlah Item BOQ',
            ];

            $sheet->fromArray($headers, null, 'A1');

            $rowIndex = 2;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    $row->pid ?? '-',
                    $row->pid_sap ?? '-',
                    $row->project_name ?? '-',
                    $row->program ?? '-',
                    $row->status_project ?? '-',
                    $row->status ?? '-',
                    $row->project_golive ?? '-',
                    $row->project_sdi_approval ?? '-',
                    $row->id_ihld ?? '-',
                    $row->lop_name ?? '-',
                    $row->lop_pid_sap ?? '-',
                    $row->branch ?? '-',
                    $row->sto ?? '-',
                    $row->batch ?? '-',
                    $row->status_progress ?? '-',
                    $row->package_id ?? '-',
                    $row->lop_golive ?? '-',
                    $row->lop_sdi_approval ?? '-',
                    (int) $row->assignment_count,
                    (int) $row->boq_count,
                ], null, 'A' . $rowIndex);
                $rowIndex++;
            }
        } else {
            $sheet->setTitle('Data PID Regular');

            $base = \Illuminate\Support\Facades\DB::table('projects as p');
            $this->applyRegularPidFilters($base, $request, $regions);

            $rows = (clone $base)
                ->leftJoin('lops as l', 'l.project_id', '=', 'p.id_project')
                ->orderByDesc('p.id_project')
                ->orderBy('l.id_lop')
                ->get([
                    'p.pid',
                    'p.pid_sap',
                    'p.project_name',
                    'p.program',
                    'p.execution_type',
                    'p.status_project',
                    'l.id_ihld',
                    'l.lop_name',
                    'l.program_sap',
                    'l.tematik',
                    'l.sto',
                    'l.branch',
                    'l.batch',
                    'l.no_sp',
                    'l.tgl_sp',
                    'l.tgl_toc',
                    'l.mitra_name',
                ]);

            $headers = [
                'PID', 'PID SAP', 'Nama Project', 'Program', 'Execution Type', 'Status Project',
                'ID IHLD', 'Nama LOP', 'Program SAP', 'Tematik', 'STO', 'Branch', 'Batch',
                'No SP', 'Tgl SP', 'Tgl TOC', 'Mitra',
            ];

            $sheet->fromArray($headers, null, 'A1');

            $rowIndex = 2;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    $row->pid ?? '-',
                    $row->pid_sap ?? '-',
                    $row->project_name ?? '-',
                    $row->program ?? '-',
                    $row->execution_type ?? '-',
                    $row->status_project ?? '-',
                    $row->id_ihld ?? '-',
                    $row->lop_name ?? '-',
                    $row->program_sap ?? '-',
                    $row->tematik ?? '-',
                    $row->sto ?? '-',
                    $row->branch ?? '-',
                    $row->batch ?? '-',
                    $row->no_sp ?? '-',
                    $row->tgl_sp ?? '-',
                    $row->tgl_toc ?? '-',
                    $row->mitra_name ?? '-',
                ], null, 'A' . $rowIndex);
                $rowIndex++;
            }
        }

        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('DBEAFE');

        foreach (range('A', $lastColumn) as $columnId) {
            $sheet->getColumnDimension($columnId)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $fileName = 'data-pid-' . $dataType . '-' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function updatePid(\Illuminate\Http\Request $request, \App\Models\Project $project)
{
    /* Hanya Regular. PT2 LOP diedit di workflow PT2/LOP, bukan parent project. */
    $request->validate([
        'pid'              => 'required|string|max:100',
        'pid_sap'          => 'nullable|string|max:100',
        'nama_lop'         => 'required|string|max:255',
        'program'          => 'nullable|string|max:150',
        'execution_type'   => 'required|in:kemitraan,swakelola,turnkey',
        'status_project'   => 'required|in:init,active,close,bast,drop',
        'id_ihld'          => 'nullable|string|max:100',
        'tematik'          => 'nullable|string|max:150',
        'sto'              => 'nullable|string|max:50',
        'branch'           => 'nullable|string|max:100',
        'batch'            => 'nullable|string|max:100',
        'no_sp'            => 'nullable|string|max:100',
        'tgl_sp'           => 'nullable|date',
        'tgl_toc'          => 'nullable|date',
        'mitra_name'       => 'nullable|string|max:150',
    ]);

    \Illuminate\Support\Facades\DB::transaction(function () use ($request, $project) {
        $project->update([
            'pid'            => $request->pid,
            'pid_sap'        => $request->pid_sap,
            'project_name'   => $request->nama_lop,
            'program'        => $request->program,
            'branch'         => $request->branch,
            'sto'            => $request->sto,
            'mitra_name'     => $request->mitra_name,
            'execution_type' => $request->execution_type,
            'status_project' => $request->status_project,
        ]);

        $lop = \App\Models\Lop::where('project_id', $project->id_project)->first();

        $payload = [
            'project_id'     => $project->id_project,
            'id_ihld'        => $request->id_ihld,
            'lop_name'       => $request->nama_lop,
            'pid_sap'        => $request->pid_sap,
            'program_sap'    => $request->program,
            'tematik'        => $request->tematik,
            'sto'            => $request->sto,
            'branch'         => $request->branch,
            'batch'          => $request->batch,
            'no_sp'          => $request->no_sp,
            'tgl_sp'         => $request->tgl_sp,
            'tgl_toc'        => $request->tgl_toc,
            'mitra_name'     => $request->mitra_name,
            'mapping_status' => 'auto_matched',
        ];

        if ($lop) {
            $lop->update($payload);
        } else {
            $payload['status_progress'] = 'preparation';
            \App\Models\Lop::create($payload);
        }
    });

    return back()->with('success', 'Data PID Regular dan LOP berhasil diperbarui.');
}

public function destroyPid(\App\Models\Project $project)
{
    $hasEvidence = \App\Models\Evidence::where('project_id', $project->id_project)->exists();

    if ($hasEvidence) {
        return back()->with('error', 'Project tidak dapat dihapus karena sudah memiliki evidence.');
    }

    \Illuminate\Support\Facades\DB::transaction(function () use ($project) {
        \App\Models\BoqItem::where('project_id', $project->id_project)->delete();
        \App\Models\ProjectAssignment::where('project_id', $project->id_project)->delete();
        \App\Models\Lop::where('project_id', $project->id_project)->delete();
        $project->delete();
    });

    return back()->with('success', 'Project Regular berhasil dihapus.');
}

private function normalizePidProgram(?string $program, bool $isPt2): ?string
{
    if ($isPt2) {
        return 'PT 2';
    }

    if (!$program) {
        return null;
    }

    $normalized = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $program));

    return match ($normalized) {
        'PT2', 'PT02'   => 'PT 2',
        'NODEB', 'NODE0B' => 'NODE B',
        default         => trim($program),
    };
}

private function nonBlankPidPayload(array $payload): array
{
    return array_filter(
        $payload,
        static fn ($value) => $value !== null && $value !== ''
    );
}

private function pidRegions(): array
{
    return [
        'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
        'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
        'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES'],
    ];
}

private function applyRegularPidFilters($query, \Illuminate\Http\Request $request, array $regions): void
{
    $search = trim((string) $request->input('search', ''));

    if ($search !== '') {
        $like = '%' . $search . '%';

        $query->where(function ($q) use ($like) {
            $q->where('p.pid', 'like', $like)
                ->orWhere('p.pid_sap', 'like', $like)
                ->orWhere('p.project_name', 'like', $like)
                ->orWhere('p.program', 'like', $like)
                ->orWhereExists(function ($l) use ($like) {
                    $l->selectRaw('1')
                        ->from('lops as lx')
                        ->whereColumn('lx.project_id', 'p.id_project')
                        ->where(function ($x) use ($like) {
                            $x->where('lx.lop_name', 'like', $like)
                                ->orWhere('lx.id_ihld', 'like', $like)
                                ->orWhere('lx.branch', 'like', $like)
                                ->orWhere('lx.sto', 'like', $like);
                        });
                });
        });
    }

    if ($request->filled('region')) {
        $region = strtoupper(trim((string) $request->region));

        if (isset($regions[$region])) {
            $branches = $regions[$region];

            $query->whereExists(function ($l) use ($branches) {
                $l->selectRaw('1')
                    ->from('lops as lx')
                    ->whereColumn('lx.project_id', 'p.id_project')
                    ->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(TRIM(lx.branch))'), $branches);
            });
        }
    }

    if ($request->filled('branch')) {
        $branch = strtoupper(trim((string) $request->branch));

        $query->whereExists(function ($l) use ($branch) {
            $l->selectRaw('1')
                ->from('lops as lx')
                ->whereColumn('lx.project_id', 'p.id_project')
                ->whereRaw('UPPER(TRIM(lx.branch)) = ?', [$branch]);
        });
    }

    if ($request->filled('program')) {
        $query->where('p.program', $request->program);
    }

    if ($request->filled('status_project')) {
        $query->where('p.status_project', $request->status_project);
    }
}

private function applyPt2PidFilters($query, \Illuminate\Http\Request $request, array $regions): void
{
    $search = trim((string) $request->input('search', ''));

    if ($search !== '') {
        $like = '%' . $search . '%';

        $query->where(function ($q) use ($like) {
            $q->where('p.pid', 'like', $like)
                ->orWhere('p.pid_sap', 'like', $like)
                ->orWhere('p.project_name', 'like', $like)
                ->orWhere('p.program', 'like', $like)
                ->orWhereExists(function ($l) use ($like) {
                    $l->selectRaw('1')
                        ->from('pt2_lops as lx')
                        ->whereColumn('lx.pt2_project_id', 'p.id_pt2_project')
                        ->where(function ($x) use ($like) {
                            $x->where('lx.lop_name', 'like', $like)
                                ->orWhere('lx.id_ihld', 'like', $like)
                                ->orWhere('lx.branch', 'like', $like)
                                ->orWhere('lx.sto', 'like', $like);
                        });
                });
        });
    }

    if ($request->filled('region')) {
        $region = strtoupper(trim((string) $request->region));

        if (isset($regions[$region])) {
            $branches = $regions[$region];

            $query->whereExists(function ($l) use ($branches) {
                $l->selectRaw('1')
                    ->from('pt2_lops as lx')
                    ->whereColumn('lx.pt2_project_id', 'p.id_pt2_project')
                    ->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(TRIM(lx.branch))'), $branches);
            });
        }
    }

    if ($request->filled('branch')) {
        $branch = strtoupper(trim((string) $request->branch));

        $query->whereExists(function ($l) use ($branch) {
            $l->selectRaw('1')
                ->from('pt2_lops as lx')
                ->whereColumn('lx.pt2_project_id', 'p.id_pt2_project')
                ->whereRaw('UPPER(TRIM(lx.branch)) = ?', [$branch]);
        });
    }

    if ($request->filled('program')) {
        $query->where('p.program', $request->program);
    }

    if ($request->filled('status_project')) {
        $query->where('p.status_project', $request->status_project);
    }
}

private function buildRegularPidMatrix(array $regions, $programs): array
{
    $programList = collect($programs)->values()->all();
    $programSet = array_fill_keys($programList, true);

    $branchToRegion = [];
    foreach ($regions as $region => $branches) {
        foreach ($branches as $branch) {
            $branchToRegion[$branch] = $region;
        }
    }

    $emptyStats = static fn () => [
        'init' => 0,
        'active' => 0,
        'close' => 0,
        'bast' => 0,
        'drop' => 0,
    ];

    $acc = [];
    $grandTotals = [];

    foreach ($regions as $region => $branches) {
        $acc[$region] = [
            'programs' => [],
            'branches' => [],
        ];

        foreach ($programList as $program) {
            $acc[$region]['programs'][$program] = $emptyStats();
            $grandTotals[$program] = $grandTotals[$program] ?? $emptyStats();
        }
    }

    $rows = \Illuminate\Support\Facades\DB::table('projects as p')
        ->join('lops as l', 'l.project_id', '=', 'p.id_project')
        ->whereNotNull('p.program')
        ->where('p.program', '!=', '')
        ->whereRaw("UPPER(TRIM(p.program)) <> 'PT 2'")
        ->distinct()
        ->get([
            'p.id_project',
            'p.program',
            'p.status_project',
            'l.branch',
        ]);

    foreach ($rows as $row) {
        $branch = strtoupper(trim((string) $row->branch));
        $region = $branchToRegion[$branch] ?? null;
        $program = trim((string) $row->program);
        $status = strtolower(trim((string) $row->status_project));

        if (!$region || !isset($programSet[$program]) || !in_array($status, ['init', 'active', 'close', 'bast', 'drop'], true)) {
            continue;
        }

        $acc[$region]['programs'][$program][$status]++;
        $grandTotals[$program][$status]++;

        if (!isset($acc[$region]['branches'][$branch])) {
            $acc[$region]['branches'][$branch] = [];
            foreach ($programList as $p) {
                $acc[$region]['branches'][$branch][$p] = $emptyStats();
            }
        }

        $acc[$region]['branches'][$branch][$program][$status]++;
    }

    $matrixData = [];

    foreach ($regions as $region => $branches) {
        $branchRows = [];

        foreach ($branches as $branch) {
            if (!isset($acc[$region]['branches'][$branch])) {
                continue;
            }

            $branchRows[] = [
                'name' => $branch,
                'programs' => $acc[$region]['branches'][$branch],
            ];
        }

        $matrixData[] = [
            'region' => $region,
            'programs' => $acc[$region]['programs'],
            'branches' => $branchRows,
        ];
    }

    return [$matrixData, $grandTotals];
}

    public function boqIndex()
    {
        $customers = Customer::query()
            ->active()
            ->orderBy('customer_name')
            ->get([
                'id_customer',
                'customer_name',
            ]);

        $packages = PackageModel::query()
            ->orderBy('package_name')
            ->get([
                'id_package',
                'customer_id',
                'package_code',
                'package_name',
            ]);

        // 5 upload BOQ terakhir dari semua user.
        $importProcesses = ImportProcess::query()
            ->with('uploader')
            ->where('import_type', ImportProcess::TYPE_BOQ)
            ->latest('id_import')
            ->limit(5)
            ->get();

        $lastProcess = $importProcesses->first();

        /*
        |--------------------------------------------------------------------------
        | BACKGROUND QUEUE HEALTH
        |--------------------------------------------------------------------------
        | Portable untuk queue driver database maupun redis.
        |--------------------------------------------------------------------------
        */
        $queuedCount = ImportProcess::query()
            ->where('import_type', ImportProcess::TYPE_BOQ)
            ->where('status', ImportProcess::STATUS_QUEUED)
            ->count();

        $processingCount = ImportProcess::query()
            ->where('import_type', ImportProcess::TYPE_BOQ)
            ->where('status', ImportProcess::STATUS_PROCESSING)
            ->count();

        $oldestQueued = ImportProcess::query()
            ->where('import_type', ImportProcess::TYPE_BOQ)
            ->where('status', ImportProcess::STATUS_QUEUED)
            ->oldest('created_at')
            ->first([
                'id_import',
                'created_at',
            ]);

        $queuedTooLong =
            $oldestQueued?->created_at?->lt(now()->subMinutes(5))
            ?? false;

        if ($processingCount > 0) {
            $queueHealth = [
                'state' => 'processing',
                'label' => 'Memproses',
                'description' => 'Background worker sedang memproses import BOQ.',
            ];
        } elseif ($queuedCount > 0 && $queuedTooLong) {
            $queueHealth = [
                'state' => 'warning',
                'label' => 'Perlu Dicek',
                'description' => 'Ada import BOQ menunggu lebih dari 5 menit. Periksa queue worker.',
            ];
        } elseif ($queuedCount > 0) {
            $queueHealth = [
                'state' => 'waiting',
                'label' => 'Menunggu',
                'description' => 'Import BOQ sudah masuk antrean dan menunggu worker.',
            ];
        } else {
            $queueHealth = [
                'state' => 'normal',
                'label' => 'Normal',
                'description' => 'Tidak ada antrean BOQ yang tertahan.',
            ];
        }

        $queueHealth['queued_count'] = $queuedCount;
        $queueHealth['processing_count'] = $processingCount;
        $queueHealth['driver'] = config('queue.default');

        return view(
            'admin.import.boq',
            compact(
                'customers',
                'packages',
                'importProcesses',
                'lastProcess',
                'queueHealth',
            )
        );
    }


    public function importBoq(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:102400',
            ],

            // Mapping PID sengaja tidak dibuka pada UI final.
            'mapping_by' => [
                'required',
                'in:id_ihld,lop_name',
            ],

            'project_type' => [
                'required',
                'in:internal,external,pt2',
            ],

            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id_customer',
            ],

            'package_id' => [
                'required',
                'integer',
                'exists:packages,id_package',
            ],
        ]);

        $projectType = $validated['project_type'];

        if (in_array($projectType, ['internal', 'pt2'], true)) {
            $customerId = 1;
        } else {
            $customerId = isset($validated['customer_id'])
                ? (int) $validated['customer_id']
                : null;

            if (!$customerId || $customerId === 1) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Customer Exbis wajib dipilih.',
                ]);
            }
        }

        $package = PackageModel::query()
            ->where('id_package', $validated['package_id'])
            ->where('customer_id', $customerId)
            ->first();

        if (!$package) {
            throw ValidationException::withMessages([
                'package_id' => 'Package tidak valid atau tidak sesuai dengan customer yang dipilih.',
            ]);
        }

        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();

        $storedPath = null;
        $import = null;

        try {
            $storedPath = $file->store(
                'imports/boq',
                'local'
            );

            $currentUserId =
                auth()->user()->id_user
                ?? auth()->id();

            $import = ImportProcess::query()->create([
                'uuid' => (string) Str::uuid(),

                'import_type' => ImportProcess::TYPE_BOQ,
                'project_type' => $projectType,
                'customer_id' => $customerId,

                'original_file_name' => $originalFileName,
                'stored_file_path' => $storedPath,
                'disk' => 'local',

                'status' => ImportProcess::STATUS_QUEUED,
                'current_stage' => 'Menunggu background worker',
                'progress' => 0,

                'total_rows' => 0,
                'processed_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,

                'created_count' => 0,
                'updated_count' => 0,
                'unchanged_count' => 0,
                'skipped_count' => 0,

                'summary' => [
                    'options' => [
                        'mapping_by' => $validated['mapping_by'],
                        'package_id' => (int) $package->id_package,
                        'package_code' => $package->package_code,
                        'package_name' => $package->package_name,
                    ],
                ],

                'error_message' => null,
                'uploaded_by' => $currentUserId,
            ]);

            ProcessBoqImportJob::dispatch(
                $import->id_import
            );

            return redirect()
                ->route(
                    'admin.import.boq',
                    [
                        'import_uuid' => $import->uuid,
                    ]
                )
                ->with(
                    'success',
                    "File {$originalFileName} berhasil diterima dan masuk antrean import BOQ."
                );

        } catch (\Throwable $e) {
            if ($import) {
                $import->update([
                    'status' => ImportProcess::STATUS_FAILED,
                    'current_stage' => 'Gagal menyiapkan background import BOQ',
                    'error_message' => mb_substr(
                        $e->getMessage(),
                        0,
                        65000
                    ),
                    'finished_at' => now(),
                ]);
            } elseif ($storedPath) {
                Storage::disk('local')
                    ->delete($storedPath);
            }

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'File gagal masuk antrean import BOQ: '
                    . $e->getMessage()
                );
        }
    }


    public function importBoqStatus(string $uuid)
    {
        /*
        |--------------------------------------------------------------------------
        | AUTHORIZATION
        |--------------------------------------------------------------------------
        | History BOQ bersifat global untuk user yang punya akses modul Admin Import.
        | Karena itu endpoint tidak dibatasi uploaded_by.
        | Route ini HARUS berada pada middleware/permission Admin Import yang sama.
        |--------------------------------------------------------------------------
        */
        $import = ImportProcess::query()
            ->with('uploader')
            ->where('uuid', $uuid)
            ->where('import_type', ImportProcess::TYPE_BOQ)
            ->firstOrFail();

        $errors = [];

        if ($import->isFinished()) {
            $errors = $import->errors()
                ->orderBy('row_number')
                ->limit(10)
                ->get([
                    'row_number',
                    'pid_sap',
                    'id_ihld',
                    'nama_lop',
                    'error_code',
                    'message',
                    'row_data',
                ])
                ->map(function ($error) {
                    $rowData = is_array($error->row_data)
                        ? $error->row_data
                        : [];

                    return [
                        'row_number' => $error->row_number,
                        'pid_sap' => $error->pid_sap,
                        'id_ihld' => $error->id_ihld,
                        'nama_lop' => $error->nama_lop,
                        'error_code' => $error->error_code,
                        'message' => $error->message,

                        // BOQ-specific preview fields.
                        'type' => $rowData['type'] ?? null,
                        'header' => $rowData['header'] ?? null,
                        'designator' => $rowData['designator'] ?? null,
                        'qty' => $rowData['qty'] ?? null,
                    ];
                })
                ->values();
        }

        $uploaderName =
            $import->uploader?->name
            ?? $import->uploader?->full_name
            ?? $import->uploader?->username
            ?? $import->uploader?->email
            ?? (
                $import->uploaded_by
                    ? 'User #' . $import->uploaded_by
                    : '-'
            );

        return response()->json([
            'success' => true,

            'data' => [
                'uuid' => $import->uuid,
                'file_name' => $import->original_file_name,

                'import_type' => $import->import_type,
                'project_type' => $import->project_type,
                'customer_id' => $import->customer_id,

                'status' => $import->status,
                'stage' => $import->current_stage,
                'progress' => min(
                    100,
                    max(
                        0,
                        (int) $import->progress
                    )
                ),

                'total_rows' => (int) $import->total_rows,
                'processed_rows' => (int) $import->processed_rows,
                'valid_rows' => (int) $import->valid_rows,
                'invalid_rows' => (int) $import->invalid_rows,

                'created_count' => (int) $import->created_count,
                'updated_count' => (int) $import->updated_count,
                'unchanged_count' => (int) $import->unchanged_count,
                'skipped_count' => (int) $import->skipped_count,

                'summary' => $import->summary ?? [],
                'error_message' => $import->error_message,

                'uploaded_by' => $import->uploaded_by,

                'uploader' => [
                    'id' => $import->uploaded_by,
                    'name' => $uploaderName,
                    'email' => $import->uploader?->email,
                ],

                'started_at' => optional(
                    $import->started_at
                )?->format('Y-m-d H:i:s'),

                'finished_at' => optional(
                    $import->finished_at
                )?->format('Y-m-d H:i:s'),

                'errors' => $errors,

                'error_download_url' =>
                    (int) $import->invalid_rows > 0
                        ? route(
                            'admin.import.boq.errors.download',
                            $import->uuid
                        )
                        : null,
            ],
        ])->header(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0'
        );
    }


    public function downloadBoqImportErrors(string $uuid)
    {
        $import = ImportProcess::query()
            ->where('uuid', $uuid)
            ->where('import_type', ImportProcess::TYPE_BOQ)
            ->firstOrFail();

        if ((int) $import->invalid_rows <= 0) {
            abort(
                404,
                'Import BOQ ini tidak memiliki data invalid.'
            );
        }

        $downloadName =
            'boq-import-errors-'
            . $import->uuid
            . '.csv';

        return response()->streamDownload(
            function () use ($import) {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                // UTF-8 BOM agar Excel Windows membaca karakter dengan baik.
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $handle,
                    [
                        'Type',
                        'Header / LOP',
                        'Row',
                        'PID SAP',
                        'ID IHLD',
                        'Nama LOP',
                        'Designator',
                        'Qty',
                        'Error Code',
                        'Keterangan',
                    ]
                );

                $import->errors()
                    ->orderBy('row_number')
                    ->chunkById(
                        500,
                        function ($errors) use ($handle) {
                            foreach ($errors as $error) {
                                $rowData =
                                    is_array($error->row_data)
                                        ? $error->row_data
                                        : [];

                                fputcsv(
                                    $handle,
                                    [
                                        $rowData['type'] ?? null,
                                        $rowData['header'] ?? null,
                                        $error->row_number,
                                        $error->pid_sap,
                                        $error->id_ihld,
                                        $error->nama_lop,
                                        $rowData['designator'] ?? null,
                                        $rowData['qty'] ?? null,
                                        $error->error_code,
                                        $error->message,
                                    ]
                                );
                            }
                        },
                        'id_error'
                    );

                fclose($handle);
            },
            $downloadName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }

    public function dataBoq(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $package = $request->input('package');

        /*
        |--------------------------------------------------------------------------
        | TABLE NAME
        |--------------------------------------------------------------------------
        | Pakai nama table dari Model agar aman jika nama tabel custom.
        */
        $lopTable = (new Lop())->getTable();
        $projectTable = (new Project())->getTable();
        $packageTable = (new PackageModel())->getTable();
        $boqTable = (new BoqItem())->getTable();
        $designatorTable = (new Designator())->getTable();
        $priceTable = (new DesignatorPackagePrice())->getTable();

        /*
        |--------------------------------------------------------------------------
        | CURRENT PACKAGE PRICE
        |--------------------------------------------------------------------------
        |
        | Harga tidak menggunakan boq_items.unit_price / total_price.
        |
        | Harga selalu dihitung ulang dari:
        |
        | designator_package_price.price × boq_items.quantity_plan
        |
        | Jika tanpa sengaja ada lebih dari 1 price untuk kombinasi
        | designator + package, gunakan record id_price terakhir.
        |
        */
        $latestPriceIdSub = DB::table($priceTable)
            ->selectRaw('MAX(id_price) AS id_price')
            ->groupBy(
                'designator_id',
                'package_id'
            );

        $currentPriceSub = DB::table("{$priceTable} as dpp")
            ->joinSub(
                $latestPriceIdSub,
                'latest_price',
                function ($join) {
                    $join->on(
                        'latest_price.id_price',
                        '=',
                        'dpp.id_price'
                    );
                }
            )
            ->select([
                'dpp.designator_id',
                'dpp.package_id',
            ])
            ->selectRaw("
                CAST(
                    NULLIF(dpp.price, '')
                    AS DECIMAL(20,2)
                ) AS price
            ");

        /*
        |--------------------------------------------------------------------------
        | AGGREGATE BOQ PER LOP
        |--------------------------------------------------------------------------
        |
        | Query ini menghasilkan:
        |
        | lop_id
        | item_count
        | total_jasa
        | total_material
        |
        | Tidak mengambil seluruh boq_items ke PHP.
        |
        */
        $boqTotalsSub = DB::table("{$boqTable} as bi")
            ->join(
                "{$lopTable} as bl",
                'bl.id_lop',
                '=',
                'bi.lop_id'
            )
            ->join(
                "{$designatorTable} as d",
                'd.id_designator',
                '=',
                'bi.designator_id'
            )
            ->leftJoinSub(
                clone $currentPriceSub,
                'cp',
                function ($join) {
                    $join->on(
                        'cp.designator_id',
                        '=',
                        'bi.designator_id'
                    );

                    $join->on(
                        'cp.package_id',
                        '=',
                        'bl.package_id'
                    );
                }
            )
            ->select('bi.lop_id')

            ->selectRaw("
                COUNT(*) AS item_count
            ")

            ->selectRaw("
                SUM(
                    CASE
                        WHEN d.type = 'jasa'
                        THEN
                            COALESCE(bi.quantity_plan, 0)
                            *
                            COALESCE(cp.price, 0)
                        ELSE 0
                    END
                ) AS total_jasa
            ")

            ->selectRaw("
                SUM(
                    CASE
                        WHEN d.type = 'material'
                        THEN
                            COALESCE(bi.quantity_plan, 0)
                            *
                            COALESCE(cp.price, 0)
                        ELSE 0
                    END
                ) AS total_material
            ")

            ->groupBy('bi.lop_id');

        /*
        |--------------------------------------------------------------------------
        | LIST LOP
        |--------------------------------------------------------------------------
        |
        | Satu query utama.
        | Tidak eager-load boqItems seluruhnya.
        |
        */
        $lops = Lop::query()
            ->from("{$lopTable} as l")

            ->joinSub(
                clone $boqTotalsSub,
                'bt',
                function ($join) {
                    $join->on(
                        'bt.lop_id',
                        '=',
                        'l.id_lop'
                    );
                }
            )

            ->leftJoin(
                "{$projectTable} as p",
                'p.id_project',
                '=',
                'l.project_id'
            )

            ->leftJoin(
                "{$packageTable} as pkg",
                'pkg.id_package',
                '=',
                'l.package_id'
            )

            ->select([
                'l.id_lop',
                'l.project_id',
                'l.id_ihld',
                'l.lop_name',
                'l.package_id',
                'l.branch',
                'l.sto',
                'l.pid_sap',

                'p.pid as project_pid',
                'p.pid_sap as project_pid_sap',
                'p.project_name',
                'p.mitra_name',

                'pkg.package_name',

                'bt.item_count',
                'bt.total_jasa',
                'bt.total_material',
            ])

            /*
            |--------------------------------------------------------------------------
            | SEARCH
            |--------------------------------------------------------------------------
            */
            ->when(
                $search !== '',
                function ($query) use ($search) {

                    $keyword = "%{$search}%";

                    $query->where(
                        function ($q) use ($keyword) {

                            $q->where(
                                'l.lop_name',
                                'like',
                                $keyword
                            )

                            ->orWhere(
                                'l.id_ihld',
                                'like',
                                $keyword
                            )

                            ->orWhere(
                                'l.sto',
                                'like',
                                $keyword
                            )

                            ->orWhere(
                                'l.branch',
                                'like',
                                $keyword
                            )

                            ->orWhere(
                                'p.mitra_name',
                                'like',
                                $keyword
                            )

                            ->orWhere(
                                'p.pid',
                                'like',
                                $keyword
                            )

                            ->orWhere(
                                'p.pid_sap',
                                'like',
                                $keyword
                            )

                            ->orWhere(
                                'p.project_name',
                                'like',
                                $keyword
                            );
                        }
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | PACKAGE FILTER
            |--------------------------------------------------------------------------
            */
            ->when(
                !empty($package),
                function ($query) use ($package) {
                    $query->where(
                        'l.package_id',
                        $package
                    );
                }
            )

            ->orderByDesc('l.id_lop')

            ->paginate(10)

            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | DETAIL BOQ UNTUK MODAL
        |--------------------------------------------------------------------------
        |
        | Jangan eager-load detail seluruh database.
        |
        | Ambil boq_items hanya untuk 10 LOP pada halaman aktif.
        |
        | Jadi meskipun DB memiliki puluhan/ratusan ribu BOQ,
        | detail yang ditarik hanya milik current page.
        |
        */
        $lopIds = $lops
            ->getCollection()
            ->pluck('id_lop')
            ->filter()
            ->values();

        $boqItemsByLop = collect();

        if ($lopIds->isNotEmpty()) {

            $detailRows = DB::table("{$boqTable} as bi")

                ->join(
                    "{$lopTable} as l",
                    'l.id_lop',
                    '=',
                    'bi.lop_id'
                )

                ->join(
                    "{$designatorTable} as d",
                    'd.id_designator',
                    '=',
                    'bi.designator_id'
                )

                ->leftJoinSub(
                    clone $currentPriceSub,
                    'cp',
                    function ($join) {

                        $join->on(
                            'cp.designator_id',
                            '=',
                            'bi.designator_id'
                        );

                        $join->on(
                            'cp.package_id',
                            '=',
                            'l.package_id'
                        );
                    }
                )

                ->whereIn(
                    'bi.lop_id',
                    $lopIds
                )

                ->select([
                    'bi.id_boq',
                    'bi.lop_id',
                    'bi.designator_id',

                    'd.designator',
                    'd.type',
                    'd.item_name',
                    'd.unit',

                    'bi.quantity_plan',
                    'bi.quantity_actual',
                ])

                ->selectRaw("
                    COALESCE(cp.price, 0)
                    AS unit_price
                ")

                ->selectRaw("
                    (
                        COALESCE(bi.quantity_plan, 0)
                        *
                        COALESCE(cp.price, 0)
                    ) AS total_price
                ")

                ->orderBy('bi.lop_id')
                ->orderBy('d.type')
                ->orderBy('d.designator')

                ->get();

            $boqItemsByLop = $detailRows
                ->groupBy('lop_id');
        }

        /*
        |--------------------------------------------------------------------------
        | GLOBAL SUMMARY
        |--------------------------------------------------------------------------
        |
        | Gunakan aggregate SQL yang sama.
        | Tidak load seluruh boq_items.
        |
        */
        $summary = DB::query()
            ->fromSub(
                clone $boqTotalsSub,
                'bt'
            )
            ->selectRaw("
                COUNT(*) AS total_lop_boq,

                COALESCE(
                    SUM(bt.total_jasa),
                    0
                ) AS total_jasa,

                COALESCE(
                    SUM(bt.total_material),
                    0
                ) AS total_material,

                COALESCE(
                    SUM(
                        bt.total_jasa
                        +
                        bt.total_material
                    ),
                    0
                ) AS total_boq
            ")
            ->first();

        $totalLopBoq =
            (int) ($summary->total_lop_boq ?? 0);

        $totalJasaValue =
            (float) ($summary->total_jasa ?? 0);

        $totalMaterialValue =
            (float) ($summary->total_material ?? 0);

        $totalBoqValue =
            (float) ($summary->total_boq ?? 0);

        /*
        |--------------------------------------------------------------------------
        | ASSIGNMENT SUMMARY
        |--------------------------------------------------------------------------
        |
        | Hitung dalam 1 query.
        |
        */
        $assignmentStats = DB::table("{$lopTable} as l")

            ->leftJoin(
                'pro_assign as pa',
                'pa.project_id',
                '=',
                'l.project_id'
            )

            ->whereExists(
                function ($query) use ($boqTable) {

                    $query
                        ->selectRaw('1')
                        ->from("{$boqTable} as bi")
                        ->whereColumn(
                            'bi.lop_id',
                            'l.id_lop'
                        );
                }
            )

            ->selectRaw("
                COUNT(
                    DISTINCT CASE
                        WHEN pa.project_id IS NOT NULL
                        THEN l.id_lop
                    END
                ) AS sudah_assign
            ")

            ->selectRaw("
                COUNT(
                    DISTINCT CASE
                        WHEN pa.project_id IS NULL
                        THEN l.id_lop
                    END
                ) AS belum_assign
            ")

            ->first();

        $sudahAssign =
            (int) ($assignmentStats->sudah_assign ?? 0);

        $belumAssign =
            (int) ($assignmentStats->belum_assign ?? 0);

        /*
        |--------------------------------------------------------------------------
        | PACKAGE FILTER OPTIONS
        |--------------------------------------------------------------------------
        */
        $packages = PackageModel::query()
            ->orderBy('package_name')
            ->get([
                'id_package',
                'package_name',
            ]);

        return view(
            'admin.import.data-boq',
            compact(
                'lops',
                'boqItemsByLop',

                'packages',
                'search',
                'package',

                'totalLopBoq',

                'totalJasaValue',
                'totalMaterialValue',
                'totalBoqValue',

                'sudahAssign',
                'belumAssign'
            )
        );
    }

    public function downloadPidTemplate()
    {
        $headers = [
            'pid',
            'pid_sap',
            'nama_lop',
            'program',
            'execution_type',
            'status_project',
            'id_ihld',
            'tematik',
            'sto',
            'branch',
            'batch',
            'no_sp',
            'tgl_sp',
            'tgl_toc',
            'mitra_name',
        ];

        $sample = [
            'PID001',
            'SAP001',
            'LOP AREA 1',
            'OSP',
            'kemitraan',
            'active',
            'IHLD001',
            'FTTH',
            'SDA',
            'SURABAYA',
            'BATCH 1',
            'SP001',
            '2026-06-23',
            '2026-06-30',
            'MITRA A',
        ];

        $filename = 'template_import_pid.csv';

        $callback = function () use ($headers, $sample) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, $sample);
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function downloadBoqTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PAKET 5');

        $sheet->setCellValue('A1', 'DESIGNATOR');
        $sheet->setCellValue('B1', 'PID_SAP_001');
        $sheet->setCellValue('C1', 'PID_SAP_002');
        $sheet->setCellValue('D1', 'PID_SAP_003');

        $designators = Designator::query()
            ->forCustomer($this->defaultCustomerId())
            ->whereNotNull('pair_code')
            ->select('pair_code')
            ->distinct()
            ->orderBy('pair_code')
            ->get();

        $row = 2;

        foreach ($designators as $designator) {
            $sheet->setCellValue("A{$row}", $designator->pair_code);
            $sheet->setCellValue("B{$row}", 0);
            $sheet->setCellValue("C{$row}", 0);
            $sheet->setCellValue("D{$row}", 0);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);

        $sheet->freezePane('B2');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $fileName = 'template_import_boq.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    //HELPER PID
    private function defaultCustomerId(): ?int
    {
        return DB::table('customers')
            ->where('customer_code', 'TIF')
            ->value('id_customer');
    }

    private function cleanValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function cleanDecimal($value)
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return null;
        }

        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? $value : null;
    }


    //HELPER LOP
    private function cleanNumber($value)
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return 0;
        }

        $value = str_replace(['.', ','], ['', '.'], $value);

        return is_numeric($value) ? $value : 0;
    }

    private function cleanDate($value)
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
