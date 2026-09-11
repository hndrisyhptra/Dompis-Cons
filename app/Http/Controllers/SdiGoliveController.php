<?php

namespace App\Http\Controllers;

use App\Models\Lop;
use App\Models\LopGoliveVerification;
use App\Services\ProjectActivityService;
use Illuminate\Http\Request;

/**
 * Section AF: verifikasi tahap Golive (sequence 11) utk LOP REGULER (model
 * Lop) -- TERPISAH dari SdiController (yang cuma menangani LOP PT2/Pt2Lop
 * lewat submitGolive()). LOP yang muncul di sini adalah yang sudah sampai
 * status_progress 'fi_ogp_golive' (sequence 10) dan dokumen submission-nya
 * (LopGoliveSubmission, diisi Admin di ProjectController::reviewGolive()/
 * submitGoliveDocuments()) sudah lengkap. SDI upload capture UIM di sini,
 * lalu LOP resmi jadi 'golive' + lops.is_golive=true.
 */
class SdiGoliveController extends Controller
{
    public function index(Request $request)
    {
        $query = Lop::with(['project', 'goliveSubmission', 'goliveVerification'])
            ->where('status_progress', 'fi_ogp_golive');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lop_name', 'like', '%'.$search.'%')
                    ->orWhere('id_ihld', 'like', '%'.$search.'%')
                    ->orWhere('sto', 'like', '%'.$search.'%');
            });
        }

        $lops = $query->latest('updated_at')->paginate($request->per_page ?? 10)->withQueryString();

        return view('sdi.golive.index', compact('lops'));
    }

    public function show($id)
    {
        $lop = Lop::with(['project', 'goliveSubmission', 'goliveVerification'])->findOrFail($id);

        return view('sdi.golive.show', compact('lop'));
    }

    public function verify(Request $request, $id)
    {
        $lop = Lop::with(['project', 'stage', 'goliveSubmission'])->findOrFail($id);

        $request->validate([
            'capture_uim' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if (! $lop->goliveSubmission || ! $lop->goliveSubmission->isComplete()) {
            return back()->with('error', 'Dokumen FI-OGP Golive dari Admin belum lengkap. Verifikasi belum bisa dilakukan.');
        }

        $file = $request->file('capture_uim');
        $path = $file->storeAs(
            'evidences/golive/'.$lop->id_lop,
            'capture_uim_'.time().'.'.$file->getClientOriginalExtension(),
            'public'
        );

        LopGoliveVerification::updateOrCreate(
            ['lop_id' => $lop->id_lop],
            [
                'capture_uim_path' => $path,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]
        );

        ProjectActivityService::log([
            'project_id' => $lop->project_id,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'golive_verification_upload',
            'title' => 'Capture UIM Diverifikasi SDI',
            'description' => 'Tim SDI mengunggah capture UIM untuk LOP: '.$lop->lop_name,
        ]);

        /*
        |--------------------------------------------------------------------------
        | AUTO-ADVANCE status_progress: fi_ogp_golive (10) -> golive (11)
        |--------------------------------------------------------------------------
        | Gate sama dgn transisi lain: bukan PT2, belum drop/closed, tidak
        | sedang hold/drop, persis di sequence 10. Begitu capture UIM
        | tersimpan, LOP resmi Golive dan is_golive di-set true (dibaca
        | ProjectController's isAlreadyClosed check & tempat lain).
        */
        $programSap = strtoupper($lop->program_sap ?? '');
        $isPt2 = str_contains($programSap, 'PT2') || str_contains($programSap, 'PT-2') || str_contains($programSap, 'PT 2');
        $isAlreadyClosed = in_array($lop->status_progress, ['drop', 'golive'], true) || (bool) $lop->is_golive;
        $currentStage = $lop->stage;
        $currentSequence = $currentStage?->sequence;
        $isPausedOrDropped = (bool) ($currentStage?->is_pause_type || $currentStage?->is_terminal);

        if (! $isPt2 && ! $isAlreadyClosed && $currentSequence === 10 && ! $isPausedOrDropped) {
            $lop->update([
                'status_progress' => 'golive',
                'is_golive' => true,
                'golive_at' => now(),
            ]);

            ProjectActivityService::log([
                'project_id' => $lop->project_id,
                'lop_id' => $lop->id_lop,
                'activity_type' => 'lop_golive',
                'title' => 'LOP Golive',
                'description' => 'LOP resmi Golive setelah verifikasi capture UIM oleh SDI.',
                'status_before' => 'fi_ogp_golive',
                'status_after' => 'golive',
            ]);
        }

        return redirect()->route('sdi.golive.index')->with('success', 'LOP berhasil diverifikasi dan di-Golive-kan.');
    }
}
