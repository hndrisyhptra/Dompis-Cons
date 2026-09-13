<?php

namespace App\Http\Controllers;

use App\Models\BoqItem;
use App\Models\Customer;
use App\Models\Designator;
use App\Models\Evidence;
use App\Models\EvidenceRevisionHistory;
use App\Models\Lop;
use App\Models\LopMeasurementCheck;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectActivityLog;
use App\Models\ProjectAssignment;
use App\Models\User;
use App\Services\ProjectActivityService;
use App\Services\TelegramWebhookEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        // Role super_tif tidak boleh melihat project program Konstruksi
        // Eksternal sama sekali (listing, filter dropdown, maupun stat card).
        $isSuperTif = auth()->user()?->role === 'super_tif';

        $query = Project::with([
            'boqItems',
            'assignments.waspang',
            'assignment.waspang',
            'evidences',
            'lop',
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
        ]);

        if ($isSuperTif) {
            $query->whereRaw('UPPER(TRIM(program)) != ?', ['KONSTRUKSI EKSTERNAL']);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('pid', 'like', "%{$search}%")
                    ->orWhere('pid_sap', 'like', "%{$search}%")
                    ->orWhereHas('lop', function ($lop) use ($search) {
                        $lop->where('sto', 'like', "%{$search}%")
                            ->orWhere('branch', 'like', "%{$search}%")
                            ->orWhere('mitra_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('program')) {
            $query->where('program', $request->program);
        }

        if ($request->filled('branch')) {
            $query->whereHas('lop', function ($lop) use ($request) {
                $lop->where('branch', $request->branch);
            });
        }

        $perPage = $request->input('per_page', 10);
        $projects = $query
            ->latest('updated_at')
            ->paginate($perPage) // <-- Gunakan variabel $perPage
            ->onEachSide(1)      // <-- membatasi angka pagination
            ->withQueryString();

        $programsQuery = Project::whereNotNull('program')
            ->where('program', '!=', '');
        if ($isSuperTif) {
            $programsQuery->whereRaw('UPPER(TRIM(program)) != ?', ['KONSTRUKSI EKSTERNAL']);
        }
        $programs = $programsQuery
            ->distinct()
            ->orderBy('program')
            ->pluck('program');

        $branches = Lop::whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');

        $assignableUsers = User::roleCode(['waspang', 'teknisi'])->get();

        $designators = Designator::forCustomer(Customer::defaultId())
            ->orderBy('designator')
            ->get();

        $statBaseQuery = Project::query();
        if ($isSuperTif) {
            $statBaseQuery->whereRaw('UPPER(TRIM(program)) != ?', ['KONSTRUKSI EKSTERNAL']);
        }
        $totalProject = (clone $statBaseQuery)->count();
        $activeProject = (clone $statBaseQuery)->whereHas('lops', function ($lopQuery) {
            $lopQuery->whereNotIn('status_progress', ['drop', 'golive']);
        })->count();
        $completedProject = (clone $statBaseQuery)->whereHas('lops', function ($lopQuery) {
            $lopQuery->where('status_progress', 'golive');
        })->count();
        $waitingUt = (clone $statBaseQuery)->whereHas('lops', function ($lopQuery) {
            $lopQuery->where('status_progress', 'fi_ogp_golive');
        })->count();

        return view('admin.projects.index', compact(
            'projects',
            'programs',
            'branches',
            'assignableUsers',
            'designators',
            'totalProject',
            'activeProject',
            'waitingUt',
            'completedProject'
        ));
    }

    public function assignWaspang(Request $request)
    {
        $request->validate([
            'project_id' => 'required',
            'assigned_user_id' => 'required|exists:users,id_user', // Menggunakan nama universal
        ]);

        $oldAssignment = ProjectAssignment::where('project_id', $request->project_id)->first();
        $targetUser = User::where('id_user', $request->assigned_user_id)->first();

        // Siapkan data yang akan di-update
        $dataToUpdate = [
            'assigned_by' => auth()->user()->id_user,
        ];

        // Pengecekan otomatis!
        // FIX (2026-09-08): sebelumnya assign salah satu role (waspang/teknisi)
        // otomatis meng-null-kan role yang satunya (assign waspang menghapus
        // teknisi yang sudah ada, dan sebaliknya) -- padahal keduanya bisa
        // ter-assign bersamaan di satu project. Sekarang hanya kolom milik
        // role yang sedang di-assign yang diisi; role yang lain (kalau sudah
        // ada) TIDAK disentuh sama sekali, karena updateOrCreate cuma
        // meng-update kolom yang ada di $dataToUpdate.
        if ($targetUser->role === 'teknisi') {
            $dataToUpdate['teknisi_id'] = $targetUser->id_user;
        } else {
            $dataToUpdate['waspang_id'] = $targetUser->id_user;
        }

        ProjectAssignment::updateOrCreate(
            ['project_id' => $request->project_id],
            $dataToUpdate
        );

        $lop = Lop::where('project_id', $request->project_id)->first();

        /*
        |--------------------------------------------------------------------------
        | STAGE 4c: assign Waspang -> LOP pindah dari 'inisiasi' ke 'survey'.
        |--------------------------------------------------------------------------
        | Hanya untuk LOP reguler (bukan PT2) yang MASIH PERSIS di 'inisiasi'
        | (sequence 1) -- kalau sudah lebih maju (mis. reassign waspang lain
        | di tengah jalan) posisi LOP TIDAK disentuh/dimundurkan.
        */
        if ($lop && $targetUser->role === 'waspang') {
            $programSap = strtoupper($lop->program_sap ?? '');
            $isPt2 = str_contains($programSap, 'PT2') || str_contains($programSap, 'PT-2') || str_contains($programSap, 'PT 2');

            if (! $isPt2 && $lop->status_progress === 'inisiasi') {
                $lop->advanceStage('survey', auth()->id());
            }
        }

        // LOGGING DINAMIS
        $roleTitle = ucfirst($targetUser->role); // 'Teknisi' atau 'Waspang'
        $isReassign = ($oldAssignment && ($oldAssignment->waspang_id || $oldAssignment->teknisi_id));

        ProjectActivityService::log([
            'project_id' => $request->project_id,
            'lop_id' => $lop?->id_lop,
            'target_user_id' => $targetUser->id_user,
            'activity_type' => $isReassign ? 'reassign_'.$targetUser->role : 'assign_'.$targetUser->role,
            'title' => $isReassign ? "Reassign {$roleTitle}" : "Assign {$roleTitle}",
            'description' => "Project di-assign ke {$roleTitle} ".$targetUser->name,
            'status_before' => $isReassign ? 'assigned' : 'unassigned',
            'status_after' => 'assigned',
            'meta' => [
                "old_{$targetUser->role}_id" => $targetUser->role === 'teknisi' ? $oldAssignment?->teknisi_id : $oldAssignment?->waspang_id,
                "new_{$targetUser->role}_id" => $targetUser->id_user,
                "new_{$targetUser->role}_name" => $targetUser->name,
            ],
        ]);

        // WEBHOOK EVENT: project baru di-assign -- event pribadi utk waspang/teknisi.
        $projectForNotif = Project::find($request->project_id);
        TelegramWebhookEventService::publishToUser(
            $targetUser,
            'project_assigned',
            $isReassign ? 'Project Di-assign Ulang' : 'Project Baru Ditugaskan',
            "Anda ditugaskan sebagai {$roleTitle} untuk project ".($projectForNotif->project_name ?? '-').' ('.($projectForNotif->pid ?? '-').').',
            [
                'project_name' => $projectForNotif->project_name ?? null,
                'pid' => $projectForNotif->pid ?? null,
                'role_assigned_as' => $targetUser->role,
                'is_reassign' => $isReassign,
            ],
            ['project_id' => $request->project_id]
        );

        return back()->with('success', 'Assignment berhasil disimpan');
    }

    public function removeAssign($project)
    {
        $oldAssignment = ProjectAssignment::where('project_id', $project)->first();

        // Hapus Assignment
        ProjectAssignment::where('project_id', $project)->delete();

        $lop_id = Lop::where('project_id', $project)->value('id_lop');

        // Logging Hapus Waspang
        if ($oldAssignment && $oldAssignment->waspang_id) {
            ProjectActivityService::log([
                'project_id' => $project,
                'lop_id' => $lop_id,
                'target_user_id' => $oldAssignment->waspang_id,
                'activity_type' => 'remove_assignment',
                'title' => 'Assignment Waspang Dihapus',
                'description' => 'Assignment Waspang dihapus dari project.',
                'status_before' => 'assigned',
                'status_after' => 'unassigned',
                'meta' => ['old_waspang_id' => $oldAssignment->waspang_id],
            ]);
        }

        // Logging Hapus Teknisi
        if ($oldAssignment && $oldAssignment->teknisi_id) {
            ProjectActivityService::log([
                'project_id' => $project,
                'lop_id' => $lop_id,
                'target_user_id' => $oldAssignment->teknisi_id,
                'activity_type' => 'remove_teknisi_assignment',
                'title' => 'Assignment Teknisi Dihapus',
                'description' => 'Assignment Teknisi dihapus dari project.',
                'status_before' => 'assigned',
                'status_after' => 'unassigned',
                'meta' => ['old_teknisi_id' => $oldAssignment->teknisi_id],
            ]);
        }

        return back()->with('success', 'Assignment berhasil dihapus');
    }

    public function show($id)
    {
        $project = Project::with([
            'boqItems',
            'assignments.waspang',
            'evidences',
        ])->findOrFail($id);

        return view('admin.projects.show', compact('project'));
    }

    // CRUD PROJECT
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id_customer',
            'project_name' => 'required|string|max:255',
            'branch' => 'required|string|max:255',
            'sto' => 'required|string|max:20',
            'mitra_name' => 'nullable|string|max:100',
            'jenis_eksekusi' => 'nullable|in:plan,survey,ogp,finish',
            'designator_id' => 'nullable|array',
            'designator_id.*' => 'nullable|exists:designators,id_designator',
            'boq_qty' => 'nullable|array',
            'boq_qty.*' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {

            $project = Project::create([
                'customer_id' => $request->customer_id,
                'project_name' => $request->project_name,
                'branch' => $request->branch,
                'sto' => $request->sto,
                'mitra_name' => $request->mitra_name,
                'jenis_eksekusi' => $request->jenis_eksekusi,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location_address' => $request->location_address,
            ]);

            if ($request->has('designator_id')) {

                foreach ($request->designator_id as $index => $designatorId) {

                    if (! $designatorId) {
                        continue;
                    }

                    $qty = $request->boq_qty[$index] ?? 0;

                    if ($qty === null || $qty === '') {
                        $qty = 0;
                    }

                    $designator = Designator::forCustomer($project->customer_id)
                        ->where('id_designator', $designatorId)
                        ->first();

                    if (! $designator) {
                        continue;
                    }

                    BoqItem::create([
                        'project_id' => $project->id_project,
                        'designator_id' => $designator->id_designator,
                        'designator' => $designator->designator,
                        'item_name' => $designator->item_name,
                        'unit' => $designator->unit,
                        'quantity_plan' => $qty,
                        'quantity_actual' => 0,
                    ]);
                }
            }

            DB::commit();

            return back()->with('success', 'LOP dan item BOQ berhasil dibuat');

        } catch (\Throwable $e) {

            DB::rollBack();

            return back()->with('error', 'Gagal menyimpan LOP: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id_customer',
            'project_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'sto' => 'nullable|string|max:20',
            'mitra_name' => 'nullable|string|max:100',
        ]);

        $project->update([
            'customer_id' => $request->input('customer_id', $project->customer_id),
            'project_name' => $request->project_name,
            'branch' => $request->branch,
            'sto' => $request->sto,
            'mitra_name' => $request->mitra_name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_address' => $request->location_address,
        ]);

        /*
        |--------------------------------------------------------------------------
        | SINKRONISASI & UPDATE BOQ LAMA
        |--------------------------------------------------------------------------
        */
        if ($request->existing_boq_id) {
            foreach ($request->existing_boq_id as $index => $boqId) {

                $updateData = [
                    'quantity_plan' => $request->existing_qty[$index] ?? 0,
                ];

                // Tangkap perubahan designator baru jika user mengubah dropdown
                if (isset($request->existing_designator_id[$index]) && ! empty($request->existing_designator_id[$index])) {
                    $newDesignatorId = $request->existing_designator_id[$index];

                    $designator = Designator::where('id_designator', $newDesignatorId)->first();

                    if ($designator) {
                        $updateData['designator_id'] = $designator->id_designator;
                        $updateData['designator'] = $designator->designator;
                        $updateData['item_name'] = $designator->item_name;
                        $updateData['unit'] = $designator->unit;
                    }
                }

                BoqItem::where('id_boq', $boqId)
                    ->where('project_id', $project->id_project)
                    ->update($updateData);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TAMBAH DESIGNATOR BARU DARI MODAL EDIT
        |--------------------------------------------------------------------------
        */
        if ($request->designator_id) {
            $lop = $project->lop;

            foreach ($request->designator_id as $index => $designatorId) {
                if (! $designatorId) {
                    continue;
                }

                $designator = Designator::where('id_designator', $designatorId)->first();
                if (! $designator) {
                    continue;
                }

                // Cek duplicate BOQ di dalam project ini
                $exists = BoqItem::where('project_id', $project->id_project)
                    ->where('designator_id', $designator->id_designator)
                    ->exists();

                if ($exists) {
                    continue;
                }

                BoqItem::create([
                    'project_id' => $project->id_project,
                    'lop_id' => $lop?->id_lop,
                    'designator_id' => $designator->id_designator,
                    'designator' => $designator->designator,
                    'item_name' => $designator->item_name,
                    'unit' => $designator->unit,
                    'quantity_plan' => $request->boq_qty[$index] ?? 0,
                    'quantity_actual' => 0,
                ]);
            }
        }

        return back()->with('success', 'Project dan BOQ berhasil diperbarui');
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return back()->with('success', 'Project berhasil dihapus');
    }

    // FUNGSI KHUSUS MENGHAPUS ITEM DESIGNATOR SATUAN
    public function destroyBoq(Request $request, $id)
    {
        $boqItem = BoqItem::findOrFail($id);

        // Opsional: Cek jika BOQ sudah memiliki eviden, cegah penghapusan
        if ($boqItem->quantity_actual > 0) {
            return back()
                ->with('error', 'Item ini tidak bisa dihapus karena sudah memiliki progres aktual lapangan.')
                ->with('reopen_lop', $request->input('reopen_lop'));
        }

        $boqItem->delete();

        return back()
            ->with('success', 'Item Designator berhasil dihapus dari project.')
            ->with('reopen_lop', $request->input('reopen_lop'));
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id_customer',
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = fopen($request->file('csv_file')->getRealPath(), 'r');

        $header = fgetcsv($file);

        $requiredHeader = [
            'project_name',
            'branch',
            'sto',
            'mitra_name',
            'jenis_eksekusi',
            'status_progress',
        ];

        if ($header !== $requiredHeader) {
            fclose($file);

            return back()->with(
                'error',
                'Format header CSV tidak sesuai'
            );
        }

        $allowedJenis = ['kemitraan', 'swakelola', 'turnkey'];
        $allowedStatus = ProjectStage::active()->pluck('code')->all();

        $total = 0;

        while (($row = fgetcsv($file)) !== false) {

            if (count($row) < 6) {
                continue;
            }

            $jenisEksekusi = strtolower(trim($row[4]));
            $statusProgress = strtolower(trim($row[5]));

            if (! in_array($jenisEksekusi, $allowedJenis)) {
                continue;
            }

            if (! in_array($statusProgress, $allowedStatus, true)) {
                continue;
            }

            DB::transaction(function () use ($request, $row, $jenisEksekusi, $statusProgress) {
                $project = Project::create([
                    'customer_id' => $request->customer_id,
                    'project_name' => trim($row[0]),
                    'branch' => trim($row[1]),
                    'sto' => trim($row[2]),
                    'mitra_name' => trim($row[3]),
                    'execution_type' => $jenisEksekusi,
                ]);

                Lop::create([
                    'project_id' => $project->id_project,
                    'lop_name' => trim($row[0]),
                    'branch' => trim($row[1]),
                    'sto' => trim($row[2]),
                    'mitra_name' => trim($row[3]),
                    'status_progress' => $statusProgress,
                ]);
            });

            $total++;
        }

        fclose($file);

        return back()->with(
            'success',
            $total.' project berhasil diimport'
        );
    }

    // APPROVAL FROM ADMIN
    public function approvalIndex(Request $request)
    {
        $search = $request->search;
        // Default kita arahkan ke pending agar Admin langsung fokus ke kerjaan
        $statusFilter = $request->input('status_filter', 'pending');
        $myKawal = $request->input('my_kawal', '0');
        $programFilter = $request->program;
        $branchFilter = $request->branch;

        $allowedPrograms = ['OSP', 'HEM', 'OLO', 'NODE B'];

        // BASE QUERY: Harus sudah punya minimal 1 eviden (foto)
        // NOTE: 'boqItems.designatorDataByCode' ikut di-eager-load supaya
        // progressSummary() di view tidak memicu query tambahan per project (N+1).
        $query = Project::with([
            'evidences',
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
            'assignment.waspang',
            'lop',
        ])->whereHas('evidences')
            ->where(function ($q) use ($allowedPrograms) {
                $q->whereIn('program', $allowedPrograms)
                    ->orWhereHas('lop', function ($sub) use ($allowedPrograms) {
                        $sub->whereIn('program_sap', $allowedPrograms);
                    });
            });

        // Section AI: definisi 3 tab diluruskan sesuai kondisi flow
        // terbaru (permintaan user) --
        // "Menunggu Review" = LOP yang BENAR-BENAR belum ada evidennya yang
        // di-approve SAMA SEKALI (bukan sekadar "ada yang berstatus
        // pending" seperti logika lama -- LOP yang sudah pernah di-approve
        // tapi kebetulan ada foto baru yang masih pending itu SEHARUSNYA
        // sudah dianggap "On Progress", karena admin sudah mulai
        // mengerjakannya).
        // "On Progress" = sudah ada MINIMAL 1 eviden approved, TAPI belum
        // mencapai kriteria "Selesai" di bawah.
        // "Selesai" = LOP sudah mencapai tahap FI-OGP Golive/Golive (baik
        // langsung maupun via hold/drop SETELAH sempat mencapai tahap itu --
        // hold/drop-safe, lihat Section AH) DAN tidak ada eviden yang masih
        // nyangkut (pending/rejected).
        $completedCodes = ['fi_ogp_golive', 'golive'];
        $completedOrDropCodes = ['fi_ogp_golive', 'golive', 'drop'];

        $applyPendingFilter = function ($q) {
            $q->whereDoesntHave('evidences', function ($sub) {
                $sub->where('status', 'approved');
            });
        };

        $applyActiveFilter = function ($q) use ($completedOrDropCodes) {
            $q->whereHas('evidences', function ($sub) {
                $sub->where('status', 'approved');
            })->whereHas('lops', function ($lopQuery) use ($completedOrDropCodes) {
                $lopQuery->whereNotIn('status_progress', $completedOrDropCodes)
                    ->where(function ($qq) use ($completedOrDropCodes) {
                        $qq->whereNull('status_progress_before_hold')
                            ->orWhereNotIn('status_progress_before_hold', $completedOrDropCodes);
                    });
            });
        };

        $applyCompleteFilter = function ($q) use ($completedCodes) {
            $q->whereHas('lops', function ($lopQuery) use ($completedCodes) {
                $lopQuery->where('status_progress', '!=', 'drop')
                    ->where(function ($qq) use ($completedCodes) {
                        $qq->whereIn('status_progress', $completedCodes)
                            ->orWhereIn('status_progress_before_hold', $completedCodes);
                    });
            })->whereDoesntHave('evidences', function ($ev) {
                $ev->whereIn('status', ['pending', 'rejected']);
            });
        };

        $tabCounts = null;

        if ($search) {
            $query->where(function ($q) use ($search) {
                // ... (Logika Search sama seperti sebelumnya) ...
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('execution_type', 'like', "%{$search}%")
                    ->orWhereHas('lop', function ($lopQ) use ($search) {
                        $lopQ->where('sto', 'like', "%{$search}%")
                            ->orWhere('branch', 'like', "%{$search}%")
                            ->orWhere('mitra_name', 'like', "%{$search}%")
                            ->orWhere('program_sap', 'like', "%{$search}%")
                            ->orWhere('id_ihld', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assignment.waspang', function ($waspangQ) use ($search) {
                        $waspangQ->where('name', 'like', "%{$search}%");
                    });
            });
            // Saat search aktif tab diabaikan (semua hasil ditampilkan
            // terlepas dari tab) -- jadi badge angka per-tab tidak relevan.
        } else {
            // Filter Kawalanku, Program, Branch diterapkan DULU (independen
            // dari tab) supaya angka counter di tiap tab (di bawah) ikut
            // merefleksikan filter yang sedang aktif.
            $query->when($myKawal == '1', function ($q) {
                $q->whereHas('assignment', function ($sub) {
                    $sub->where('assigned_by', auth()->user()->id_user);
                });
            });

            $query->when($programFilter, function ($q) use ($programFilter) {
                $q->where(function ($subQ) use ($programFilter) {
                    $subQ->where('program', $programFilter)
                        ->orWhereHas('lop', function ($subLop) use ($programFilter) {
                            $subLop->where('program_sap', $programFilter);
                        });
                });
            });

            $query->when($branchFilter, function ($q) use ($branchFilter) {
                $q->whereHas('lop', function ($sub) use ($branchFilter) {
                    $sub->where('branch', $branchFilter);
                });
            });

            // Hitung jumlah LOP di tiap tab (dgn filter Kawalanku/Program/
            // Branch yang sama) utk badge angka di UI tab, supaya admin
            // tahu beban kerja tiap tab tanpa harus klik satu-satu.
            $pendingCountQuery = clone $query;
            $applyPendingFilter($pendingCountQuery);

            $activeCountQuery = clone $query;
            $applyActiveFilter($activeCountQuery);

            $completeCountQuery = clone $query;
            $applyCompleteFilter($completeCountQuery);

            $tabCounts = [
                'pending' => $pendingCountQuery->count(),
                'active' => $activeCountQuery->count(),
                'complete' => $completeCountQuery->count(),
            ];

            if ($statusFilter === 'pending') {
                $applyPendingFilter($query);
            } elseif ($statusFilter === 'complete') {
                $applyCompleteFilter($query);
            } elseif ($statusFilter === 'active') {
                $applyActiveFilter($query);
            }
        }

        $projects = $query->latest('updated_at')->paginate(10)->withQueryString();

        $availableBranches = Lop::whereNotNull('branch')->distinct()->pluck('branch');
        $availablePrograms = $allowedPrograms;

        return view('admin.evidences.approval', compact(
            'projects',
            'search',
            'availableBranches',
            'availablePrograms',
            'tabCounts'
        ));
    }

    public function approveEvidence($id)
    {
        $evidence = Evidence::with(['project', 'boqItem'])->findOrFail($id);

        $oldStatus = $evidence->status;
        $evidenceLabel = $this->evidenceLabel($evidence);

        $evidence->status = 'approved';
        $evidence->review_note = null; // Kosongkan note saat di-approve
        $evidence->save();

        $lopId = Lop::where('project_id', $evidence->project_id)->value('id_lop');

        /*
        |--------------------------------------------------------------------------
        | STAGE 4: sinkronisasi lop_measurement_checks saat eviden Pengukuran
        | di-approve.
        |--------------------------------------------------------------------------
        | Project::progressSummary()'s pengukuranDone (gate nyata sejak Stage 2)
        | hanya baca dari tabel ini, TAPI tidak ada satupun kode yang menulis ke
        | sana untuk LOP yang baru berjalan (hanya backfill migration utk LOP
        | lama yg sudah lewat Pengukuran) -- tanpa hook ini, LOP baru akan macet
        | permanen di tahap Pengukuran meski semua eviden sudah di-approve.
        | Alias nama lama (otdr_sor/lainnya, sebelum Stage 4) tetap dipetakan
        | supaya eviden lama yang baru di-approve sekarang juga ikut tersinkron.
        */
        if ($lopId && $evidence->stage === 'pengukuran') {
            $itemKey = match ($evidence->evidence_type) {
                'otdr' => 'otdr',
                'file_sor', 'otdr_sor' => 'file_sor',
                'opm' => 'opm',
                'kedalaman' => 'kedalaman',
                'eviden_lainnya', 'lainnya' => 'eviden_lainnya',
                default => null,
            };

            if ($itemKey) {
                LopMeasurementCheck::updateOrCreate(
                    ['lop_id' => $lopId, 'item_key' => $itemKey],
                    ['evidence_id' => $evidence->id_evidence, 'is_not_applicable' => false]
                );
            }
        }

        ProjectActivityService::log([
            'project_id' => $evidence->project_id,
            'lop_id' => $lopId,
            'evidence_id' => $evidence->id_evidence,
            'activity_type' => 'approve_evidence',
            'title' => 'Eviden Disetujui',
            'description' => 'Admin menyetujui eviden: '.$evidenceLabel,
            'stage' => $evidence->stage,
            'status_before' => $oldStatus,
            'status_after' => 'approved',
            'meta' => [
                'evidence_type' => $evidence->evidence_type,
                'boq_item_id' => $evidence->boq_item_id,
                'boq_designator' => $evidence->boqItem?->designator,
                'boq_item_name' => $evidence->boqItem?->item_name,
            ],
        ]);

        Notification::create([
            'user_id' => $evidence->uploaded_by,
            'project_id' => $evidence->project_id,
            'type' => 'approved',
            'title' => 'Eviden disetujui',
            'message' => 'Eviden '.ucfirst($evidence->stage).' Project '.$evidence->project->project_name.' telah disetujui Admin.',
            'redirect_url' => route('waspang.projects.show', $evidence->project_id),
        ]);

        $project = Project::with([
            'evidences',
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
            'lop.stage', // Pastikan LOP + tahapnya ter-load (cek program SAP & posisi status_progress)
        ])->find($evidence->project_id);

        if ($project) {

            $summary = $project->progressSummary();

            /*
            |--------------------------------------------------------------------------
            | BARIER PELINDUNG PT2 & GOLIVE
            |--------------------------------------------------------------------------
            | Kita cegah sistem reguler mengubah status LOP jika ini adalah PT2
            | atau jika project sudah ditutup / Go-Live.
            */
            $programSap = strtoupper($project->lop->program_sap ?? '');
            $isPt2 = str_contains($programSap, 'PT2') || str_contains($programSap, 'PT-2') || str_contains($programSap, 'PT 2');

            $isAlreadyClosed = in_array($project->lop?->status_progress, ['drop', 'golive'], true)
                || (bool) $project->lop?->is_golive;

            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS PROGRESS LOP (KHUSUS REGULER)
            |--------------------------------------------------------------------------
            | FIX (2026-09-08, Stage 2 refactor "flow 11-tahap"):
            | 1. Setiap transisi sekarang di-gate pada POSISI SEKARANG
            |    (project_stages.sequence via lops.status_progress), bukan
            |    ditimpa langsung -- supaya approve eviden yang telat/re-order
            |    tidak bisa memundurkan ATAU melompati tahap LOP.
            | 2. LOP yang sedang HOLD/DROP TIDAK disentuh sama sekali -- itu
            |    jeda/batal yang sengaja; resume adalah aksi eksplisit
            |    terpisah, di luar scope approve eviden.
            | 3. Instalasi selesai -> berhenti dulu di 'pengukuran' (dulu
            |    langsung loncat ke 'finishing', itu sebabnya Pengukuran
            |    dulu cuma alias dari Instalasi -- lihat Project::progressSummary()).
            | 4. 'finishing' (seq 9) adalah tahap terakhir yang dijangkau
            |    otomatis dari approve eviden. Project TIDAK di-close lagi di
            |    sini -- 2 tahap setelahnya (FI-OGP Golive & Golive, seq
            |    10-11) adalah gate baru yang butuh aksi manual Admin/SDI
            |    (Stage 5, belum dikerjakan). Sampai Stage 5 jadi, LOP yang
            |    sudah finishingDone akan berhenti di status_progress
            |    'finishing' -- ini disengaja,
            |    BUKAN bug, dan sudah didiskusikan di ANALISA_REFACTOR_PERSIAPAN.md.
            */
            if (! $isPt2 && ! $isAlreadyClosed) {

                $lop = $project->lop;
                $currentStage = $lop?->stage; // ProjectStage|null
                $currentSequence = $currentStage?->sequence;
                $isPausedOrDropped = (bool) ($currentStage?->is_pause_type || $currentStage?->is_terminal);

                if ($lop && $currentSequence !== null && ! $isPausedOrDropped) {

                    if (
                        $evidence->stage == 'persiapan'
                        && ($summary['persiapanDone'] ?? false)
                        && $currentSequence <= 6 // masih di fase Persiapan / Persiapan Instalasi (seq 1-6)
                    ) {
                        // Kelima sub-step Persiapan baru (inisiasi/survey/drm/
                        // perizinan/material_delivery) + Persiapan Instalasi
                        // belum punya UI upload sendiri (Stage 4, belum
                        // dikerjakan). Eviden 'persiapan' yang ada sekarang
                        // (barang_tiba + perizinan) masih dianggap mewakili
                        // SELURUH fase Persiapan lama.
                        $lop->advanceStage('instalasi', auth()->id());

                    } elseif (
                        $evidence->stage == 'instalasi'
                        && ($summary['instalasiDone'] ?? false)
                        && $currentSequence === 7 // persis di tahap Instalasi
                    ) {
                        $lop->advanceStage('pengukuran', auth()->id());

                    } elseif (
                        $evidence->stage == 'pengukuran'
                        && ($summary['pengukuranDone'] ?? false)
                        && $currentSequence === 8 // persis di tahap Pengukuran
                    ) {
                        $lop->advanceStage('finishing', auth()->id());
                    }
                }
            } // <-- Akhir dari Barier Pelindung

            /*
            |--------------------------------------------------------------------------
            | PROJECT COMPLETE ACTIVITY (KHUSUS REGULER)
            |--------------------------------------------------------------------------
            | FIX: trigger dipindah dari `progress == 100` (yang sekarang
            | berarti sungguh-sungguh Golive, seq 11) ke `finishingDone` --
            | ini yang secara semantik berarti "seluruh eviden wajib s.d.
            | Finishing sudah disetujui, siap lanjut ke FI-OGP Golive".
            */
            $alreadyCompleteLogged = ProjectActivityLog::where('project_id', $project->id_project)
                ->where('activity_type', 'project_completed')
                ->exists();

            if (! $isPt2 && ! $isAlreadyClosed && ($summary['finishingDone'] ?? false) && ! $alreadyCompleteLogged) {

                ProjectActivityService::log([
                    'project_id' => $project->id_project,
                    'lop_id' => $lopId,
                    'activity_type' => 'project_completed',
                    'title' => 'Project Complete',
                    'description' => 'Seluruh eviden wajib telah disetujui.',
                    'status_after' => 'completed',
                    'meta' => [
                        'progress' => $summary['progress'] ?? null,
                    ],
                ]);

                Notification::create([
                    'user_id' => $evidence->uploaded_by,
                    'project_id' => $evidence->project_id,
                    'type' => 'ready_ut',
                    'title' => 'Project Ready UT',
                    'message' => 'Project "'.$evidence->project->project_name.'" telah selesai dan siap UT.',
                    'redirect_url' => route('waspang.projects.show', $evidence->project_id),
                ]);
            }
        }

        return back()->with('success', 'Eviden berhasil diapprove');
    }

    public function rejectEvidence(Request $request, $id)
    {
        $request->validate([
            'review_note' => 'required|string',
        ]);

        $evidence = Evidence::with(['project', 'boqItem', 'uploader'])->findOrFail($id);

        $oldStatus = $evidence->status;
        $evidenceLabel = $this->evidenceLabel($evidence);

        $evidence->status = 'rejected';
        $evidence->review_note = $request->review_note;
        $evidence->save();

        Notification::create([
            'user_id' => $evidence->uploaded_by,
            'project_id' => $evidence->project_id,
            'type' => 'reject',
            'title' => 'Eviden ditolak Admin',
            'message' => 'Eviden '.$evidence->stage.' ditolak. Note: "'.$request->review_note.'"',
            'redirect_url' => route('waspang.projects.show', $evidence->project_id),
        ]);

        // WEBHOOK EVENT: eviden direject -- event pribadi utk waspang/teknisi pengunggah.
        if ($evidence->uploader) {
            TelegramWebhookEventService::publishToUser(
                $evidence->uploader,
                'evidence_rejected',
                'Eviden Ditolak',
                "Eviden {$evidenceLabel} pada tahap {$evidence->stage} untuk project ".($evidence->project->project_name ?? '-').' ditolak oleh Admin. Catatan: "'.$request->review_note.'"',
                [
                    'evidence_id' => $evidence->id_evidence,
                    'evidence_label' => $evidenceLabel,
                    'stage' => $evidence->stage,
                    'evidence_type' => $evidence->evidence_type,
                    'project_name' => $evidence->project->project_name ?? null,
                    'review_note' => $request->review_note,
                ],
                ['project_id' => $evidence->project_id]
            );
        }

        EvidenceRevisionHistory::create([
            'evidence_id' => $evidence->id_evidence,
            'project_id' => $evidence->project_id,
            'reviewed_by' => auth()->user()->id_user,
            'stage' => $evidence->stage,
            'evidence_type' => $evidence->evidence_type,
            'review_note' => $request->review_note,
            'status' => 'rejected',
        ]);

        ProjectActivityService::log([
            'project_id' => $evidence->project_id,
            'lop_id' => Lop::where('project_id', $evidence->project_id)->value('id_lop'),
            'evidence_id' => $evidence->id_evidence,
            'activity_type' => 'reject_evidence',
            'title' => 'Eviden Ditolak',
            'description' => 'Admin menolak eviden: '.$evidenceLabel.'. Catatan: '.$request->review_note,
            'stage' => $evidence->stage,
            'status_before' => $oldStatus,
            'status_after' => 'rejected',
            'meta' => [
                'review_note' => $request->review_note,
                'evidence_type' => $evidence->evidence_type,
                'boq_item_id' => $evidence->boq_item_id,
                'boq_designator' => $evidence->boqItem?->designator,
                'boq_item_name' => $evidence->boqItem?->item_name,
            ],
        ]);

        return back()->with('success', 'Eviden berhasil direject');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'evidence_ids' => 'required|array',
            'evidence_ids.*' => 'exists:evidences,id_evidence',
        ]);

        // Update semua ID yang dikirim menjadi approved
        Evidence::whereIn('id_evidence', $request->evidence_ids)
            ->update([
                'status' => 'approved',
                'review_note' => null, // Hapus note reject jika sebelumnya ada
            ]);

        return back()->with('success', count($request->evidence_ids).' Eviden berhasil di-approve sekaligus.');
    }

    public function bulkReviewEvidence(Request $request)
    {
        $request->validate([
            'project_id' => 'required',
            'action' => 'required|in:approve,reject',
        ]);

        $status = $request->action === 'approve'
            ? 'approved'
            : 'rejected';

        Evidence::where('project_id', $request->project_id)
            ->where('status', 'pending')
            ->update([
                'status' => $status,
            ]);

        return back()->with('success', 'Bulk review eviden berhasil diproses');
    }

    public function reviewIndex()
    {
        $projects = Project::with([
            'evidences',
            'assignment.waspang',
        ])->get();

        return view('admin.evidences.approval', compact('   '));
    }

    /**
     * Section AP: Step "Persiapan" (baru) di Approval Konstruksi admin --
     * ringkasan 4 sub-step LOP sebelum Persiapan Instalasi (inisiasi/survey/
     * perizinan/material_delivery, sequence 1-5), menyamakan struktur
     * stepper Approval Konstruksi dgn stepper Waspang terbaru (permintaan
     * user). Inisiasi & Survey tidak punya eviden foto yg perlu di-approve
     * di sini (murni indikator posisi LOP) -- Perizinan & Material Delivery
     * PUNYA eviden foto (lihat WaspangController::persiapan()/addPerizinan()/
     * finishMaterialDelivery()), jadi ditampilkan & bisa di-approve/reject
     * lewat partial review-item yg sama dgn step lain (route generik
     * admin.evidences.approve/reject/reset TIDAK dibatasi per-stage, jadi
     * otomatis berfungsi tanpa perubahan backend approve/reject).
     */
    public function reviewPersiapan($id)
    {
        $project = Project::with([
            'evidences',
        ])->findOrFail($id);

        $summary = $project->progressSummary();
        $seq = $summary['effectiveStageSequence'] ?? null;
        $stageCode = $summary['effectiveStageCode'] ?? null;
        if ($stageCode === 'drm') {
            $stageCode = 'perizinan';
        }

        // Breakdown 4 sub-step -- status murni dari posisi sequence LOP,
        // persis pola WaspangController::persiapan().
        $subSteps = [
            'inisiasi' => [
                'label' => 'Inisiasi',
                'done' => $seq !== null && $seq > 1,
                'active' => $stageCode === 'inisiasi',
            ],
            'survey' => [
                'label' => 'Survey',
                'done' => $seq !== null && $seq > 2,
                'active' => $stageCode === 'survey',
            ],
            'perizinan' => [
                'label' => 'Perizinan',
                'done' => $seq !== null && $seq > 4,
                'active' => $stageCode === 'perizinan',
            ],
            'material_delivery' => [
                'label' => 'Material Delivery',
                'done' => $seq !== null && $seq > 5,
                'active' => $stageCode === 'material_delivery',
            ],
        ];

        $evidences = $project->evidences ?? collect();

        // Eviden yang BISA di-approve Admin di step ini (stage='perizinan'
        // evidence_type eviden_perizinan/ba_kp, dan stage='material_delivery').
        $perizinanEvidences = $evidences->where('stage', 'perizinan')
            ->where('evidence_type', '!=', 'ba_kp');
        $baKpEvidences = $evidences->where('stage', 'perizinan')
            ->where('evidence_type', 'ba_kp');
        $materialDeliveryEvidences = $evidences->where('stage', 'material_delivery');

        $persiapanDone = $seq !== null && $seq > 5;

        return view('admin.evidences.review-persiapan', compact(
            'project',
            'subSteps',
            'perizinanEvidences',
            'baKpEvidences',
            'materialDeliveryEvidences',
            'persiapanDone'
        ));
    }

    public function reviewProject($id)
    {
        $project = Project::with([
            'evidences',
            'boqItems',
        ])->findOrFail($id);

        $barangTiba = $project->evidences
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'barang_tiba');

        $perizinan = $project->evidences
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'perizinan');

        return view('admin.evidences.review-project', compact(
            'project',
            'barangTiba',
            'perizinan'
        ));
    }

    public function resetEvidence($id)
    {
        // 1. Cari eviden berdasarkan ID spesifik yang diklik
        $evidence = Evidence::with(['project', 'boqItem'])->findOrFail($id);

        $oldStatus = $evidence->status;
        $evidenceLabel = $this->evidenceLabel($evidence);

        // 2. UPDATE HANYA PADA 1 ID INI SAJA (Jangan pakai where stage / evidence_type massal)
        $evidence->update([
            'status' => 'pending',
            'review_note' => null,
        ]);

        // 3. Catat activity log & notifikasi (opsional, sesuaikan dengan kode Anda)
        $lopId = Lop::where('project_id', $evidence->project_id)->value('id_lop');

        ProjectActivityService::log([
            'project_id' => $evidence->project_id,
            'lop_id' => $lopId,
            'evidence_id' => $evidence->id_evidence,
            'activity_type' => 'reset_evidence',
            'title' => 'Approval Eviden Dibatalkan',
            'description' => 'Admin membatalkan approval pada eviden: '.$evidenceLabel,
            'stage' => $evidence->stage,
            'status_before' => $oldStatus,
            'status_after' => 'pending',
            'meta' => [
                'evidence_type' => $evidence->evidence_type,
                'boq_item_id' => $evidence->boq_item_id,
            ],
        ]);

        Notification::create([
            'user_id' => $evidence->uploaded_by,
            'project_id' => $evidence->project_id,
            'type' => 'reject',
            'title' => 'Approval Dibatalkan',
            'message' => 'Status eviden '.ucfirst($evidence->stage).' diubah kembali menjadi pending oleh Admin.',
            'redirect_url' => route('waspang.projects.show', $evidence->project_id),
        ]);

        return back()->with('success', 'Status eviden berhasil diatur ulang menjadi pending.');
    }

    // STEP 2 - REVIEW INSTALASI
    public function reviewInstalasi($id)
    {
        $project = Project::with([
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
            'evidences',
            'assignment.waspang',
            'lop',
        ])->where('id_project', $id)->firstOrFail();

        return view('admin.evidences.review-instalasi', compact('project'));
    }

    // STEP 3 - REVIEW PENGUKURAN
    public function reviewPengukuran($id)
    {
        $project = Project::with([
            'evidences',
            'boqItems',
            'assignment.waspang',
        ])->findOrFail($id);

        return view('admin.evidences.review-pengukuran', compact('project'));
    }

    // STEP 4 - REVIEW FINISHING
    public function reviewFinishing($id)
    {
        $project = Project::with([
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
            'evidences',
            'assignment.waspang',
            'lop',
        ])->where('id_project', $id)->firstOrFail();

        return view('admin.evidences.review-finishing', compact('project'));
    }

    // STEP 5 - REVIEW / UPLOAD DOKUMEN FI-OGP GOLIVE (Section AF)
    public function reviewGolive($id)
    {
        $project = Project::with([
            'lop.goliveSubmission',
            'lop.goliveVerification',
            'assignment.waspang',
        ])->where('id_project', $id)->firstOrFail();

        return view('admin.evidences.review-golive', compact('project'));
    }

    // Section AF: upload/perbarui 4 dokumen FI-OGP Golive (capture valins,
    // PDF ABD & Valid4, KML, Mancore -- foto ATAU excel, lihat
    // LopGoliveSubmission::mancore_input_type). Field dikirim satu-satu,
    // upload parsial diperbolehkan (form bisa disubmit berkali-kali sampai
    // lengkap) -- makanya validasi semuanya 'nullable', bukan 'required'.
    public function submitGoliveDocuments(Request $request, $id)
    {
        $project = Project::with(['lop.stage'])->where('id_project', $id)->firstOrFail();
        $lop = $project->lop;

        if (! $lop) {
            return back()->with('error', 'LOP untuk project ini belum ada.');
        }

        $request->validate([
            'capture_valins' => 'nullable|array',
            'capture_valins.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'abd_valid4' => 'nullable|array',
            'abd_valid4.*' => 'file|mimes:pdf|max:10240',
            'kml' => 'nullable|array',
            'kml.*' => 'file|mimes:kml,xml|max:5120',
            'mancore_input_type' => 'nullable|in:photo,excel',
            'mancore' => 'nullable|array',
            'mancore.*' => 'file|mimes:jpeg,png,jpg,webp,xls,xlsx|max:10240',
        ]);

        $submission = \App\Models\LopGoliveSubmission::firstOrNew(['lop_id' => $lop->id_lop]);
        $submission->lop_id = $lop->id_lop;

        $folder = 'evidences/golive/'.$lop->id_lop;

        // Revisi (permintaan user): tiap kategori sekarang boleh MULTIPLE
        // file -- file baru DITAMBAHKAN ke daftar yg sudah ada (bukan
        // menimpa), supaya upload boleh dilakukan bertahap/berkali-kali
        // tanpa menghilangkan file yg sudah tersimpan. Kolom *_path lama
        // ikut disinkron ke file TERAKHIR (kompatibilitas mundur, dibaca
        // di tempat lain yg belum diupdate ke *_paths).
        $appendFiles = function (string $key, string $prefix) use ($request, $folder, $submission) {
            if (! $request->hasFile($key)) {
                return;
            }

            $existing = $submission->filesFor($key);

            foreach ((array) $request->file($key) as $file) {
                if (! $file) {
                    continue;
                }

                $filename = $prefix.'_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $existing[] = $file->storeAs($folder, $filename, 'public');
            }

            $submission->{$key.'_paths'} = $existing;
            $submission->{$key.'_path'} = end($existing) ?: null;
        };

        $appendFiles('capture_valins', 'capture_valins');
        $appendFiles('abd_valid4', 'abd_valid4');
        $appendFiles('kml', 'kml');
        $appendFiles('mancore', 'mancore');

        if ($request->filled('mancore_input_type')) {
            $submission->mancore_input_type = $request->input('mancore_input_type');
        }

        // Revisi (permintaan user): "Tanggal FI" = momen admin SELESAI
        // upload ke-4 kategori dokumen (isComplete() PERTAMA KALI true).
        // Sengaja TIDAK ditimpa lagi kalau admin upload/hapus file lagi
        // setelahnya -- ini histori, bukan status live.
        if ($submission->isComplete() && ! $submission->fi_completed_at) {
            $submission->fi_completed_at = now();
        }

        $submission->submitted_by = auth()->id();
        $submission->submitted_at = now();
        $submission->save();

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'golive_submission_upload',
            'title' => 'Dokumen FI-OGP Golive Diunggah',
            'description' => 'Admin mengunggah/memperbarui dokumen FI-OGP Golive untuk LOP: '.$lop->lop_name,
            'status_after' => $submission->isComplete() ? 'complete' : 'partial',
        ]);

        /*
        |--------------------------------------------------------------------------
        | AUTO-ADVANCE status_progress: finishing (9) -> fi_ogp_golive (10)
        |--------------------------------------------------------------------------
        | Barier & gate PERSIS SAMA dgn approveEvidence()/toggleMeasurementCheck():
        | bukan PT2, belum drop/golive, tidak sedang hold/drop, persis di
        | sequence 9 (Finishing), DAN finishingDone (seluruh eviden wajib s.d.
        | Finishing sudah disetujui). Baru maju kalau ke-4 dokumen submission
        | ini JUGA lengkap.
        */
        $programSap = strtoupper($lop->program_sap ?? '');
        $isPt2 = str_contains($programSap, 'PT2') || str_contains($programSap, 'PT-2') || str_contains($programSap, 'PT 2');
        $isAlreadyClosed = in_array($lop->status_progress, ['drop', 'golive'], true) || (bool) $lop->is_golive;

        $project = $project->fresh(['lop.stage']);
        $summary = $project->progressSummary();
        $currentStage = $project->lop?->stage;
        $currentSequence = $currentStage?->sequence;
        $isPausedOrDropped = (bool) ($currentStage?->is_pause_type || $currentStage?->is_terminal);

        if (
            ! $isPt2 && ! $isAlreadyClosed
            && $project->lop && $currentSequence !== null && ! $isPausedOrDropped
            && ($summary['finishingDone'] ?? false)
            && $currentSequence === 9
            && $submission->isComplete()
        ) {
            $lop->advanceStage('fi_ogp_golive', auth()->id());

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => 'lop_stage_advance',
                'title' => 'LOP Maju ke FI-OGP Golive',
                'description' => 'Seluruh dokumen FI-OGP Golive lengkap, LOP maju otomatis ke tahap FI-OGP Golive.',
                'status_before' => 'finishing',
                'status_after' => 'fi_ogp_golive',
            ]);
        }

        return back()->with('success', 'Dokumen FI-OGP Golive berhasil disimpan.');
    }

    // Revisi (permintaan user): hapus 1 file yg sudah tersimpan dari salah
    // satu kategori dokumen FI-OGP Golive (sekarang multi-file per
    // kategori) -- file fisik ikut dihapus dari storage. Kolom *_path
    // lama disinkron ulang ke file TERAKHIR yg tersisa (atau null kalau
    // kategori itu jadi kosong).
    public function removeGoliveDocument(Request $request, $id)
    {
        $request->validate([
            'key' => 'required|in:capture_valins,abd_valid4,kml,mancore',
            'path' => 'required|string',
        ]);

        $project = Project::with(['lop.goliveSubmission'])->where('id_project', $id)->firstOrFail();
        $lop = $project->lop;
        $submission = $lop?->goliveSubmission;

        if (! $submission) {
            return back()->with('error', 'Belum ada dokumen FI-OGP Golive untuk LOP ini.');
        }

        $key = $request->input('key');
        $path = $request->input('path');
        $existing = $submission->filesFor($key);

        if (! in_array($path, $existing, true)) {
            return back()->with('error', 'File tidak ditemukan atau sudah dihapus sebelumnya.');
        }

        $existing = array_values(array_filter($existing, fn ($p) => $p !== $path));
        $submission->{$key.'_paths'} = $existing;
        $submission->{$key.'_path'} = end($existing) ?: null;
        $submission->save();

        Storage::disk('public')->delete($path);

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'golive_submission_delete',
            'title' => 'File Dokumen FI-OGP Golive Dihapus',
            'description' => 'Admin menghapus 1 file kategori '.$key.' pada dokumen FI-OGP Golive LOP: '.$lop->lop_name,
        ]);

        return back()->with('success', 'File berhasil dihapus.');
    }

    public function reviewBoq($id)
    {
        // Ambil project lengkap dengan relasi LOP dan item BOQ beserta data master designator-nya
        $project = Project::with([
            'lop',
            'boqItems' => function ($query) {
                $query->with('designatorData');
            },
        ])->findOrFail($id);

        // Ambil peta harga designator (harga terbaru) untuk Package LOP ini,
        // dipakai untuk menghitung Nilai Material & Nilai Jasa berdasarkan Qty Actual.
        $packageId = $project->lop?->package_id;
        $priceMap = collect();

        if ($packageId) {
            $latestPriceIds = DB::table('designator_package_prices')
                ->selectRaw('MAX(id_price) AS id_price')
                ->where('package_id', $packageId)
                ->groupBy('designator_id');

            $priceMap = DB::table('designator_package_prices as dpp')
                ->joinSub($latestPriceIds, 'latest_price', function ($join) {
                    $join->on('latest_price.id_price', '=', 'dpp.id_price');
                })
                ->selectRaw("dpp.designator_id, CAST(NULLIF(TRIM(dpp.price), '') AS DECIMAL(20,2)) AS price")
                ->get()
                ->pluck('price', 'designator_id');
        }

        // Lempar data ke file review-boq yang baru saja dibuat
        return view('admin.evidences.review-boq', compact('project', 'priceMap'));
    }

    public function downloadPreview($id)
    {
        $project = Project::with([
            'lop',
            'evidences' => function ($q) {
                $q->where('status', 'approved'); // Hanya ambil berkas yang lolos verifikasi
            },
        ])->findOrFail($id);

        return view('admin.evidences.download-preview', compact('project'));
    }

    public function downloadZip(Request $request, $id)
    {
        $onlyStage = $request->query('only_stage'); // Ambil info parameter pemicu unduhan step

        // Section AR: only_stage sekarang boleh berupa daftar dipisah koma
        // (mis. 'perizinan,material_delivery' utk tombol Step 1 Persiapan
        // yg baru, Section AP) -- 1 nilai tunggal tetap jalan persis seperti
        // sebelumnya (whereIn dgn 1 elemen setara where biasa).
        $onlyStages = $onlyStage
            ? array_values(array_filter(array_map('trim', explode(',', $onlyStage))))
            : [];

        $project = Project::with(['evidences' => function ($q) use ($onlyStages) {
            $q->where('status', 'approved')
                ->when(! empty($onlyStages), function ($sub) use ($onlyStages) {
                    $sub->whereIn('stage', $onlyStages); // Filter stage jika diklik tombol per-step
                });
        }])->findOrFail($id);

        $evidences = $project->evidences;

        if ($evidences->isEmpty()) {
            return back()->with('error', 'Tidak ada data foto yang disetujui untuk diunduh.');
        }

        // Nama file dinamis
        $suffix = $onlyStage ? '_'.ucfirst(str_replace(',', '-', $onlyStage)) : '_Semua_Eviden';
        $zipFileName = 'Eviden'.$suffix.'_'.Str::slug($project->project_name).'.zip';
        $zipPath = storage_path('app/public/'.$zipFileName);

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($evidences as $evidence) {
                // Membaca relasi untuk menyematkan nama designator ke file fisik jika ada
                $evidence->load('boqItem');
                $designatorCode = $evidence->boqItem ? '_'.Str::slug($evidence->boqItem->designator) : '';

                $filePath = public_path('storage/'.$evidence->file_path);

                if (file_exists($filePath) && is_file($filePath)) {
                    $folderInsideZip = $evidence->stage.'/'.$evidence->evidence_type.'/';
                    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

                    // Menyertakan kode designator ke nama file di dalam ZIP agar informatif sewaktu dibuka admin
                    $fileNameInsideZip = $folderInsideZip.now()->format('Ymd').$designatorCode.'_'.uniqid().'.'.$fileExtension;

                    $zip->addFile($filePath, $fileNameInsideZip);
                }
            }
            $zip->close();
        }

        if (! file_exists($zipPath)) {
            return back()->with('error', 'Gagal membuat file arsip kompresi.');
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function storeBoq(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id_project',
            'designator_id' => 'required|array',
            'boq_qty' => 'required|array',
        ]);

        $project = Project::findOrFail($request->project_id);

        // Ambil LOP otomatis (1 Project = 1 LOP)
        $lop = $project->lop;

        if (! $lop) {
            return back()
                ->with('error', 'LOP untuk project ini belum tersedia.')
                ->with('reopen_lop', $request->input('reopen_lop'));
        }

        foreach ($request->designator_id as $index => $designatorId) {

            if (! $designatorId) {
                continue;
            }

            $designator = Designator::forCustomer($project->customer_id)
                ->where('id_designator', $designatorId)
                ->first();

            if (! $designator) {
                continue;
            }

            // Cek duplicate
            $exists = BoqItem::where('lop_id', $lop->id_lop)
                ->where('designator_id', $designator->id_designator)
                ->exists();

            if ($exists) {
                continue;
            }

            BoqItem::create([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,

                'designator_id' => $designator->id_designator,
                'designator' => $designator->designator,
                'item_name' => $designator->item_name,
                'unit' => $designator->unit,

                'quantity_plan' => $request->boq_qty[$index] ?? 0,
                'quantity_actual' => 0,
            ]);
        }

        return back()
            ->with('success', 'Item BOQ berhasil ditambahkan.')
            ->with('reopen_lop', $request->input('reopen_lop'));
    }

    // RELASI DENGAN LOP
    public function lops()
    {
        return $this->hasMany(Lop::class, 'project_id', 'id_project');
    }

    // UPLOAD FILE KML
    public function uploadKml(Request $request, $id)
    {
        $request->validate([
            'kml_file' => 'required|file|mimes:kml,xml|max:5120',
        ]);

        $project = Project::where('id_project', $id)->firstOrFail();
        $previousKmlPath = $project->kml_file;

        $file = $request->file('kml_file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = str_replace([' ', '/', '\\'], '-', strtolower($originalName));
        $fileName = $safeName.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.kml';

        // Simpan file ke storage public
        $path = $file->storeAs('kml', $fileName, 'public');
        $project->kml_file = $path;

        /*
        |--------------------------------------------------------------------------
        | PARSING KOORDINAT ANCHOR UNTUK CLUSTER DASHBOARD
        |--------------------------------------------------------------------------
        */
        try {
            $kmlContent = file_get_contents($file->getRealPath());
            // Bersihkan namespace XML agar mudah dibaca oleh SimpleXMLElement
            $cleanKml = preg_replace('/xmlns="[^"]+"/', '', $kmlContent);
            $xml = new \SimpleXMLElement($cleanKml);

            // Cari tag <coordinates> pertama di dalam file KML
            $coordinates = $xml->xpath('//coordinates');
            if (! empty($coordinates)) {
                // Bersihkan spasi/line break dan ambil baris koordinat pertama
                $coordString = trim((string) $coordinates[0]);
                $pureCoords = preg_split('/\s+/', $coordString);
                $firstSet = explode(',', $pureCoords[0]);

                if (count($firstSet) >= 2) {
                    // Format KML standar adalah: longitude, latitude, altitude
                    $project->kml_lng = current($firstSet);
                    $project->kml_lat = next($firstSet);
                }
            }
        } catch (\Exception $e) {
            // Jika parsing gagal karena file corrupt, biarkan null atau log errornya
        }

        $project->save();

        // File lama sengaja tidak dihapus. Pointer-nya disimpan di activity
        // log agar seluruh desain Admin tetap tersedia sebagai histori.
        if ($previousKmlPath && $previousKmlPath !== $path && Storage::disk('public')->exists($previousKmlPath)) {
            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'activity_type' => 'survey_admin_kml_archived',
                'title' => 'Desain KML Admin Diarsipkan',
                'description' => 'Versi KML Admin sebelumnya disimpan sebagai histori Survey.',
                'stage' => 'survey',
                'meta' => ['kml_path' => $previousKmlPath],
            ]);
        }

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'activity_type' => 'survey_admin_kml_uploaded',
            'title' => 'Desain KML Admin Diunggah',
            'description' => 'Admin mengunggah desain KML untuk referensi Survey.',
            'stage' => 'survey',
            'meta' => ['kml_path' => $path],
        ]);

        return back()->with('success', 'File KML berhasil diupload dan koordinat jangkar berhasil diekstrak.');
    }

    public function viewKml($id)
    {
        $project = Project::where('id_project', $id)->firstOrFail();

        if (! $project->kml_file) {
            return back()->withErrors('File KML belum tersedia.');
        }

        $kmlUrl = asset('storage/'.$project->kml_file);

        return view('admin.projects.kml-map', compact('project', 'kmlUrl'));
    }

    // private function defaultCustomerId(): ?int
    // {
    //     return DB::table('customers')
    //         ->where('customer_code', 'TIF')
    //         ->value('id_customer');
    // }

    private function evidenceLabel($evidence)
    {
        $typeLabels = [
            'barang_tiba' => 'Barang Tiba',
            'perizinan' => 'Perizinan',
            'progress_boq' => 'Progress BOQ',
            'otdr' => 'OTDR',
            'opm' => 'OPM',
            'kedalaman' => 'Kedalaman Galian',
            'finishing' => 'Finishing',
        ];

        $stageLabel = ucfirst($evidence->stage ?? '-');
        $typeLabel = $typeLabels[$evidence->evidence_type] ?? ucfirst(str_replace('_', ' ', $evidence->evidence_type));

        if ($evidence->boqItem) {
            return $stageLabel.' | '.
                $typeLabel.' | '.
                ($evidence->boqItem->designator ?? '-').' - '.
                ($evidence->boqItem->item_name ?? '-');
        }

        return $stageLabel.' | '.$typeLabel;
    }
}
