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
            'file' => 'required|file|extensions:xlsx,xls,csv',
            'customer_id' => 'nullable|exists:customers,id_customer',
            'project_type' => 'required|in:internal,external,pt2' 
        ]);

        $projectType = $request->project_type;
        $customerId = ($projectType === 'pt2') ? 1 : $request->customer_id; 

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->getRealPath();
        
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membaca file spreadsheet. Pastikan file tidak corrupt. Detail: ' . $e->getMessage());
        }
        
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $header = strtolower(trim((string) $sheet->getCell($columnLetter . '1')->getValue()));
            $headers[$col] = $header;
        }

        $requiredHeaders = ['pid_sap', 'id_ihld', 'nama_lop'];
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
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $headerName = $headers[$col] ?? null;

                if (!$headerName) continue;
                $data[$headerName] = $sheet->getCell($columnLetter . $row)->getValue();
            }

            $pid = $this->cleanValue($data['pid'] ?? null);
            $pidSap = $this->cleanValue($data['pid_sap'] ?? null);
            $namaLop = $this->cleanValue($data['nama_lop'] ?? null);
            $idIhld = $this->cleanValue($data['id_ihld'] ?? null);
            
            $rawProgram = $this->cleanValue($data['program'] ?? null);
            $programFormatted = $rawProgram;
            if ($rawProgram) {
                $progUpper = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $rawProgram)); 
                if (in_array($progUpper, ['PT2', 'PT02'])) {
                    $programFormatted = 'PT 2';
                } elseif (in_array($progUpper, ['NODEB', 'NODE0B'])) {
                    $programFormatted = 'NODE B';
                }
            }

            if ($projectType === 'pt2') {
                $programFormatted = 'PT 2';
            }

            $rowErrors = [];
            if (!$pidSap) $rowErrors[] = 'PID SAP wajib diisi';
            if (!$namaLop) $rowErrors[] = 'Nama LOP wajib diisi';

            if ($pidSap) {
                $pidSapKey = strtolower(trim($pidSap));
                $ihldKey = strtolower(trim($idIhld));
                $trackerKey = ($projectType === 'pt2' || $programFormatted === 'PT 2') ? $pidSapKey . '_' . $ihldKey : $pidSapKey;

                if (isset($pidSapTracker[$trackerKey])) {
                    $rowErrors[] = 'Duplikat di file pada row ' . $pidSapTracker[$trackerKey];
                } else {
                    $pidSapTracker[$trackerKey] = $row;
                }
            }

            if (!empty($rowErrors)) {
                $skipped++;
                $invalidRows[] = [
                    'row' => $row,
                    'pid_sap' => $pidSap,
                    'nama_lop' => $namaLop,
                    'reason' => implode(', ', $rowErrors),
                ];
                continue;
            }

            $validRows++;

            $executionType = $this->cleanValue($data['execution_type'] ?? 'kemitraan') ?: 'kemitraan';
            $statusProject = $this->cleanValue($data['status_project'] ?? 'active') ?: 'active';
            
            if (!in_array($executionType, ['kemitraan', 'swakelola', 'turnkey'])) $executionType = 'kemitraan';
            if (!in_array($statusProject, ['init', 'active', 'close', 'bast', 'drop'])) $statusProject = 'active';

            $pidForProject = $pid ?: $pidSap;

            $projectPayload = [
                'customer_id'     => $customerId,
                'pid'             => $pidForProject,
                'pid_sap'         => $pidSap,
                'project_name'    => $namaLop,
                'program'         => $programFormatted,
                'branch'          => $this->cleanValue($data['branch'] ?? null),
                'sto'             => $this->cleanValue($data['sto'] ?? null),
                'mitra_name'      => $this->cleanValue($data['mitra_name'] ?? null),
                'execution_type'  => $executionType,
                'status_project'  => $statusProject,
            ];

            if ($projectType === 'pt2') {
                // ============================================
                // IMPORT KHUSUS PT 2 DENGAN ID BARU
                // ============================================
                $project = Pt2Project::where('pid_sap', $pidSap)->first();
                if (!$project && $pid) {
                    $project = Pt2Project::where('pid', $pid)->first();
                }

                if ($project) {
                    $project->update($projectPayload);
                    $projectUpdated++;
                } else {
                    $project = Pt2Project::create($projectPayload);
                    $projectImported++;
                }

                $lopPayload = [
                    'pt2_project_id' => $project->id_pt2_project, // <-- UPDATE PK
                    'id_ihld' => $idIhld,
                    'lop_name' => $namaLop,
                    'pid_sap' => $pidSap,
                    'tematik' => $this->cleanValue($data['tematik'] ?? null),
                    'sto' => $this->cleanValue($data['sto'] ?? null),
                    'branch' => $this->cleanValue($data['branch'] ?? null),
                    'batch' => $this->cleanValue($data['batch'] ?? null),
                    'no_sp' => $this->cleanValue($data['no_sp'] ?? null),
                    'tgl_sp' => $this->cleanDate($data['tgl_sp'] ?? null),
                    'tgl_toc' => $this->cleanDate($data['tgl_toc'] ?? null),
                    'mitra_name' => $this->cleanValue($data['mitra_name'] ?? null),
                    'status_progress' => 'preparation',
                ];

                $lop = null;
                if ($idIhld) {
                    $lop = Pt2Lop::where('pt2_project_id', $project->id_pt2_project)->where('id_ihld', $idIhld)->first();
                }
                if (!$lop) {
                    $lop = Pt2Lop::where('pt2_project_id', $project->id_pt2_project)->whereRaw('LOWER(TRIM(lop_name)) = ?', [strtolower(trim($namaLop))])->first();
                }

                if ($lop) {
                    $lop->update($lopPayload);
                    $lopUpdated++;
                } else {
                    Pt2Lop::create($lopPayload);
                    $lopImported++;
                }

            } else {
                // ============================================
                // IMPORT REGULER OSP
                // ============================================
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
                    'program_sap' => $programFormatted, 
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
                    $lop = Lop::where('project_id', $project->id_project)->where('id_ihld', $idIhld)->first();
                }
                if (!$lop) {
                    $lop = Lop::where('project_id', $project->id_project)->whereRaw('LOWER(TRIM(lop_name)) = ?', [strtolower(trim($namaLop))])->first();
                }

                if ($lop) {
                    $lop->update($lopPayload);
                    $lopUpdated++;
                } else {
                    Lop::create($lopPayload);
                    $lopImported++;
                }
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
            ->with('success', "Import PID selesai. Project Baru {$projectImported}, Update Project {$projectUpdated}, LOP Baru {$lopImported}, Update LOP {$lopUpdated}, Skip {$skipped}.");
    }

    public function updatePid(Request $request, Project $project)
    {
        $request->validate([
            'pid' => 'required|string|max:100',
            'pid_sap' => 'nullable|string|max:100',
            'nama_lop' => 'required|string|max:255',
            'program' => 'nullable|string|max:150',
            'execution_type' => 'required|in:kemitraan,swakelola,turnkey',
            'status_project' => 'required|in:init,active,close,bast,drop',

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

        BoqItem::where('project_id', $project->id_project)->delete();
        ProjectAssignment::where('project_id', $project->id_project)->delete();
        Lop::where('project_id', $project->id_project)->delete();
        $project->delete();

        return back()->with('success', 'Project berhasil dihapus.');
    }

    public function boqIndex()
    {
        $customers = Customer::active()->get();
        $packages = PackageModel::all();

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
            'customers', 
            'packages', 
            'importLogs', 
            'lastImport'
        ));
    }

    public function importBoq(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'mapping_by' => 'required|in:pid,id_ihld,lop_name',
            'customer_id' => 'required|exists:customers,id_customer',
            'package_id' => 'required|exists:packages,id_package',
            'project_type' => 'required|in:internal,external,pt2' 
        ]);

        $projectType = $request->project_type;

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $sheetName = strtoupper(trim($sheet->getTitle()));

        $package = PackageModel::where('id_package', $request->package_id)
            ->where('customer_id', $request->customer_id)
            ->first();

        if (!$package) {
            return back()->with('error', "Package tidak valid atau tidak sesuai dengan Customer yang dipilih.");
        }

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        DB::beginTransaction();

        try {
            $imported = 0; $updated = 0; $skipped = 0;
            $unmappedLop = 0; $unmappedDesignator = 0; $priceMissing = 0;
            $matchedLop = 0; $volumeItems = 0; $existingBoqHeaders = 0;

            $matchedHeaders = []; $unmatchedHeaders = []; $existingHeaders = []; $invalidRows = [];

            for ($col = 2; $col <= $highestColumnIndex; $col++) {

                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $headerValue = trim((string) $sheet->getCell($columnLetter . '1')->getValue());

                if ($headerValue === '') continue;

                if ($projectType === 'pt2') {
                    switch ($request->mapping_by) {
                        case 'pid':
                            $project = Pt2Project::where('pid', $headerValue)->orWhere('pid_sap', $headerValue)->first();
                            $lop = $project ? Pt2Lop::where('pt2_project_id', $project->id_pt2_project)->first() : null;
                            break;
                        case 'id_ihld':
                            $lop = Pt2Lop::where('id_ihld', $headerValue)->first();
                            break;
                        case 'lop_name':
                            $lop = Pt2Lop::whereRaw('LOWER(TRIM(lop_name)) = ?', [strtolower(trim($headerValue))])->first();
                            break;
                        default:
                            $lop = null;
                    }
                } else {
                    switch ($request->mapping_by) {
                        case 'pid':
                            $project = Project::where('pid', $headerValue)->orWhere('pid_sap', $headerValue)->first();
                            $lop = $project ? Lop::where('project_id', $project->id_project)->first() : null;
                            break;
                        case 'id_ihld':
                            $lop = Lop::where('id_ihld', $headerValue)->first();
                            break;
                        case 'lop_name':
                            $lop = Lop::whereRaw('LOWER(TRIM(lop_name)) = ?', [strtolower(trim($headerValue))])->first();
                            break;
                        default:
                            $lop = null;
                    }
                }

                if (!$lop) {
                    $unmappedLop++;
                    $unmatchedHeaders[] = $headerValue;
                    continue;
                }

                if ($projectType !== 'pt2') {
                    $projectCustomerId = Project::where('id_project', $lop->project_id)->value('customer_id');
                    if ($projectCustomerId != $request->customer_id) {
                        $unmappedLop++;
                        $unmatchedHeaders[] = $headerValue;
                        continue; 
                    }
                }

                $matchedLop++;
                $matchedHeaders[] = $headerValue;

                if ($projectType === 'pt2') {
                    $hasExistingBoq = \App\Models\Pt2BoqItem::where('pt2_lop_id', $lop->id_pt2_lop)->exists();
                } else {
                    $hasExistingBoq = BoqItem::where('lop_id', $lop->id_lop)->exists();
                }

                if ($hasExistingBoq) {
                    $existingBoqHeaders++;
                    $existingHeaders[] = $headerValue;
                }

                if (!$lop->package_id) {
                    $lop->update(['package_id' => $package->id_package]);
                }

                for ($row = 2; $row <= $highestRow; $row++) {

                    $baseDesignator = strtoupper(trim((string) $sheet->getCell('A' . $row)->getValue()));
                    $qty = $sheet->getCell($columnLetter . $row)->getCalculatedValue();
                    $qty = is_numeric($qty) ? (float) $qty : 0;

                    if ($baseDesignator === '' || $qty <= 0) {
                        $skipped++;
                        continue;
                    }

                    $volumeItems++;
                    $projectCustomerId = ($projectType === 'pt2') ? 1 : Project::where('id_project', $lop->project_id)->value('customer_id');

                    $designators = Designator::forCustomer($projectCustomerId)
                        ->where(function ($query) use ($baseDesignator) {
                            $query->where('pair_code', $baseDesignator)
                                ->orWhere('designator', $baseDesignator);
                        })->get();

                    if ($designators->count() == 0) {
                        $unmappedDesignator++;
                        continue;
                    }

                    foreach ($designators as $designator) {
                        $price = DesignatorPackagePrice::where('designator_id', $designator->id_designator)->where('package_id', $package->id_package)->first();
                        $unitPrice = $price?->price ?? 0;
                        if (!$price) $priceMissing++;
                        $totalPrice = $qty * $unitPrice;

                        if ($projectType === 'pt2') {
                            $existing = \App\Models\Pt2BoqItem::where('pt2_lop_id', $lop->id_pt2_lop)
                                ->where(function ($q) use ($designator) {
                                    $q->where('designator_id', $designator->id_designator)->orWhere('designator', $designator->designator);
                                })->first();

                            if ($existing) {
                                $skipped++;
                                continue;
                            }

                            \App\Models\Pt2BoqItem::create([
                                'pt2_project_id' => $lop->pt2_project_id,
                                'pt2_lop_id' => $lop->id_pt2_lop, // <-- UPDATE PK
                                'designator_id' => $designator->id_designator,
                                'designator' => $designator->designator,
                                'item_name' => $designator->item_name,
                                'unit' => $designator->unit,
                                'quantity_plan' => $qty,
                                'quantity_actual' => 0,
                                'unit_price' => $unitPrice,
                                'total_price' => $totalPrice,
                            ]);
                        } else {
                            $existing = BoqItem::where('lop_id', $lop->id_lop)
                                ->where(function ($q) use ($designator) {
                                    $q->where('designator_id', $designator->id_designator)->orWhere('designator', $designator->designator);
                                })->first();

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
                        }
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

            return back()->with('success', "Import BOQ selesai. Baru: {$imported}, Skip: {$skipped}, Unmapped LOP: {$unmappedLop}.");

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error($e);
            return back()->with('error', 'Import BOQ gagal : ' . $e->getMessage());
        }
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

    public function dataPid(Request $request)
    {
        $search = $request->search;

        // 1. Setup Mapping Region (Untuk Filter dan Tabel Rekap)
        $regions = [
            'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
            'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
            'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
        ];

        // 2. Ambil list Program untuk Filter
        $programs = \App\Models\Project::whereNotNull('program')->where('program', '!=', '')
            ->distinct()->orderBy('program', 'asc')->pluck('program');

        // ====================================================================
        // KODE BARU: TABEL MATRIX UNFILTERED (Berdasarkan STATUS PROJECT)
        // ====================================================================
        // Diambil dari tabel Project langsung, karena status_project ada di sini
        $allProjectsUnfiltered = \App\Models\Project::all();

        $matrixData = [];
        foreach ($regions as $regionName => $regionBranches) {
            
            $regionProjects = $allProjectsUnfiltered->filter(function ($p) use ($regionBranches) {
                return in_array(strtoupper($p->branch ?? ''), $regionBranches);
            });

            $regionProgs = [];
            foreach ($programs as $prog) {
                $pProgs = $regionProjects->filter(function($p) use ($prog) { return ($p->program ?? '') === $prog; });
                $regionProgs[$prog] = [
                    'init'   => $pProgs->where('status_project', 'init')->count(),
                    'active' => $pProgs->where('status_project', 'active')->count(),
                    'close'  => $pProgs->where('status_project', 'close')->count(),
                    'bast'   => $pProgs->where('status_project', 'bast')->count(),
                    'drop'   => $pProgs->where('status_project', 'drop')->count(),
                ];
            }

            $branchesData = [];
            foreach ($regionBranches as $bName) {
                $bProjects = $regionProjects->filter(function($p) use ($bName) { return strtoupper($p->branch ?? '') === $bName; });
                if ($bProjects->isEmpty()) continue;

                $bProgs = [];
                foreach ($programs as $prog) {
                    $bpProgs = $bProjects->filter(function($p) use ($prog) { return ($p->program ?? '') === $prog; });
                    $bProgs[$prog] = [
                        'init'   => $bpProgs->where('status_project', 'init')->count(),
                        'active' => $bpProgs->where('status_project', 'active')->count(),
                        'close'  => $bpProgs->where('status_project', 'close')->count(),
                        'bast'   => $bpProgs->where('status_project', 'bast')->count(),
                        'drop'   => $bpProgs->where('status_project', 'drop')->count(),
                    ];
                }
                $branchesData[] = [
                    'name' => $bName,
                    'programs' => $bProgs
                ];
            }

            $matrixData[] = [
                'region' => $regionName,
                'programs' => $regionProgs,
                'branches' => $branchesData
            ];
        } 
        // ====================================================================

        // 3. Query Utama dengan Filter
        $query = \App\Models\Project::with('lop');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pid', 'like', "%{$search}%")
                    ->orWhere('pid_sap', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhere('program', 'like', "%{$search}%")
                    ->orWhere('execution_type', 'like', "%{$search}%")
                    ->orWhere('status_project', 'like', "%{$search}%");
            });
        }

        if ($request->filled('region')) {
            $selectedRegion = strtoupper($request->region);
            if (isset($regions[$selectedRegion])) {
                $query->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(branch)'), $regions[$selectedRegion]);
            }
        }
        
        if ($request->filled('branch')) {
            $query->whereRaw('UPPER(branch) = ?', [strtoupper($request->branch)]);
        }
        
        if ($request->filled('program')) {
            $query->where('program', $request->program);
        }
        
        if ($request->filled('status_project')) {
            $query->where('status_project', $request->status_project);
        }

       // 4. Hitung KPI Widget Cards secara Dinamis (Berdasarkan Query Filter)
        $filteredQuery = clone $query;
        
        $totalPid = $filteredQuery->count();
        
        // NEW: Menghitung total PID (Project) yang sudah memiliki data di tabel boq_items
        $pidMatchBoq = (clone $filteredQuery)->whereExists(function ($q) {
            $q->select(\Illuminate\Support\Facades\DB::raw(1))
              ->from('boq_items')
              ->whereColumn('boq_items.project_id', 'projects.id_project');
        })->count();
        
        // NEW: Project yang berstatus 'active' saja
        $projectActive = (clone $filteredQuery)->where('status_project', 'active')->count();
        
        // Project yang berstatus 'drop'
        $projectDrop = (clone $filteredQuery)->where('status_project', 'drop')->count();

        // 5. Eksekusi Data Tabel Pagination
        $projects = $query->latest('id_project')->paginate(10)->withQueryString();

        return view('admin.import.data-pid', compact(
            'projects',
            'search',
            'programs',
            'totalPid',
            'pidMatchBoq',    // Variabel Baru
            'projectActive',  // Variabel Baru
            'projectDrop',
            'matrixData'
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
