<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Lop;
use App\Models\BoqItem;
use App\Models\Designator;
use App\Models\Evidence;
use App\Models\ProjectAssignment;
use App\Models\ImportLog;
use App\Models\Package as PackageModel;
use App\Models\DesignatorPackagePrice;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportController extends Controller
{
    public function pidIndex(Request $request)
    {
        $lastImport = ImportLog::with('uploader')
            ->where('type', 'pid')
            ->latest()
            ->first();

        $importLogs = ImportLog::with('uploader')
            ->where('type', 'pid')
            ->latest()
            ->take(2)
            ->get();

        return view('admin.import.pid', compact(
            'lastImport',
            'importLogs'
        ));
    }

    public function importPid(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $headers = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);

            $header = strtolower(
                trim((string) $sheet->getCell($columnLetter . '1')->getValue())
            );

            $headers[$col] = $header;
        }

        $requiredHeaders = [
            'pid_sap',
            'id_ihld',
            'nama_lop',
        ];

        $missingHeaders = [];

        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $headers)) {
                $missingHeaders[] = $requiredHeader;
            }
        }

        if (!empty($missingHeaders)) {
            return back()
                ->with('import_result', [
                    'file_name' => $fileName,
                    'total_rows' => max($highestRow - 1, 0),
                    'valid_rows' => 0,
                    'invalid_rows_count' => 0,
                    'invalid_rows' => [],
                    'missing_headers' => $missingHeaders,
                    'imported' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'project_imported' => 0,
                    'project_updated' => 0,
                    'lop_imported' => 0,
                    'lop_updated' => 0,
                ])
                ->with('error', 'Import gagal. Header wajib tidak ditemukan: ' . implode(', ', $missingHeaders));
        }

        $projectImported = 0;
        $projectUpdated = 0;
        $lopImported = 0;
        $lopUpdated = 0;
        $skipped = 0;
        $validRows = 0;

        $invalidRows = [];
        $pidSapTracker = [];

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

            /*
            | PID di file boleh kosong.
            | Karena mandatory utama adalah PID SAP + Nama LOP,
            | maka pid project fallback ke PID SAP agar create project tetap aman.
            */
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
        }

        ImportLog::create([
            'type' => 'pid',
            'file_name' => $fileName,
            'uploaded_by' => auth()->user()->id_user ?? auth()->id(),
            'total_rows' => max($highestRow - 1, 0),
            'imported' => $projectImported,
            'updated' => $projectUpdated,
            'skipped' => $skipped,
            'status' => 'success',
        ]);

        return back()
            ->with('import_result', [
                'file_name' => $fileName,
                'total_rows' => max($highestRow - 1, 0),
                'valid_rows' => $validRows,
                'invalid_rows_count' => count($invalidRows),
                'invalid_rows' => array_slice($invalidRows, 0, 10),
                'missing_headers' => [],
                'processed_rows' => $validRows,
                'imported' => $projectImported,
                'updated' => $projectUpdated,
                'skipped' => $skipped,
                'project_imported' => $projectImported,
                'project_updated' => $projectUpdated,
                'lop_imported' => $lopImported,
                'lop_updated' => $lopUpdated,
            ])
            ->with(
                'success',
                "Import PID selesai. Project Baru {$projectImported}, Update Project {$projectUpdated}, LOP Baru {$lopImported}, Update LOP {$lopUpdated}, Data di Skip {$skipped}."
            );
    }

    public function updatePid(Request $request, Project $project)
    {
        $request->validate([
            'pid' => 'required|string|max:100',
            'pid_sap' => 'nullable|string|max:100',
            'nama_lop' => 'required|string|max:255',
            'program' => 'nullable|string|max:150',
            'execution_type' => 'required|in:kemitraan,swakelola,turnkey',
            'status_project' => 'required|in:init,active,close,bast',

            'id_ihld' => 'nullable|string|max:100',
            'tematik' => 'nullable|string|max:150',
            'sto' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:100',
            'batch' => 'nullable|string|max:100',
            'no_sp' => 'nullable|string|max:100',
            'tgl_sp' => 'nullable|date',
            'tgl_toc' => 'nullable|date',
            'mitra_name' => 'nullable|string|max:150',
        ]);

        $project->update([
            'pid' => $request->pid,
            'pid_sap' => $request->pid_sap,
            'project_name' => $request->nama_lop,
            'program' => $request->program,
            'execution_type' => $request->execution_type,
            'status_project' => $request->status_project,
        ]);

        $lop = $project->lop;

        if ($lop) {
            $lop->update([
                'id_ihld' => $request->id_ihld,
                'lop_name' => $request->nama_lop,
                'pid_sap' => $request->pid_sap,
                'program_sap' => $request->program,
                'tematik' => $request->tematik,
                'sto' => $request->sto,
                'branch' => $request->branch,
                'batch' => $request->batch,
                'no_sp' => $request->no_sp,
                'tgl_sp' => $request->tgl_sp,
                'tgl_toc' => $request->tgl_toc,
                'mitra_name' => $request->mitra_name,
                'mapping_status' => 'auto_matched',
            ]);
        } else {
            Lop::create([
                'project_id' => $project->id_project,
                'id_ihld' => $request->id_ihld,
                'lop_name' => $request->nama_lop,
                'pid_sap' => $request->pid_sap,
                'program_sap' => $request->program,
                'tematik' => $request->tematik,
                'sto' => $request->sto,
                'branch' => $request->branch,
                'batch' => $request->batch,
                'no_sp' => $request->no_sp,
                'tgl_sp' => $request->tgl_sp,
                'tgl_toc' => $request->tgl_toc,
                'mitra_name' => $request->mitra_name,
                'mapping_status' => 'auto_matched',
                'status_progress' => 'preparation',
            ]);
        }

        return back()->with('success', 'Data PID dan LOP berhasil diperbarui.');
    }

    public function destroyPid(Project $project)
    {
        $evidenceCount = Evidence::where(
            'project_id',
            $project->id_project
        )->count();

        if ($evidenceCount > 0) {

            return back()->with(
                'error',
                'Project tidak dapat dihapus karena sudah memiliki evidence.'
            );
        }

        BoqItem::where(
            'project_id',
            $project->id_project
        )->delete();

        ProjectAssignment::where(
            'project_id',
            $project->id_project
        )->delete();

        Lop::where(
            'project_id',
            $project->id_project
        )->delete();

        $project->delete();

        return back()->with(
            'success',
            'Project berhasil dihapus.'
        );
    }

    //IMPORT LOP
    // public function lopIndex(Request $request)
    // {
    //     $search = $request->search;

    //     $lops = Lop::with('project')
    //         ->when($search, function ($query) use ($search) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('lop_name', 'like', "%{$search}%")
    //                 ->orWhere('pid_sap', 'like', "%{$search}%")
    //                 ->orWhere('program_sap', 'like', "%{$search}%")
    //                 ->orWhere('sto', 'like', "%{$search}%")
    //                 ->orWhere('branch', 'like', "%{$search}%")
    //                 ->orWhere('wo_smile', 'like', "%{$search}%")
    //                 ->orWhere('mitra_name', 'like', "%{$search}%")
    //                 ->orWhere('mapping_status', 'like', "%{$search}%");
    //             });
    //         })
    //         ->latest()
    //         ->paginate(10)
    //         ->withQueryString();

    //     return view('admin.import.lop', compact('lops', 'search'));
    // }

    // public function importLop(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:csv,txt',
    //     ]);

    //     $file = fopen($request->file('file')->getRealPath(), 'r');

    //     $header = fgetcsv($file);
    //     $header = array_map('trim', $header);

    //     $imported = 0;
    //     $updated = 0;
    //     $mapped = 0;
    //     $unmapped = 0;

    //     while (($row = fgetcsv($file, 10000, ",")) !== false) {

    //         if (
    //             count($row) === 1 &&
    //             (
    //                 $row[0] === null ||
    //                 trim($row[0]) === ''
    //             )
    //         ) {
    //             continue;
    //         }

    //         if (count($row) !== count($header)) {
    //             continue;
    //         }

    //         $data = array_combine($header, $row);

    //         $lopName = $this->cleanValue($data['lop_name'] ?? null);
    //         $pidSap = $this->cleanValue($data['pid_sap'] ?? null);
    //         $woSmile = $this->cleanValue($data['wo_smile'] ?? null);

    //         if (!$lopName && !$pidSap && !$woSmile) {
    //             continue;
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | AUTO MATCHING PROJECT
    //         |--------------------------------------------------------------------------
    //         */

    //         $lopNameNormalized = strtolower(trim($lopName));

    //         $programNormalized = strtolower(
    //             trim(
    //                 $this->cleanValue($data['program_sap'] ?? null)
    //             )
    //         );

    //         $project = Project::all()->first(function ($project) use (
    //             $lopNameNormalized,
    //             $programNormalized
    //         ) {
    //             return strtolower(trim($project->project_name)) === $lopNameNormalized
    //                 && strtolower(trim($project->program)) === $programNormalized;
    //         });

    //         $mappingStatus = $project
    //             ? 'auto_matched'
    //             : 'unmapped';

    //         if ($project) {
    //             $mapped++;
    //         } else {
    //             $unmapped++;
    //         }

    //         $matchedPidSap = $project?->pid_sap;

    //         $payload = [
    //             'project_id' => $project?->id_project,

    //             'id_ihld' => $this->cleanValue($data['id_ihld'] ?? null),
    //             'lop_name' => $lopName,
    //             'pid_sap' => $matchedPidSap ?? $pidSap,
    //             'program_sap' => $this->cleanValue($data['program_sap'] ?? null),
    //             'tematik' => $this->cleanValue($data['tematik'] ?? null),

    //             'sto' => $this->cleanValue($data['sto'] ?? null),
    //             'branch' => $this->cleanValue($data['branch'] ?? null),
    //             'tahun_order' => $this->cleanValue($data['tahun_order'] ?? null),
    //             'start_tgl' => $this->cleanDate($data['start_tgl'] ?? null),
    //             'wo_smile' => $woSmile,

    //             'nilai_material' => $this->cleanNumber($data['nilai_material'] ?? 0),
    //             'nilai_jasa' => $this->cleanNumber($data['nilai_jasa'] ?? 0),
    //             'nilai_total' => $this->cleanNumber($data['nilai_total'] ?? 0),

    //             'odp_8' => $this->cleanNumber($data['odp_8'] ?? 0),
    //             'odp_16' => $this->cleanNumber($data['odp_16'] ?? 0),
    //             'total_port' => $this->cleanNumber($data['total_port'] ?? 0),

    //             'plan_tiang' => $this->cleanNumber($data['plan_tiang'] ?? 0),
    //             'realisasi_tiang' => $this->cleanNumber($data['realisasi_tiang'] ?? 0),
    //             'plan_kabel' => $this->cleanNumber($data['plan_kabel'] ?? 0),
    //             'realisasi_kabel' => $this->cleanNumber($data['realisasi_kabel'] ?? 0),
    //             'plan_galian' => $this->cleanNumber($data['plan_galian'] ?? 0),
    //             'real_galian' => $this->cleanNumber($data['real_galian'] ?? 0),

    //             'status_progress' => $this->cleanValue($data['status_progress'] ?? 'preparation') ?: 'preparation',

    //             'nama_waspang' => $this->cleanValue($data['nama_waspang'] ?? null),
    //             'nik_waspang' => $this->cleanValue($data['nik_waspang'] ?? null),
    //             'nama_admin' => $this->cleanValue($data['nama_admin'] ?? null),
    //             'nik_admin' => $this->cleanValue($data['nik_admin'] ?? null),
    //             'mitra_name' => $this->cleanValue($data['mitra_name'] ?? null),

    //             'est_prep' => $this->cleanDate($data['est_prep'] ?? null),
    //             'est_izin' => $this->cleanDate($data['est_izin'] ?? null),
    //             'est_delivery' => $this->cleanDate($data['est_delivery'] ?? null),
    //             'est_instalasi' => $this->cleanDate($data['est_instalasi'] ?? null),
    //             'est_golive' => $this->cleanDate($data['est_golive'] ?? null),

    //             'mapping_status' => $mappingStatus,
    //         ];

    //         $lop = Lop::query()
    //             ->when($woSmile, fn ($q) => $q->orWhere('wo_smile', $woSmile))
    //             ->when($lopName, fn ($q) => $q->orWhere('lop_name', $lopName))
    //             ->first();

    //         if ($lop) {
    //             $lop->update($payload);
    //             $updated++;
    //         } else {
    //             Lop::create($payload);
    //             $imported++;
    //         }
    //     }

    //     fclose($file);

    //     return back()->with(
    //         'success',
    //         "Import LOP selesai. {$imported} data baru, {$updated} diperbarui, {$mapped} auto matched, {$unmapped} unmapped."
    //     );
    // }

    // public function mappingIndex(Request $request)
    // {
    //     $search = $request->search;

    //     $unmappedLops = Lop::where('mapping_status', 'unmapped')
    //         ->when($search, function ($query) use ($search) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('lop_name', 'like', "%{$search}%")
    //                 ->orWhere('pid_sap', 'like', "%{$search}%")
    //                 ->orWhere('program_sap', 'like', "%{$search}%")
    //                 ->orWhere('sto', 'like', "%{$search}%")
    //                 ->orWhere('branch', 'like', "%{$search}%")
    //                 ->orWhere('wo_smile', 'like', "%{$search}%");
    //             });
    //         })
    //         ->latest()
    //         ->paginate(10)
    //         ->withQueryString();

    //     $projects = Project::orderBy('project_name')->get();

    //     return view('admin.import.mapping', compact(
    //         'unmappedLops',
    //         'projects',
    //         'search'
    //     ));
    // }

    // public function saveMapping(Request $request, $id)
    // {
    //     $request->validate([
    //         'project_id' => 'required|exists:projects,id_project',
    //     ]);

    //     $lop = Lop::findOrFail($id);

    //     $project = Project::findOrFail(
    //         $request->project_id
    //     );

    //     $lop->update([

    //         'project_id' => $project->id_project,

    //         'pid_sap' => $project->pid_sap,

    //         'mapping_status' => 'manual_mapped',

    //     ]);

    //     return back()->with(
    //         'success',
    //         'Mapping berhasil disimpan'
    //     );
    // }

    // public function resetMapping($id)
    // {
    //     $lop = Lop::findOrFail($id);

    //     if ($lop->mapping_status !== 'manual_mapped') {
    //         return back()->with('error', 'Hanya mapping manual yang bisa direset');
    //     }

    //     $lop->update([
    //         'project_id' => null,
    //         'pid_sap' => null,
    //         'mapping_status' => 'unmapped',
    //     ]);

    //     return back()->with('success', 'Mapping manual berhasil direset. Silakan mapping ulang.');
    // }

    public function boqIndex()
    {
        $lastImport = ImportLog::with('uploader')
            ->where('type', 'boq')
            ->latest()
            ->first();

        $importLogs = ImportLog::with('uploader')
            ->where('type', 'boq')
            ->latest()
            ->skip(1)
            ->take(2)
            ->get();

        return view('admin.import.boq', compact(
            'lastImport',
            'importLogs'
        ));
    }
    public function importBoq(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'mapping_by' => 'required|in:pid,id_ihld,lop_name',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        $reader = new Xlsx();

        $reader->setReadDataOnly(true);

        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($file->getRealPath());

        $sheet = $spreadsheet->getActiveSheet();

        $sheetName = strtoupper(trim($sheet->getTitle()));

        $package = PackageModel::whereRaw('UPPER(package_name) = ?', [$sheetName])
            ->orWhereRaw('UPPER(package_code) = ?', [$sheetName])
            ->first();

        if (!$package) {
            return back()
                ->with('import_result', [
                    'file_name' => $fileName,
                    'sheet_name' => $sheetName,
                    'status' => 'failed',
                    'error_message' => "Package {$sheetName} belum ada di master package.",
                    'total_headers' => 0,
                    'matched_lop' => 0,
                    'unmapped_lop' => 0,
                    'existing_boq_headers' => 0,
                    'imported' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'unmapped_designator' => 0,
                    'price_missing' => 0,
                    'volume_items' => 0,
                    'invalid_rows' => [],
                ])
                ->with('error', "Package {$sheetName} belum ada di master package.");
        }

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        DB::beginTransaction();

        try {

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        $unmappedLop = 0;
        $unmappedDesignator = 0;
        $priceMissing = 0;
        $matchedLop = 0;
        $volumeItems = 0;

        // Ini yang akan dipakai untuk card "Data Sudah Ada"
        $existingBoqHeaders = 0;

        $matchedHeaders = [];
        $unmatchedHeaders = [];
        $existingHeaders = [];
        $invalidRows = [];

        for ($col = 2; $col <= $highestColumnIndex; $col++) {

            $columnLetter = Coordinate::stringFromColumnIndex($col);

            $headerValue = trim(
                (string) $sheet->getCell($columnLetter . '1')->getValue()
            );

            if ($headerValue === '') {
                continue;
            }

            switch ($request->mapping_by) {

                case 'pid':

                    $project = Project::where('pid', $headerValue)
                        ->orWhere('pid_sap', $headerValue)
                        ->first();

                    $lop = $project
                        ? Lop::where('project_id', $project->id_project)->first()
                        : null;

                    break;

                case 'id_ihld':

                    $lop = Lop::where('id_ihld', $headerValue)->first();

                    break;

                case 'lop_name':

                    $lop = Lop::whereRaw(
                        'LOWER(TRIM(lop_name)) = ?',
                        [strtolower(trim($headerValue))]
                    )->first();

                    break;

                default:

                    $lop = null;

            }

            if (!$lop) {
                $unmappedLop++;
                $unmatchedHeaders[] = $headerValue;

                $invalidRows[] = [
                    'type' => 'PID / ID IHLD / LOP tidak match',
                    'header' => $headerValue,
                    'row' => '-',
                    'designator' => '-',
                    'qty' => '-',
                    'reason' => 'Header kolom tidak ditemukan di data PID/ID IHLD/LOP',
                ];

                continue;
            }

            $matchedLop++;
            $matchedHeaders[] = $headerValue;

            /*
            |--------------------------------------------------------------------------
            | Hitung header yang sudah punya BOQ
            |--------------------------------------------------------------------------
            | Ini menghitung PID SAP / Nama LOP yang sudah pernah memiliki BOQ.
            | Bukan menghitung jumlah item designator.
            |--------------------------------------------------------------------------
            */
            $hasExistingBoq = BoqItem::where('lop_id', $lop->id_lop)->exists();

            if ($hasExistingBoq) {
                $existingBoqHeaders++;
                $existingHeaders[] = $headerValue;
            }

            if (!$lop->package_id) {
                $lop->update([
                    'package_id' => $package->id_package,
                ]);
            }

            $projectCustomerId = Project::where('id_project', $lop->project_id)
                ->value('customer_id');

            for ($row = 2; $row <= $highestRow; $row++) {

                $baseDesignator = strtoupper(
                    trim((string) $sheet->getCell('A' . $row)->getValue())
                );

                $qty = $sheet->getCell($columnLetter . $row)->getCalculatedValue();
                $qty = is_numeric($qty) ? (float) $qty : 0;

                if ($baseDesignator === '' || $qty <= 0) {
                    $skipped++;
                    continue;
                }

                $volumeItems++;

                $designators = Designator::forCustomer($projectCustomerId)
                    ->where(function ($query) use ($baseDesignator) {
                        $query->where('pair_code', $baseDesignator)
                            ->orWhere('designator', $baseDesignator);
                    })
                    ->get();

                if ($designators->count() == 0) {
                    $unmappedDesignator++;

                    $invalidRows[] = [
                        'type' => 'Designator tidak ditemukan',
                        'header' => $headerValue,
                        'row' => $row,
                        'designator' => $baseDesignator,
                        'qty' => $qty,
                        'reason' => 'Designator tidak ada di master designator / pair_code',
                    ];

                    continue;
                }

                foreach ($designators as $designator) {

                    $price = DesignatorPackagePrice::where('designator_id', $designator->id_designator)
                        ->where('package_id', $package->id_package)
                        ->first();

                    $unitPrice = $price?->price ?? 0;

                    if (!$price) {
                        $priceMissing++;

                        $invalidRows[] = [
                            'type' => 'Harga package kosong',
                            'header' => $headerValue,
                            'row' => $row,
                            'designator' => $designator->designator,
                            'qty' => $qty,
                            'reason' => "Harga {$designator->designator} untuk {$package->package_name} belum ada",
                        ];
                    }

                    $totalPrice = $qty * $unitPrice;

                    $existing = BoqItem::where('lop_id', $lop->id_lop)
                        ->where(function ($q) use ($designator) {
                            $q->where('designator_id', $designator->id_designator)
                                ->orWhere('designator', $designator->designator);
                        })
                        ->first();

                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    BoqItem::create([
                        'project_id' => $lop->project_id,
                        'lop_id' => $lop->id_lop,
                        'designator_id' => $designator->id_designator,
                        'designator' => $designator->designator,
                        'item_name' => $designator->item_name,
                        'unit' => $designator->unit,
                        'quantity_plan' => $qty,
                        'quantity_actual' => 0,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);

                    $imported++;
                }
            }
        }

        DB::commit();

        ImportLog::create([
            'type' => 'boq',
            'file_name' => $fileName,
            'uploaded_by' => auth()->user()->id_user ?? auth()->id(),
            'total_rows' => max($highestRow - 1, 0),
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'status' => 'success',
        ]);

        return back()
            ->with('import_result', [
                'file_name' => $fileName,
                'sheet_name' => $sheetName,
                'package_name' => $package->package_name ?? $sheetName,
                'mapping_by' => $request->mapping_by,

                'total_rows' => max($highestRow - 1, 0),
                'total_headers' => count($matchedHeaders) + count($unmatchedHeaders),
                'matched_lop' => $matchedLop,
                'unmapped_lop' => $unmappedLop,

                // Ini untuk card Data Sudah Ada
                'existing_boq_headers' => $existingBoqHeaders,

                'volume_items' => $volumeItems,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,

                'unmapped_designator' => $unmappedDesignator,
                'price_missing' => $priceMissing,

                'matched_headers' => array_slice($matchedHeaders, 0, 10),
                'unmatched_headers' => array_slice($unmatchedHeaders, 0, 10),
                'existing_headers' => array_slice($existingHeaders, 0, 10),
                'invalid_rows' => array_slice($invalidRows, 0, 10),
            ])
            ->with(
                'success',
                "Import BOQ selesai. Match LOP {$matchedLop}, Tidak Match {$unmappedLop}, Data Sudah Ada {$existingBoqHeaders}, Data Baru {$imported}, Designator Tidak Ketemu {$unmappedDesignator}."
            );

        }
            catch (\Throwable $e){

                DB::rollBack();

                \Log::error($e);

                return back()->with(
                    'error',
                    'Import BOQ gagal : '.$e->getMessage()
                );

            }
    }

    public function dataBoq(Request $request)
    {
        $search = $request->search;
        $package = $request->package;

        $lops = Lop::query()
            ->with([
                'project',
                'package',
                'boqItems.designatorData',
            ])
            ->whereHas('boqItems')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('lop_name', 'like', "%{$search}%")
                        ->orWhere('id_ihld', 'like', "%{$search}%")
                        ->orWhere('sto', 'like', "%{$search}%")
                        ->orWhere('branch', 'like', "%{$search}%")
                        ->orWhere('mitra_name', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($p) use ($search) {
                            $p->where('pid', 'like', "%{$search}%")
                                ->orWhere('pid_sap', 'like', "%{$search}%")
                                ->orWhere('project_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($package, function ($query) use ($package) {
                $query->where('package_id', $package);
            })
            ->latest('id_lop')
            ->paginate(10)
            ->withQueryString();

        $packages = PackageModel::orderBy('package_name')->get();

        $allBoq = BoqItem::with('designatorData')->get();

        $totalLopBoq = Lop::whereHas('boqItems')->count();
        $totalItemBoq = $allBoq->count();

        $totalMaterial = $allBoq->filter(function ($item) {
            return str_starts_with(strtoupper($item->designator ?? ''), 'M-');
        })->count();

        $totalJasa = $allBoq->filter(function ($item) {
            return str_starts_with(strtoupper($item->designator ?? ''), 'J-');
        })->count();

        $totalPlanValue = $allBoq->sum('total_price');

        $totalLopBoq = Lop::whereHas('boqItems')->count();

        $totalBoqValue = BoqItem::sum('total_price');

        $sudahAssign = Lop::whereHas('boqItems')
            ->whereHas('project.assignments')
            ->count();

        $belumAssign = Lop::whereHas('boqItems')
            ->whereDoesntHave('project.assignments')
            ->count();

        return view('admin.import.data-boq', compact(
            'lops',
            'packages',
            'search',
            'package',

            'totalLopBoq',
            'totalBoqValue',
            'sudahAssign',
            'belumAssign'
        ));
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

    public function dataPid(Request $request)
    {
        $search = $request->search;

        $projects = Project::with('lop')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('pid', 'like', "%{$search}%")
                        ->orWhere('pid_sap', 'like', "%{$search}%")
                        ->orWhere('project_name', 'like', "%{$search}%")
                        ->orWhere('program', 'like', "%{$search}%")
                        ->orWhere('execution_type', 'like', "%{$search}%")
                        ->orWhere('status_project', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

            $totalPid = Project::count();

            $totalLop = Lop::count();

            $sudahAdaBoq = Lop::whereHas('boqItems')->count();

            $belumAdaBoq = Lop::whereDoesntHave('boqItems')->count();

        return view('admin.import.data-pid', compact(
            'projects',
            'search',
            'totalPid',
            'totalLop',
            'sudahAdaBoq',
            'belumAdaBoq'
        ));
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
