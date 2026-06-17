<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Lop;
use App\Models\BoqItem;
use App\Models\Designator;
use App\Models\Evidence;
use App\Models\ProjectAssignment;
use App\Models\Package as PackageModel;
use App\Models\DesignatorPackagePrice;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ImportController extends Controller
{
    public function pidIndex(Request $request)
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

        return view('admin.import.pid', compact('projects', 'search'));
    }

    public function importPid(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
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

        $projectImported = 0;
        $projectUpdated = 0;
        $lopImported = 0;
        $lopUpdated = 0;
        $skipped = 0;

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

            if (!$pid || !$namaLop) {
                $skipped++;
                continue;
            }

            $executionType = $this->cleanValue($data['execution_type'] ?? 'kemitraan') ?: 'kemitraan';
            $statusProject = $this->cleanValue($data['status_project'] ?? 'active') ?: 'active';

            if (!in_array($executionType, ['kemitraan', 'swakelola', 'turnkey'])) {
                $executionType = 'kemitraan';
            }

            if (!in_array($statusProject, ['init', 'active', 'close', 'bast'])) {
                $statusProject = 'active';
            }

            $projectPayload = [
                'pid' => $pid,
                'pid_sap' => $pidSap,
                'project_name' => $namaLop,
                'program' => $this->cleanValue($data['program'] ?? null),
                'execution_type' => $executionType,
                'status_project' => $statusProject,
            ];

            $project = Project::where('pid', $pid)->first();

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

        return back()->with(
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
        return view('admin.import.boq');
    }

    public function importBoq(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'mapping_by' => 'required|in:pid,lop_name',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());

        // Ambil sheet aktif / sheet pertama dari file upload
        $sheet = $spreadsheet->getActiveSheet();

        // Nama sheet contoh: PAKET 5
        $sheetName = strtoupper(trim($sheet->getTitle()));

        $package = PackageModel::whereRaw('UPPER(package_name) = ?', [$sheetName])
            ->orWhereRaw('UPPER(package_code) = ?', [$sheetName])
            ->first();

        if (!$package) {
            return back()->with('error', "Package {$sheetName} belum ada di master package.");
        }

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $unmappedLop = 0;
        $unmappedDesignator = 0;
        $priceMissing = 0;
        $matchedLop = 0;
        $matchedHeaders = [];
        $unmatchedHeaders = [];

        // Loop kolom mulai B, karena A adalah Designator
        for ($col = 2; $col <= $highestColumnIndex; $col++) {

            $columnLetter = Coordinate::stringFromColumnIndex($col);

            $headerValue = trim(
                (string) $sheet->getCell($columnLetter . '1')->getValue()
            );

            if ($headerValue === '') {
                continue;
            }

            if ($request->mapping_by === 'pid') {

                $project = Project::where('pid', $headerValue)
                    ->orWhere('pid_sap', $headerValue)
                    ->first();

                $lop = $project
                    ? Lop::where('project_id', $project->id_project)->first()
                    : null;

            } else {

                $lop = Lop::whereRaw('LOWER(TRIM(lop_name)) = ?', [
                    strtolower(trim($headerValue))
                ])->first();

            }

            if (!$lop) {
                $unmappedLop++;
                $unmatchedHeaders[] = $headerValue;
                continue;
            }

            $matchedLop++;
            $matchedHeaders[] = $headerValue;

            // Set package ke LOP jika belum ada
            if (!$lop->package_id) {
                $lop->update([
                    'package_id' => $package->id_package,
                ]);
            }

            // Loop row mulai 2, karena row 1 header
            for ($row = 2; $row <= $highestRow; $row++) {

                $baseDesignator = strtoupper(
                    trim(
                        (string) $sheet->getCell('A' . $row)->getValue()
                    )
                );
                $columnLetter = Coordinate::stringFromColumnIndex($col);

                $qty = $sheet
                    ->getCell($columnLetter . $row)
                    ->getCalculatedValue();

                $qty = is_numeric($qty) ? (float) $qty : 0;

                if ($baseDesignator === '' || $qty <= 0) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Cari designator
                |--------------------------------------------------------------------------
                | File BOQ berisi base code:
                | AB-OF-SM-12D
                |
                | Master designator bisa:
                | M-AB-OF-SM-12D
                | J-AB-OF-SM-12D
                |
                | Maka kita cari berdasarkan pair_code.
                |--------------------------------------------------------------------------
                */

                $designators = Designator::where('pair_code', $baseDesignator)
                    ->orWhere('designator', $baseDesignator)
                    ->get();

                if ($designators->count() == 0) {
                    $unmappedDesignator++;
                    continue;
                }

                foreach ($designators as $designator) {

                    $price = DesignatorPackagePrice::where('designator_id', $designator->id_designator)
                        ->where('package_id', $package->id_package)
                        ->first();

                    $unitPrice = $price?->price ?? 0;

                    if (!$price) {
                        $priceMissing++;
                    }

                    $totalPrice = $qty * $unitPrice;

                    $existing = BoqItem::where('lop_id', $lop->id_lop)
                        ->where('designator_id', $designator->id_designator)
                        ->first();

                    BoqItem::updateOrCreate(
                        [
                            'lop_id' => $lop->id_lop,
                            'designator_id' => $designator->id_designator,
                        ],
                        [
                            'project_id' => $lop->project_id,
                            'designator' => $designator->designator,
                            'item_name' => $designator->item_name,
                            'unit' => $designator->unit,
                            'quantity_plan' => $qty,
                            'quantity_actual' => 0,
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                        ]
                    );

                    if ($existing) {
                        $updated++;
                    } else {
                        $imported++;
                    }
                }
            }
        }

        return back()->with(
            'success',
            "Import BOQ selesai. 
            | Match PID/Nama LOP: {$matchedLop} 
            | Tidak Match: {$unmappedLop} 
            | Data baru: {$imported} 
            | Update: {$updated}
            | Designator tidak ketemu: {$unmappedDesignator}"
        );
        
    }

    //HELPER PID
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