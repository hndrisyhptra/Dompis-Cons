<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectAssignment;
use App\Models\Designator;
use App\Models\BoqItem;
use App\Models\Evidence;
use App\Models\Notification;
use App\Models\Lop;
use App\Models\ProjectActivityLog;
use App\Models\EvidenceRevisionHistory;
use App\Services\ProjectActivityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ProjectController extends Controller
{
   public function index(Request $request)
    {
        $query = Project::with([
            'boqItems',
            'assignments.waspang',
            'assignment.waspang',
            'evidences',
            'lop',
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
        ]);

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

        $projects = $query
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $programs = Project::whereNotNull('program')
            ->where('program', '!=', '')
            ->distinct()
            ->orderBy('program')
            ->pluck('program');

        $branches = Lop::whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');

        $waspangs = User::with('assignments')
            ->where('role', 'waspang')
            ->get();

        $designators = Designator::orderBy('designator')->get();

        $totalProject = Project::count();
        $activeProject = Project::where('status', 'active')->count();
        $waitingUt = Project::where('status', 'waiting_ut')->count();
        $completedProject = Project::where('status', 'completed')->count();

        return view('admin.projects.index', compact(
            'projects',
            'programs',
            'branches',
            'waspangs',
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
            'waspang_id' => 'required',
        ]);

        $oldAssignment = ProjectAssignment::where('project_id', $request->project_id)->first();

        ProjectAssignment::updateOrCreate(
            [
                'project_id' => $request->project_id
            ],
            [
                'waspang_id' => $request->waspang_id
            ]
        );

        $lop = Lop::where('project_id', $request->project_id)->first();
        $waspang = User::where('id_user', $request->waspang_id)->first();

        ProjectActivityService::log([
            'project_id' => $request->project_id,
            'lop_id' => $lop?->id_lop,
            'target_user_id' => $request->waspang_id,
            'activity_type' => $oldAssignment ? 'reassign_waspang' : 'assign_waspang',
            'title' => $oldAssignment ? 'Reassign Waspang' : 'Assign Waspang',
            'description' => 'Project di-assign ke Waspang ' . ($waspang?->name ?? '-'),
            'status_before' => $oldAssignment ? 'assigned' : 'unassigned',
            'status_after' => 'assigned',
            'meta' => [
                'old_waspang_id' => $oldAssignment?->waspang_id,
                'new_waspang_id' => $request->waspang_id,
                'new_waspang_name' => $waspang?->name,
            ],
        ]);

        return back()->with('success', 'Waspang berhasil di-assign');
    }

    public function removeAssign($project)
    {
        $oldAssignment = ProjectAssignment::where('project_id', $project)->first();

        ProjectAssignment::where('project_id', $project)->delete();

        ProjectActivityService::log([
            'project_id' => $project,
            'lop_id' => Lop::where('project_id', $project)->value('id_lop'),
            'target_user_id' => $oldAssignment?->waspang_id,
            'activity_type' => 'remove_assignment',
            'title' => 'Assignment Dihapus',
            'description' => 'Assignment Waspang dihapus dari project.',
            'status_before' => 'assigned',
            'status_after' => 'unassigned',
            'meta' => [
                'old_waspang_id' => $oldAssignment?->waspang_id,
            ],
        ]);

        return back()->with('success', 'Assignment berhasil dihapus');
    }

    public function show($id)
    {
        $project = Project::with([
            'boqItems',
            'assignments.waspang',
            'evidences'
        ])->findOrFail($id);

        return view('admin.projects.show', compact('project'));
    }

    //CRUD PROJECT
    public function store(Request $request)
    {
        $request->validate([
        'project_name' => 'required|string|max:255',
        'branch' => 'required|string|max:255',
        'sto' => 'required|string|max:20',
        'mitra_name' => 'nullable|string|max:100',
        'jenis_eksekusi' => 'required|in:plan,survey,ogp,finish',
        'designator_id' => 'nullable|array',
        'designator_id.*' => 'nullable|exists:designators,id_designator',
        'boq_qty' => 'nullable|array',
        'boq_qty.*' => 'nullable|numeric|min:0',
    ]);

    DB::beginTransaction();

    try {

        $project = Project::create([
            'project_name' => $request->project_name,
            'branch' => $request->branch,
            'sto' => $request->sto,
            'mitra_name' => $request->mitra_name,
            'jenis_eksekusi' => $request->jenis_eksekusi,
            'status' => 'active',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_address' => $request->location_address,
        ]);

        if ($request->has('designator_id')) {

            foreach ($request->designator_id as $index => $designatorId) {

                if (!$designatorId) {
                    continue;
                }

                $qty = $request->boq_qty[$index] ?? 0;

                if ($qty === null || $qty === '') {
                    $qty = 0;
                }

                $designator = Designator::find($designatorId);

                if (!$designator) {
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

            return back()->with('error', 'Gagal menyimpan LOP: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $request->validate([
            'project_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'sto' => 'nullable|string|max:20',
            'mitra_name' => 'nullable|string|max:100',
            'jenis_eksekusi' => 'required|in:plan,survey,ogp,finish',
            'status' => 'required|in:active,completed,waiting_ut',
        ]);

        $project->update($request->only([
            'project_name',
            'branch',
            'sto',
            'mitra_name',
            'jenis_eksekusi',
            'status',
            'latitude',
            'longitude',
            'location_address',
        ]));

        // UPDATE BOQ LAMA
        if ($request->existing_boq_id) {
            foreach ($request->existing_boq_id as $index => $boqId) {
                BoqItem::where('id_boq', $boqId)
                    ->where('project_id', $project->id_project)
                    ->update([
                        'quantity_plan' => $request->existing_qty[$index] ?? 0,
                    ]);
            }
        }

        // TAMBAH DESIGNATOR BARU DARI MODAL EDIT
        if ($request->designator_id) {
            foreach ($request->designator_id as $index => $designatorId) {

                if (!$designatorId) {
                    continue;
                }

                $designator = Designator::find($designatorId);

                if (!$designator) {
                    continue;
                }

                BoqItem::create([
                    'project_id' => $project->id_project,
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

public function importCsv(Request $request)
    {
        $request->validate([
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
            'status',
        ];

        if ($header !== $requiredHeader) {
            fclose($file);

            return back()->with(
                'error',
                'Format header CSV tidak sesuai'
            );
        }

        $allowedJenis = ['plan', 'survey', 'ogp', 'finish'];
        $allowedStatus = ['active', 'completed', 'waiting_ut'];

        $total = 0;

        while (($row = fgetcsv($file)) !== false) {

            if (count($row) < 6) {
                continue;
            }

            $jenisEksekusi = strtolower(trim($row[4]));
            $status = strtolower(trim($row[5]));

            if (!in_array($jenisEksekusi, $allowedJenis)) {
                continue;
            }

            if (!in_array($status, $allowedStatus)) {
                continue;
            }

            Project::create([
                'project_name' => trim($row[0]),
                'branch' => trim($row[1]),
                'sto' => trim($row[2]),
                'mitra_name' => trim($row[3]),
                'jenis_eksekusi' => $jenisEksekusi,
                'status' => $status,
            ]);

            $total++;
        }

        fclose($file);

        return back()->with(
            'success',
            $total . ' project berhasil diimport'
        );
    }


    //APPROVAL FROM ADMIN
    public function approvalIndex(Request $request)
    {
        $search = $request->search;

        $projects = Project::with([
            'evidences',
            'boqItems',
            'assignment.waspang',
        ])
        ->whereHas('evidences')
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                ->orWhere('sto', 'like', "%{$search}%")
                ->orWhere('branch', 'like', "%{$search}%")
                ->orWhere('mitra_name', 'like', "%{$search}%");
            })
            ->orWhereHas('assignment.waspang', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        })
        ->latest('updated_at')
        ->paginate(12)
        ->withQueryString();

        return view('admin.evidences.approval', compact('projects', 'search'));
    }

    public function approveEvidence($id)
    {
        $evidence = Evidence::with('project')->findOrFail($id);

        $oldStatus = $evidence->status;

        Evidence::where('project_id', $evidence->project_id)
            ->where('stage', $evidence->stage)
            ->where('evidence_type', $evidence->evidence_type)
            ->when($evidence->boq_item_id, function ($query) use ($evidence) {
                $query->where('boq_item_id', $evidence->boq_item_id);
            })
            ->update([
                'status' => 'approved',
                'review_note' => null,
            ]);

        $lopId = Lop::where('project_id', $evidence->project_id)->value('id_lop');

        ProjectActivityService::log([
            'project_id' => $evidence->project_id,
            'lop_id' => $lopId,
            'evidence_id' => $evidence->id_evidence,
            'activity_type' => 'approve_evidence',
            'title' => 'Eviden Disetujui',
            'description' => 'Admin menyetujui eviden tahap ' . ucfirst($evidence->stage),
            'stage' => $evidence->stage,
            'status_before' => $oldStatus,
            'status_after' => 'approved',
            'meta' => [
                'evidence_type' => $evidence->evidence_type,
                'boq_item_id' => $evidence->boq_item_id,
            ],
        ]);

        Notification::create([
            'user_id' => $evidence->uploaded_by,
            'project_id' => $evidence->project_id,
            'type' => 'approved',
            'title' => 'Eviden disetujui',
            'message' => 'Eviden ' . ucfirst($evidence->stage) . ' Project ' . $evidence->project->project_name . ' telah disetujui Admin.',
            'redirect_url' => route('waspang.projects.show', $evidence->project_id),
        ]);

        $project = Project::with([
            'evidences',
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
        ])->find($evidence->project_id);

        if ($project) {
            $summary = $project->progressSummary();

            $alreadyCompleteLogged = ProjectActivityLog::where('project_id', $project->id_project)
                ->where('activity_type', 'project_completed')
                ->exists();

            if (($summary['progress'] ?? 0) == 100 && !$alreadyCompleteLogged) {
                $project->update([
                    'status' => 'completed',
                ]);

                ProjectActivityService::log([
                    'project_id' => $project->id_project,
                    'lop_id' => $lopId,
                    'activity_type' => 'project_completed',
                    'title' => 'Project Complete',
                    'description' => 'Seluruh eviden wajib sudah approved. Project mencapai progress 100%.',
                    'status_after' => 'completed',
                    'meta' => [
                        'progress' => 100,
                        'stage' => $summary['stage'] ?? null,
                    ],
                ]);

                Notification::create([
                    'user_id' => $evidence->uploaded_by,
                    'project_id' => $evidence->project_id,
                    'type' => 'ready_ut',
                    'title' => 'Project Ready UT',
                    'message' => 'Project "' . $evidence->project->project_name . '" siap uji terima.',
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

        $evidence = Evidence::with('project')->findOrFail($id);

        $oldStatus = $evidence->status;

        Evidence::where('project_id', $evidence->project_id)
            ->where('stage', $evidence->stage)
            ->where('evidence_type', $evidence->evidence_type)
            ->when($evidence->boq_item_id, function ($query) use ($evidence) {
                $query->where('boq_item_id', $evidence->boq_item_id);
            })
            ->update([
                'status' => 'rejected',
                'review_note' => $request->review_note,
            ]);

        Notification::create([
            'user_id' => $evidence->uploaded_by,
            'project_id' => $evidence->project_id,
            'type' => 'reject',
            'title' => 'Eviden ditolak Admin',
            'message' => 'Eviden ' . $evidence->stage . ' ditolak. Note: "' . $request->review_note . '"',
            'redirect_url' => route('waspang.projects.show', $evidence->project_id),
        ]);

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
            'description' => 'Admin menolak eviden. Catatan: ' . $request->review_note,
            'stage' => $evidence->stage,
            'status_before' => $oldStatus,
            'status_after' => 'rejected',
            'meta' => [
                'review_note' => $request->review_note,
                'evidence_type' => $evidence->evidence_type,
                'boq_item_id' => $evidence->boq_item_id,
            ],
        ]);

        return back()->with('success', 'Eviden berhasil direject');
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
            'assignment.waspang'
        ])->get();

        return view('admin.evidences.approval', compact('   '));
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
        $evidence = Evidence::findOrFail($id);

        Evidence::where('project_id', $evidence->project_id)
            ->where('stage', $evidence->stage)
            ->where('evidence_type', $evidence->evidence_type)
            ->when($evidence->boq_item_id, function ($query) use ($evidence) {
                $query->where('boq_item_id', $evidence->boq_item_id);
            })
            ->update([
                'status' => 'pending',
                'review_note' => null,
            ]);

        return back()->with('success', 'Status berhasil di atur ulang');
    }

    //STEP 2 - REVIEW INSTALASI
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

    //STEP 3 - REVIEW PENGUKURAN
    public function reviewPengukuran($id)
    {
        $project = Project::with([
            'evidences',
            'boqItems',
            'assignment.waspang',
        ])->findOrFail($id);

        return view('admin.evidences.review-pengukuran', compact('project'));
    }

    //STEP 4 - REVIEW FINISHING
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

    public function storeBoq(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id_project',
            'designator_id' => 'required|array',
            'boq_qty' => 'required|array',
        ]);

        foreach ($request->designator_id as $index => $designatorId) {

            if (!$designatorId) {
                continue;
            }

            $designator = Designator::find($designatorId);

            if (!$designator) {
                continue;
            }

            BoqItem::create([
                'project_id' => $request->project_id,
                'designator' => $designator->designator,
                'item_name' => $designator->item_name,
                'unit' => $designator->unit,
                'quantity_plan' => $request->boq_qty[$index] ?? 0,
                'quantity_actual' => 0,
            ]);
        }

        return back()->with('success', 'Item BOQ berhasil ditambahkan.');
    }

    //RELASI DENGAN LOP
    public function lops()
    {
        return $this->hasMany(Lop::class, 'project_id', 'id_project');
    }

    //UPLOAD FILE KML
    public function uploadKml(Request $request, $id)
    {
        $request->validate([
            'kml_file' => 'required|file|mimes:kml,xml|max:5120',
        ]);

        $project = Project::where('id_project', $id)->firstOrFail();

        if ($project->kml_file && Storage::disk('public')->exists($project->kml_file)) {
            Storage::disk('public')->delete($project->kml_file);
        }

        $file = $request->file('kml_file');

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = str_replace([' ', '/', '\\'], '-', strtolower($originalName));

        $fileName = $safeName . '-' . time() . '.kml';

        $path = $file->storeAs('kml', $fileName, 'public');

        $project->kml_file = $path;
        $project->save();

        return back()->with('success', 'File KML berhasil diupload');
    }

    public function viewKml($id)
    {
        $project = Project::where('id_project', $id)->firstOrFail();

        if (!$project->kml_file) {
            return back()->withErrors('File KML belum tersedia.');
        }

        $kmlUrl = asset('storage/' . $project->kml_file);

        return view('admin.projects.kml-map', compact('project', 'kmlUrl'));
    }

}