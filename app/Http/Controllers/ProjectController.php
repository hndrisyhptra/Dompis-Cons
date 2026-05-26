<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectAssignment;
use App\Models\Designator;
use App\Models\BoqItem;
use App\Models\Evidence;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with([
            'boqItems',
            'assignments.waspang',
            'evidences'
        ]);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('project_name', 'like', '%' . $request->search . '%')
                  ->orWhere('sto', 'like', '%' . $request->search . '%')
                  ->orWhere('branch', 'like', '%' . $request->search . '%')
                  ->orWhere('mitra_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->get();

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

        ProjectAssignment::updateOrCreate(
            ['project_id' => $request->project_id],
            ['waspang_id' => $request->waspang_id]
        );

        return back()->with('success', 'Waspang berhasil di-assign');
    }

    public function removeAssign($project)
    {
        ProjectAssignment::where('project_id', $project)->delete();

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
        ]));

        return back()->with('success', 'Project berhasil diperbarui');
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
        $evidence = Evidence::findOrFail($id);

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

        return back()->with('success', 'Eviden berhasil diapprove');
    }

    public function rejectEvidence(Request $request, $id)
    {
        $request->validate([
            'review_note' => 'required|string',
        ]);

        $evidence = Evidence::findOrFail($id);

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
            'evidences',
            'boqItems',
            'assignment.waspang',
        ])->findOrFail($id);

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

    //STEP 4 - REVIEW PENGUKURAN
    public function reviewFinishing($id)
    {
        $project = Project::with([
            'evidences',
            'boqItems',
            'assignment.waspang',
        ])->findOrFail($id);

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

}