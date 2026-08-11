<?php

namespace App\Http\Controllers;

use App\Models\Pt2Lop;
use App\Services\ProjectActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SdiController extends Controller
{
    // Menampilkan Daftar LOP PT 2 untuk SDI Approval
    // Menampilkan Daftar LOP PT 2 untuk SDI Approval
    public function index(Request $request)
    {
        // Gunakan whereNotNull dan where('sdi_approval_status', '!=', '') 
        // untuk memastikan LOP yang belum dikirim admin (masih kosong/null) tersaring keluar sepenuhnya.
        $query = \App\Models\Pt2Lop::with(['project', 'assignment.teknisi'])
            ->whereNotNull('sdi_approval_status')
            ->where('sdi_approval_status', '!=', '')
            ->whereIn('sdi_approval_status', ['pending', 'approved']);

        // Logika Pencarian (Search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lop_name', 'like', '%' . $search . '%')
                ->orWhere('id_ihld', 'like', '%' . $search . '%')
                ->orWhere('sto', 'like', '%' . $search . '%')
                ->orWhereHas('project', function ($projQ) use ($search) {
                    $projQ->where('pid', 'like', '%' . $search . '%');
                });
            });
        }

        // Filter status jika ingin (opsional)
        if ($request->filled('status_filter')) {
            $query->where('sdi_approval_status', $request->status_filter);
        }

        $lops = $query->latest('updated_at')->paginate($request->per_page ?? 10)->withQueryString();
        
        return view('sdi.index', compact('lops'));
    }

    // Memproses Upload Eviden UIM dan Go-Live Per LOP
    public function submitGolive(Request $request, $lop_id)
    {
        $request->validate([
            'golive_evidence' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', 
        ]);

        $lop = Pt2Lop::findOrFail($lop_id);

        if ($request->hasFile('golive_evidence')) {
            $file = $request->file('golive_evidence');
            $filename = 'UIM_PT2_LOP_' . time() . '_' . $lop->id_pt2_lop . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('evidences/golive/pt2', $filename, 'public');

            // Update status Go-Live spesifik pada tabel pt2_lops
            $lop->update([
                'is_golive' => 1,
                'sdi_approval_status' => 'approved',
                'golive_evidence_path' => $path,
                'golive_at' => now(),
            ]);

            // Catat log aktivitas
            ProjectActivityService::log([
                'project_id' => $lop->pt2_project_id,
                'lop_id' => $lop->id_pt2_lop,
                'activity_type' => 'lop_golive_pt2',
                'title' => 'LOP PT 2 Go-Live (SDI)',
                'description' => 'Tim SDI telah mengunggah Eviden UIM dan meresmikan status Go-Live untuk LOP: ' . $lop->lop_name,
                'status_after' => 'completed',
            ]);

            return back()->with('success', '🎉 Luar biasa! LOP PT 2 berhasil di-GoLive dan Eviden UIM telah tersimpan!');
        }

        return back()->with('error', 'Gagal mengupload eviden. Silakan coba lagi.');
    }
}