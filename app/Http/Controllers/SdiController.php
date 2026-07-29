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
        $query = \App\Models\Project::with('lop')
            ->where(function ($q) {
                // KONDISI A: Menampilkan project yang dikirim Admin (Sedang antre)
                $q->where(function ($sub) {
                    $sub->where('status', 'waiting_ut')
                        ->where('sdi_approval_status', 'pending');
                })
                // KONDISI B: ATAU menampilkan project yang sudah selesai di-GoLive
                ->orWhere('is_golive', 1);
            });

        // (Opsional) Jika di sistem Anda SDI HANYA memegang program PT 2, aktifkan baris ini:
        // $query->where('program', 'PT 2'); 

        // 2. Logika Pencarian (Search)
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('project_name', 'like', '%' . $request->search . '%')
                  ->orWhere('pid', 'like', '%' . $request->search . '%');
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
            'golive_evidence' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $project = Project::findOrFail($id);

        if ($request->hasFile('golive_evidence')) {
            $file = $request->file('golive_evidence');
            $filename = 'UIM_' . time() . '_' . $project->id_project . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('evidences/golive', $filename, 'public');

            // UPDATE MENGGUNAKAN STRUKTUR KOLOM DATABASE ANDA
            $project->update([
                'is_golive' => 1,
                'golive_evidence_path' => $path,
                'golive_at' => now(), // Mencatat waktu go-live
                'sdi_approval_status' => 'approved', // Ubah status sdi
                'status_project' => 'close' // Project dinyatakan close/selesai secara fisik
            ]);

            return back()->with('success', '🎉 Luar biasa! Project berhasil di-GoLive dan Eviden UIM telah tersimpan!');
        }

        return back()->with('error', 'Gagal mengupload eviden. Silakan coba lagi.');
    }
}