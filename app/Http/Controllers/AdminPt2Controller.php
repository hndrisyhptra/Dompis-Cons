<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class AdminPt2Controller extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->input('status_filter', 'pending');
        $search = $request->input('search');

        // Query dasar: Ambil yang Program SAP-nya mengandung unsur PT2
        $query = Project::with(['lop', 'assignment.teknisi', 'evidences', 'pt2Survey'])
            ->whereHas('lop', function ($q) {
                $q->where(function($subQ) {
                    $subQ->where('program_sap', 'LIKE', '%PT2%')
                         ->orWhere('program_sap', 'LIKE', '%PT-2%')
                         ->orWhere('program_sap', 'LIKE', '%PT 2%');
                });
            });

        // Filter Pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhereHas('lop', function ($qLop) use ($search) {
                      $qLop->where('id_ihld', 'like', "%{$search}%")
                           ->orWhere('sto', 'like', "%{$search}%");
                  });
            });
        }

        // LOGIKA TAB FILTER YANG SUDAH DIPERBAIKI
        if ($statusFilter === 'pending') {
            // Tampilkan yang ADA eviden pending DAN belum Go-Live SDI
            $query->whereHas('evidences', function($qEv) {
                $qEv->where('status', 'pending');
            })->where(function($qSdi) {
                $qSdi->whereNull('sdi_approval_status')
                     ->orWhere('sdi_approval_status', '!=', 'approve');
            });
        } elseif ($statusFilter === 'active') {
            // On Progress: Semua yang belum selesai
            $query->whereNotIn('status', ['completed', 'close']);
        } elseif ($statusFilter === 'completed') {
            // Completed: Tampilkan project yang sudah Selesai / Go-Live / Close
            $query->whereIn('status', ['completed', 'close']); 
        }

        $projects = $query->orderBy('updated_at', 'desc')->paginate(10);

        return view('admin.pt2.approval-list', compact('projects'));
    }

    public function review($id)
    {
        $project = Project::with([
            'lop', 
            'assignment.teknisi', 
            'evidences', 
            'pt2Survey'
        ])->findOrFail($id);

        $survey = $project->pt2Survey;
        $mode = $survey ? $survey->mode : 'A';
        
        $requiredEvidences = [];
        if ($mode === 'A') {
            $requiredEvidences = ['power_in' => 'Foto Eviden Power IN', 'power_out' => 'Foto Eviden Power OUT'];
        } elseif ($mode === 'B') {
            $requiredEvidences = [
                'base_tray_feeder' => 'Foto Eviden Base Tray Feeder', 
                'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi',
                'power_in_feeder' => 'Foto Power IN Feeder', 
                'power_out_splitter' => 'Foto Power OUT Splitter Ex'
            ];
        } elseif ($mode === 'C') {
            $requiredEvidences = [
                'base_tray_feeder' => 'Foto Eviden Base Tray Feeder', 
                'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi'
            ];
        } else {
            $requiredEvidences = ['survey' => 'Foto Eviden Survey Lapangan'];
        }

        return view('admin.pt2.review', compact('project', 'mode', 'requiredEvidences'));
    }

    public function approveSurvey(Request $request, $id)
    {
        $survey = \App\Models\Pt2Survey::where('project_id', $id)->firstOrFail();
        $survey->update(['pm_approval_status' => 'approved']);
        return back()->with('success', 'Data Survey lapangan berhasil disetujui!');
    }

    public function rejectSurvey(Request $request, $id)
    {
        $request->validate(['kendala_note' => 'required|string']);
        $survey = \App\Models\Pt2Survey::where('project_id', $id)->firstOrFail();
        $survey->update([
            'pm_approval_status' => 'rejected',
            'kendala_note' => $request->kendala_note,
            'has_kendala' => 1
        ]);
        return back()->with('success', 'Data Survey ditolak dan dikembalikan ke Teknisi.');
    }

    public function resetSurvey($id)
    {
        $survey = \App\Models\Pt2Survey::where('project_id', $id)->firstOrFail();
        $survey->update(['pm_approval_status' => 'pending']);
        return back()->with('success', 'Status Survey berhasil diatur ulang menjadi Pending.');
    }

    public function reviewInstalasi($id)
    {
        $project = Project::with(['lop', 'assignment.teknisi', 'evidences'])->findOrFail($id);
        return view('admin.pt2.instalasi', compact('project'));
    }

    public function reviewRedaman($id)
    {
        $project = Project::with(['lop', 'assignment.teknisi', 'evidences'])->findOrFail($id);
        return view('admin.pt2.redaman', compact('project'));
    }

    public function reviewDismantle($id)
    {
        $project = Project::with(['lop', 'assignment.teknisi', 'evidences'])->findOrFail($id);
        $dismantles = \Illuminate\Support\Facades\DB::table('dismantles')->where('project_id', $id)->get();
        return view('admin.pt2.dismantle', compact('project', 'dismantles'));
    }

    public function reviewMancore($id)
    {
        $project = Project::with(['lop', 'assignment.teknisi'])->findOrFail($id);
        return view('admin.pt2.mancore', compact('project'));
    }

    public function sendToSdi($id)
    {
        $project = \App\Models\Project::findOrFail($id);

        $project->status = 'waiting_ut';
        $project->sdi_approval_status = 'pending';
        $project->save();

        // [PERBAIKAN] Saat dikirim ke SDI, majukan progress LOP ke finishing secara paksa
        \App\Models\Lop::where('project_id', $project->id_project)
            ->update(['status_progress' => 'finishing']);

        return back()->with('success', 'Project berhasil dikirim! Menunggu proses Go-Live dari tim SDI.');
    }

    /*
    |--------------------------------------------------------------------------
    | FUNGSI APPROVE / REJECT EVIDEN KHUSUS PT2
    |--------------------------------------------------------------------------
    */

    public function approveEvidencePt2($id)
    {
        $evidence = \App\Models\Evidence::with(['project'])->findOrFail($id);

        $oldStatus = $evidence->status;

        // 1. Set status menjadi approved
        $evidence->status = 'approved';
        $evidence->review_note = null;
        $evidence->save();

        // 2. Catat Log Activity
        $lopId = \App\Models\Lop::where('project_id', $evidence->project_id)->value('id_lop');
        
        \App\Services\ProjectActivityService::log([
            'project_id' => $evidence->project_id,
            'lop_id' => $lopId,
            'evidence_id' => $evidence->id_evidence,
            'activity_type' => 'approve_evidence_pt2',
            'title' => 'Eviden PT2 Disetujui',
            'description' => 'Admin menyetujui eviden PT2 kategori: ' . $evidence->evidence_type,
            'stage' => $evidence->stage,
            'status_before' => $oldStatus,
            'status_after' => 'approved',
            'meta' => [
                'evidence_type' => $evidence->evidence_type,
            ],
        ]);

        return back()->with('success', 'Eviden PT2 berhasil disetujui.');
    }

    public function rejectEvidencePt2(Request $request, $id)
    {
        $request->validate([
            'review_note' => 'required|string',
        ]);

        $evidence = \App\Models\Evidence::with(['project'])->findOrFail($id);

        $oldStatus = $evidence->status;

        // 1. Set status menjadi rejected
        $evidence->status = 'rejected';
        $evidence->review_note = $request->review_note;
        $evidence->save();

        // 2. Catat riwayat revisi
        \App\Models\EvidenceRevisionHistory::create([
            'evidence_id' => $evidence->id_evidence,
            'project_id' => $evidence->project_id,
            'reviewed_by' => auth()->user()->id_user,
            'stage' => $evidence->stage,
            'evidence_type' => $evidence->evidence_type,
            'review_note' => $request->review_note,
            'status' => 'rejected',
        ]);

        // 3. Catat Log Activity
        $lopId = \App\Models\Lop::where('project_id', $evidence->project_id)->value('id_lop');

        \App\Services\ProjectActivityService::log([
            'project_id' => $evidence->project_id,
            'lop_id' => $lopId,
            'evidence_id' => $evidence->id_evidence,
            'activity_type' => 'reject_evidence_pt2',
            'title' => 'Eviden PT2 Ditolak',
            'description' => 'Admin menolak eviden PT2. Catatan: ' . $request->review_note,
            'stage' => $evidence->stage,
            'status_before' => $oldStatus,
            'status_after' => 'rejected',
        ]);

        return back()->with('success', 'Eviden PT2 berhasil ditolak.');
    }

    public function resetEvidencePt2($id)
    {
        $evidence = \App\Models\Evidence::findOrFail($id);
        
        $evidence->update([
            'status' => 'pending',
            'review_note' => null,
        ]);

        return back()->with('success', 'Status Eviden PT2 berhasil di-reset menjadi pending.');
    }

    public function bulkApprovePt2(Request $request)
    {
        $request->validate([
            'evidence_ids' => 'required|array',
            'evidence_ids.*' => 'exists:evidences,id_evidence',
        ]);

        \App\Models\Evidence::whereIn('id_evidence', $request->evidence_ids)
            ->update([
                'status' => 'approved',
                'review_note' => null 
            ]);

        return back()->with('success', count($request->evidence_ids) . ' Eviden PT2 berhasil disetujui sekaligus.');
    }
}