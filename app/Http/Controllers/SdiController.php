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
    // Menampilkan Daftar Project (Hanya yang dikirim Admin & Sudah GoLive)
    public function index(Request $request)
    {
        // 1. Ambil data project beserta relasi LOP
        $query = Project::with('lop')
            ->where(function ($q) {
                // KONDISI A: Menampilkan project yang dikirim Admin (Sedang antre UT/SDI)
                $q->where(function ($sub) {
                    $sub->where('status', 'waiting_ut')
                        ->where('sdi_approval_status', 'pending');
                })
                // KONDISI B: ATAU menampilkan project yang sudah selesai di-GoLive
                ->orWhere('is_golive', 1);
            });

        // 2. Logika Pencarian (Search)
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('project_name', 'like', '%' . $request->search . '%')
                  ->orWhere('pid', 'like', '%' . $request->search . '%')
                  ->orWhereHas('lop', function ($lopQ) use ($request) {
                      $lopQ->where('id_ihld', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // 3. Eksekusi query dengan Pagination
        $projects = $query->latest('updated_at')->paginate($request->per_page ?? 10);
        
        return view('sdi.index', compact('projects'));
    }

    // Memproses Upload Eviden UIM dan Update Status Go-Live
    public function submitGolive(Request $request, $id)
    {
        $request->validate([
            // Tambahkan webp sebagai best practice kompresi gambar saat ini
            'golive_evidence' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', 
        ]);

        $project = Project::findOrFail($id);

        if ($request->hasFile('golive_evidence')) {
            $file = $request->file('golive_evidence');
            $filename = 'UIM_' . time() . '_' . $project->id_project . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('evidences/golive', $filename, 'public');

            /*
            |--------------------------------------------------------------------------
            | EKSEKUSI GOLIVE FINAL
            |--------------------------------------------------------------------------
            */
            $project->update([
                'status' => 'completed',            // Kunci utama agar project masuk tab Completed
                'status_project' => 'close',        // Menyatakan fisik project ditutup
                'sdi_approval_status' => 'approved',
                'is_golive' => 1,                   // Flag ampuh untuk query Dashboard
                'golive_evidence_path' => $path,
                'golive_at' => now(),               // Timestamp resmi Golive
            ]);

            // Update LOP status progress untuk kepastian akhir
            Lop::where('project_id', $project->id_project)->update([
                'status_progress' => 'finishing'
            ]);

            /*
            |--------------------------------------------------------------------------
            | CATAT SEJARAH AKTIVITAS
            |--------------------------------------------------------------------------
            */
            $lopId = Lop::where('project_id', $project->id_project)->value('id_lop');
            
            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lopId,
                'activity_type' => 'project_golive',
                'title' => 'Project Go-Live (SDI)',
                'description' => 'Tim SDI telah mengunggah Eviden UIM dan meresmikan status Go-Live.',
                'status_after' => 'completed',
            ]);

            return back()->with('success', '🎉 Luar biasa! Project berhasil di-GoLive dan Eviden UIM telah tersimpan!');
        }

        return back()->with('error', 'Gagal mengupload eviden. Silakan coba lagi.');
    }
}