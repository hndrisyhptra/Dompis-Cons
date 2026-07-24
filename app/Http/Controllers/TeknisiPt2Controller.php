<?php

namespace App\Http\Controllers;

use App\Models\Pt2Survey;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Notification;
use App\Models\Evidence;
use App\Models\EvidenceRevisionHistory;
use App\Models\Lop;
use App\Models\User;
use App\Services\ProjectActivityService;
use App\Models\ProjectIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TeknisiPt2Controller extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Tambahkan 'pt2Mancore' pada with()
        $projects = Project::with(['evidences', 'boqItems.designatorData', 'pt2Mancore'])
            ->whereHas('assignment', function ($query) use ($user) {
                $query->where('teknisi_id', $user->id_user); 
            })
            ->get();

        $totalAssigned = $projects->count();
        $activeProjectsCount = $projects->where('is_golive', false)->count();

        return view('teknisi.dashboard', compact('projects', 'totalAssigned', 'activeProjectsCount'));
    }

    public function inbox()
    {
        $user = Auth::user();
        
        $projects = Project::with(['pt2Survey'])
            ->whereHas('assignment', function ($query) use ($user) {
                $query->where('teknisi_id', $user->id_user);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('teknisi.pt2.inbox', compact('projects'));
    }

    public function step1($project_id)
    {
        $project = Project::with('pt2Survey')->findOrFail($project_id);
        
        // Jika sudah pernah survey, bisa redirect ke step 2 atau tampilkan form readonly
        if ($project->pt2Survey) {
            // return redirect()->route('teknisi.pt2.step2', $project_id);
        }

        return view('teknisi.pt2.step1', compact('project'));
    }

    public function storeStep1(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

        $request->validate([
            'status_survey' => 'required|in:kendala,eksekusi',
            'mode' => 'required_if:status_survey,eksekusi|in:A,B,C',
            // Validasi file dan input disesuaikan berdasarkan Mode (A/B/C)
        ]);

        // Logic upload file foto eviden berdasarkan mode ...
        // (Bisa menggunakan helper upload gambar yang sudah ada di dompis_cons)

        // Insert ke tabel pt2_surveys
        Pt2Survey::create([
            'project_id' => $project->id,
            'status_survey' => $request->status_survey,
            'kendala_note' => $request->kendala_note,
            'mode' => $request->mode,
            'sub_mode_a' => $request->sub_mode_a,
            'detail_data' => json_encode($request->except(['_token', 'foto_eviden'])), // Simpan data dinamis
            'is_approved_pm' => $request->status_survey === 'kendala' ? false : null,
        ]);

        return redirect()->route('teknisi.pt2.index')->with('success', 'Survey Step 1 berhasil disimpan.');
    }
}