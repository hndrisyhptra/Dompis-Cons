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

class SdiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        // Ambil khusus program PT2
        $query = Project::with([
            'lop', 'assignment.waspang', 'assignment.teknisi', 'evidences', 'boqItems'
        ])->where(function ($q) {
            $q->where('program', 'PT 2')
              ->orWhere('program', 'PT2')
              ->orWhere('program', 'like', '%PT 2%');
        });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('pid', 'like', "%{$search}%");
            });
        }

        $projects = $query->latest('updated_at')
            ->paginate($perPage)
            ->onEachSide(1)
            ->withQueryString();

        return view('sdi.index', compact('projects'));
    }

    public function storeGoLive(Request $request, $id)
    {
        $request->validate([
            'golive_evidence' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'golive_evidence.required' => 'Eviden / Capture UIM wajib diupload.',
            'golive_evidence.image' => 'File harus berupa gambar (JPG/PNG).',
        ]);

        $project = Project::findOrFail($id);

        if ($request->hasFile('golive_evidence')) {
            $path = $request->file('golive_evidence')->store('evidences/golive', 'public');
            
            $project->update([
                'is_golive' => true,
                'golive_evidence_path' => $path,
                'golive_at' => now(),
                'sdi_approval_status' => 'approved'
            ]);
        }

        return back()->with('success', 'Project PID ' . $project->pid . ' berhasil diupdate menjadi GO LIVE.');
    }
}