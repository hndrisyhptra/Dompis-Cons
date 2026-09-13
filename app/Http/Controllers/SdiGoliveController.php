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
        // Revisi (permintaan user): tampilan Approval Golive PT 3
        // disamakan dgn PT 2 -- filter tab Semua/Waiting Approval/Sudah
        // Go-Live. Query dasar sekarang mencakup fi_ogp_golive DAN golive
        // (non-PT2) supaya "Semua" & "Sudah Go-Live" tidak kosong (dulu
        // cuma nampilin fi_ogp_golive, LOP yg sudah golive otomatis
        // hilang dari tabel ini).
        $query = Lop::with(['project', 'goliveSubmission', 'goliveVerification'])
            ->whereIn('status_progress', ['fi_ogp_golive', 'golive'])
            ->whereRaw("UPPER(COALESCE(program_sap, '')) NOT LIKE '%PT2%'")
            ->whereRaw("UPPER(COALESCE(program_sap, '')) NOT LIKE '%PT-2%'")
            ->whereRaw("UPPER(COALESCE(program_sap, '')) NOT LIKE '%PT 2%'");

        if ($request->filled('status_filter')) {
            if ($request->status_filter === 'pending') {
                $query->where('status_progress', 'fi_ogp_golive');
            } elseif ($request->status_filter === 'approved') {
                $query->where(function ($q) {
                    $q->where('status_progress', 'golive')->orWhere('is_golive', 1);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lop_name', 'like', '%'.$search.'%')
                    ->orWhere('id_ihld', 'like', '%'.$search.'%')
                    ->orWhere('sto', 'like', '%'.$search.'%');
            });
        }

        // Revisi (permintaan user): kartu ringkasan (Total LOP / Waiting
        // Approval / Jumlah LOP Golive) utk menu Approval Golive PT
        // 3/Reguler. Cakupannya SENGAJA lebih luas dari $query di atas
        // ($query cuma nampilin yg masih fi_ogp_golive -- LOP yg sudah
        // golive otomatis hilang dari tabel) -- kartu di sini menghitung
        // SEMUA LOP yg pernah masuk antrean Golive (fi_ogp_golive ATAU
        // golive), non-PT2 (dari program_sap, konsisten dgn helper isPt2 di
        // ProjectController/SdiGoliveController::verify()), supaya "Total
        // LOP" tidak menyusut begitu LOP-nya sudah di-golive-kan.
        $summaryBase = Lop::whereIn('status_progress', ['fi_ogp_golive', 'golive'])
            ->whereRaw("UPPER(COALESCE(program_sap, '')) NOT LIKE '%PT2%'")
            ->whereRaw("UPPER(COALESCE(program_sap, '')) NOT LIKE '%PT-2%'")
            ->whereRaw("UPPER(COALESCE(program_sap, '')) NOT LIKE '%PT 2%'");

        $cards = [
            'total' => (clone $summaryBase)->count(),
            'waiting' => (clone $summaryBase)->where('status_progress', 'fi_ogp_golive')->count(),
            'golive' => (clone $summaryBase)->where(function ($q) {
                $q->where('status_progress', 'golive')->orWhere('is_golive', 1);
            })->count(),
        ];

        $lops = $query->latest('updated_at')->paginate($request->per_page ?? 10)->withQueryString();

        return view('sdi.golive.index', compact('lops', 'cards'));
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
            $lop->advanceStage('golive', auth()->id());
            $lop->update([
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
