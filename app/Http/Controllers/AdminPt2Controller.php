<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class AdminPt2Controller extends Controller
{
    public function index(Request $request)
    {
        // Ubah default filter menjadi 'pending' (Menunggu Review)
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

        // LOGIKA TAB FILTER YANG BENAR
        if ($statusFilter === 'pending') {
            // Tampilkan project yang statusnya waiting_ut ATAU punya eviden berstatus pending
            $query->where(function($q) {
                $q->where('status', 'waiting_ut')
                  ->orWhereHas('evidences', function($qEv) {
                      $qEv->where('status', 'pending');
                  });
            });
        } elseif ($statusFilter === 'active') {
            // Tampilkan semua project yang sedang dikerjakan (On Progress)
            $query->where('status', 'active');
        } elseif ($statusFilter === 'completed') {
            // Tampilkan project yang sudah Selesai / Go-Live
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
        
        // Aturan Eviden Wajib Berdasarkan Mode (Sama persis dengan Teknisi)
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
            // Fallback default jika mode belum terset
            $requiredEvidences = ['survey' => 'Foto Eviden Survey Lapangan'];
        }

        return view('admin.pt2.review', compact('project', 'mode', 'requiredEvidences'));
    }

    public function approveSurvey(Request $request, $id)
    {
        $survey = \App\Models\Pt2Survey::where('project_id', $id)->firstOrFail();
        
        $survey->update([
            'pm_approval_status' => 'approved'
        ]);

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
        
        $survey->update([
            'pm_approval_status' => 'pending'
        ]);

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
        
        // Ambil data dismantle dari database
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

        // Gunakan penetapan properti langsung dan ->save() 
        // Ini akan menembus blokade $fillable pada Model Laravel
        $project->status = 'waiting_ut';
        $project->sdi_approval_status = 'pending';
        $project->save();

        return back()->with('success', 'Project berhasil dikirim! Menunggu proses Go-Live dari tim SDI.');
    }
}