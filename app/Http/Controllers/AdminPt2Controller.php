<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pt2Project;
use App\Models\Pt2Lop;
use App\Models\SurveyPt2;
use App\Models\Pt2Evidence;
use App\Models\DismantlePt2;
use App\Models\MancorePt2;
use App\Models\User;
use App\Services\ProjectActivityService;
use Illuminate\Support\Facades\DB;

class AdminPt2Controller extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FUNGSI INDEX (HALAMAN DAFTAR PROJECT & LOP ACCORDION)
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Pt2Project::with([
            'lops.boqItems',
            'lops.assignment.teknisi',
            'lops.evidences',
            'lops.surveys'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('pid', 'like', "%{$search}%")
                  ->orWhere('pid_sap', 'like', "%{$search}%")
                  ->orWhereHas('lops', function ($lop) use ($search) {
                      $lop->where('sto', 'like', "%{$search}%")
                          ->orWhere('branch', 'like', "%{$search}%")
                          ->orWhere('mitra_name', 'like', "%{$search}%")
                          ->orWhere('lop_name', 'like', "%{$search}%")
                          ->orWhere('id_ihld', 'like', "%{$search}%");
                  });
            });
        }

        $regions = [
            'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
            'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
            'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
        ];

        if ($request->filled('region')) {
            $selectedRegion = strtoupper($request->region);
            if (isset($regions[$selectedRegion])) {
                $query->whereIn(DB::raw('UPPER(branch)'), $regions[$selectedRegion]);
            }
        }

        if ($request->filled('branch')) {
            $query->whereRaw('UPPER(branch) = ?', [strtoupper($request->branch)]);
        }

        if ($request->filled('status_project')) {
            $query->where('status_project', $request->status_project);
        }

        $perPage = $request->input('per_page', 10);
        $projects = $query->latest('updated_at')->paginate($perPage)->onEachSide(1)->withQueryString();

        $branches = Pt2Lop::whereNotNull('branch')->where('branch', '!=', '')->distinct()->orderBy('branch')->pluck('branch');
        $assignableUsers = User::whereIn('role', ['teknisi', 'waspang'])->get();

        return view('admin.pt2.index', compact('projects', 'branches', 'assignableUsers'));
    }

   /*
    |--------------------------------------------------------------------------
    | FUNGSI APPROVAL LIST (HALAMAN DAFTAR LOP YANG BUTUH REVIEW)
    |--------------------------------------------------------------------------
    */
    public function approvalList(Request $request)
    {
        // Default filter diarahkan ke 'active' (On Progress)
        $statusFilter = $request->input('status_filter', 'active');
        $search = $request->input('search');
        $region = $request->input('region');
        $branch = $request->input('branch');

        // Menggunakan tabel Pt2Lop sebagai basis
        $query = \App\Models\Pt2Lop::with(['project', 'assignment.teknisi', 'evidences', 'surveys']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('lop_name', 'like', "%{$search}%")
                  ->orWhere('id_ihld', 'like', "%{$search}%")
                  ->orWhereHas('project', function($qProj) use ($search) {
                      $qProj->where('pid', 'like', "%{$search}%")
                            ->orWhere('pid_sap', 'like', "%{$search}%");
                  });
            });
        }

        $regions = [
            'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
            'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
            'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
        ];

        if ($region && isset($regions[strtoupper($region)])) {
            $query->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(branch)'), $regions[strtoupper($region)]);
        }

        if ($branch) {
            $query->whereRaw('UPPER(branch) = ?', [strtoupper($branch)]);
        }

        // ==========================================
        // LOGIKA TAB FILTER YANG AKURAT
        // ==========================================
        if ($statusFilter === 'pending') {
            // MENUNGGU REVIEW: Punya minimal 1 eviden berstatus 'pending'
            $query->whereHas('evidences', function($qEv) {
                $qEv->where('status', 'pending');
            });
                  
        } elseif ($statusFilter === 'completed') {
            // SELESAI: Sudah sampai tahap akhir (Mancore), atau sudah dikirim ke SDI, atau Go-Live
            $query->where(function($q) {
                $q->whereIn('status_progress', ['done', 'mancore', 'complete'])
                  ->orWhere('is_golive', 1)
                  ->orWhereIn('sdi_approval_status', ['pending', 'approved']);
            });

        } elseif ($statusFilter === 'active') {
            // ON PROGRESS: 
            // 1. TIDAK ada eviden pending
            // 2. Belum masuk tahap Selesai/Go-Live
            // 3. SUDAH ADA AKTIVITAS (Telah upload foto eviden / survey) -> LOP Kosong disembunyikan
            $query->whereDoesntHave('evidences', function($qEv) {
                $qEv->where('status', 'pending');
            })->where(function($q) {
                $q->whereNotIn('status_progress', ['done', 'mancore', 'complete'])
                  ->orWhereNull('status_progress');
            })->where(function($q) {
                $q->whereNull('sdi_approval_status')
                  ->orWhereNotIn('sdi_approval_status', ['pending', 'approved']);
            })->where(function($q) {
                $q->where('is_golive', 0)
                  ->orWhereNull('is_golive');
            })->where(function($q) {
                // KUNCI PERBAIKAN: Menjamin hanya LOP yang sudah diisi teknisi yang muncul
                $q->whereHas('evidences')
                  ->orWhereHas('surveys');
            });
        }

        $lops = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        $branches = \App\Models\Pt2Lop::whereNotNull('branch')->where('branch', '!=', '')->distinct()->orderBy('branch')->pluck('branch');

        return view('admin.pt2.approval-list', compact('lops', 'branches'));
    }

    /*
    |--------------------------------------------------------------------------
    | FUNGSI REVIEW KELENGKAPAN PER LOP (Suntik Data ke Blade Lama)
    |--------------------------------------------------------------------------
    */

    public function review($lop_id)
    {
        $lop = Pt2Lop::with(['project', 'assignment.teknisi', 'evidences', 'surveys'])->findOrFail($lop_id);

        // Trik agar file Blade lama Anda tetap jalan tanpa error
        $project = $lop->project;
        $project->lop = $lop; 
        $project->evidences = $lop->evidences; 
        $project->assignment = $lop->assignment; 
        $project->pt2Survey = $lop->surveys()->first(); 

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

    public function approveSurvey(Request $request, $lop_id)
    {
        $survey = SurveyPt2::where('pt2_lop_id', $lop_id)->firstOrFail();
        $survey->update(['pm_approval_status' => 'approved']);
        return back()->with('success', 'Data Survey lapangan berhasil disetujui!');
    }

    public function rejectSurvey(Request $request, $lop_id)
    {
        $request->validate(['kendala_note' => 'required|string']);
        $survey = SurveyPt2::where('pt2_lop_id', $lop_id)->firstOrFail();
        $survey->update([
            'pm_approval_status' => 'rejected',
            'kendala_note' => $request->kendala_note,
            'has_kendala' => 1
        ]);
        return back()->with('success', 'Data Survey ditolak dan dikembalikan ke Teknisi.');
    }

    public function resetSurvey($lop_id)
    {
        $survey = SurveyPt2::where('pt2_lop_id', $lop_id)->firstOrFail();
        $survey->update(['pm_approval_status' => 'pending']);
        return back()->with('success', 'Status Survey berhasil diatur ulang menjadi Pending.');
    }

    public function reviewInstalasi($lop_id)
    {
        $lop = Pt2Lop::with(['project', 'assignment.teknisi', 'evidences'])->findOrFail($lop_id);
        
        $project = $lop->project;
        $project->lop = $lop;
        $project->evidences = $lop->evidences;
        $project->assignment = $lop->assignment;

        return view('admin.pt2.instalasi', compact('project'));
    }

    public function reviewRedaman($lop_id)
    {
        $lop = Pt2Lop::with(['project', 'assignment.teknisi', 'evidences'])->findOrFail($lop_id);
        
        $project = $lop->project;
        $project->lop = $lop;
        $project->evidences = $lop->evidences;
        $project->assignment = $lop->assignment;

        return view('admin.pt2.redaman', compact('project'));
    }

    public function reviewDismantle($lop_id)
    {
        $lop = Pt2Lop::with(['project', 'assignment.teknisi', 'evidences'])->findOrFail($lop_id);
        
        $project = $lop->project;
        $project->lop = $lop;
        $project->evidences = $lop->evidences;
        $project->assignment = $lop->assignment;

        // Tarik data dismantle dari tabel dismantles_pt2
        $dismantles = DismantlePt2::where('pt2_lop_id', $lop_id)->get();

        return view('admin.pt2.dismantle', compact('project', 'dismantles'));
    }

    public function reviewMancore($lop_id)
    {
        $lop = Pt2Lop::with(['project', 'assignment.teknisi'])->findOrFail($lop_id);
        
        $project = $lop->project;
        $project->lop = $lop;
        $project->assignment = $lop->assignment;
        $project->pt2Mancore = MancorePt2::where('pt2_lop_id', $lop_id)->first();

        return view('admin.pt2.mancore', compact('project'));
    }

    /*
    |--------------------------------------------------------------------------
    | FUNGSI GO-LIVE (SDI) - PER LOP
    |--------------------------------------------------------------------------
    */
    public function sendToSdi($lop_id)
    {
        $lop = Pt2Lop::findOrFail($lop_id);

        // Update status spesifik LOP untuk masuk antrean SDI
        $lop->update([
            'sdi_approval_status' => 'pending', 
            'updated_at' => now(),
        ]);

        return back()->with('success', 'LOP berhasil dikirim ke SDI untuk proses Go-Live.');
    }

    /*
    |--------------------------------------------------------------------------
    | FUNGSI APPROVE / REJECT EVIDEN KHUSUS PT2
    |--------------------------------------------------------------------------
    */
    public function approveEvidencePt2($id_pt2_evidence)
    {
        $evidence = Pt2Evidence::findOrFail($id_pt2_evidence);

        $oldStatus = $evidence->status;
        $evidence->status = 'approved';
        $evidence->review_note = null;
        $evidence->save();

        ProjectActivityService::log([
            'project_id' => $evidence->pt2_project_id,
            'lop_id' => $evidence->pt2_lop_id,
            'evidence_id' => $evidence->id_pt2_evidence,
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

    public function rejectEvidencePt2(Request $request, $id_pt2_evidence)
    {
        $request->validate([
            'review_note' => 'required|string',
        ]);

        $evidence = Pt2Evidence::findOrFail($id_pt2_evidence);

        $oldStatus = $evidence->status;
        $evidence->status = 'rejected';
        $evidence->review_note = $request->review_note;
        $evidence->save();

        ProjectActivityService::log([
            'project_id' => $evidence->pt2_project_id,
            'lop_id' => $evidence->pt2_lop_id,
            'evidence_id' => $evidence->id_pt2_evidence,
            'activity_type' => 'reject_evidence_pt2',
            'title' => 'Eviden PT2 Ditolak',
            'description' => 'Admin menolak eviden PT2. Catatan: ' . $request->review_note,
            'stage' => $evidence->stage,
            'status_before' => $oldStatus,
            'status_after' => 'rejected',
        ]);

        return back()->with('success', 'Eviden PT2 berhasil ditolak.');
    }

    public function resetEvidencePt2($id_pt2_evidence)
    {
        $evidence = Pt2Evidence::findOrFail($id_pt2_evidence);
        
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
            'evidence_ids.*' => 'exists:pt2_evidences,id_pt2_evidence',
        ]);

        Pt2Evidence::whereIn('id_pt2_evidence', $request->evidence_ids)
            ->update([
                'status' => 'approved',
                'review_note' => null 
            ]);

        return back()->with('success', count($request->evidence_ids) . ' Eviden PT2 berhasil disetujui sekaligus.');
    }
}