<?php

namespace App\Http\Controllers;

use App\Models\Pt2Project;
use App\Models\Pt2Lop;
use App\Models\Pt2Assignment;
use App\Models\SurveyPt2;
use App\Models\Pt2Evidence;
use App\Models\DismantlePt2;
use App\Models\MancorePt2;
use App\Models\BoqItem; // Atau Pt2BoqItem jika Anda menggunakannya, di sini kita gunakan model terkait
use App\Models\Designator;
use App\Models\User;
use App\Services\ProjectActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeknisiPt2Controller extends Controller
{
   public function index()
    {
        $user = Auth::user();

        // Ambil semua LOP PT2 yang di-assign ke teknisi
        $assignedLops = Pt2Lop::with([
            'project',
            'evidences',
            'boqItems.designatorData',
            'surveys'
        ])
        ->whereHas('assignment', function ($query) use ($user) {
            $query->where('teknisi_id', $user->id_user);
        })
        ->get();

        /*
        |--------------------------------------------------------------------------
        | STATISTIK LOP
        |--------------------------------------------------------------------------
        */

        $statOnProgress = 0;
        $statWaitingApproval = 0;
        $statFinish = 0;

        foreach ($assignedLops as $lop) {

            /*
            |--------------------------------------------------------------------------
            | PRIORITAS UTAMA:
            | is_golive pada LOP
            |--------------------------------------------------------------------------
            */

            if ((int) $lop->is_golive === 1) {

                // Project/LOP sudah Go Live
                $statFinish++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Jika belum Go Live, cek apakah sedang Review
            |--------------------------------------------------------------------------
            */

            $survey = $lop->surveys->first();

            /*
            |--------------------------------------------------------------------------
            | Mancore sudah dibuat = sudah masuk proses review
            |--------------------------------------------------------------------------
            */

            $mancore = MancorePt2::where(
                'pt2_lop_id',
                $lop->id_pt2_lop
            )->first();

            if (
                $mancore ||
                ($survey && $survey->pm_approval_status === 'approved')
            ) {

                $statWaitingApproval++;

            } else {

                $statOnProgress++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $totalAssigned = $assignedLops->count();

        $activeProjectsCount =
            $statOnProgress + $statWaitingApproval;

        /*
        |--------------------------------------------------------------------------
        | PROGRESS PENYELESAIAN
        |--------------------------------------------------------------------------
        */

        $progressDone = $statFinish;

        $progressPercent = $totalAssigned > 0
            ? round(($progressDone / $totalAssigned) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | UPDATE EVIDENCE TERAKHIR
        |--------------------------------------------------------------------------
        */

        $lastUpdate = optional(
            $assignedLops
                ->flatMap(function ($lop) {
                    return $lop->evidences ?? collect();
                })
                ->sortByDesc('updated_at')
                ->first()
        )->updated_at;

        return view('teknisi.dashboard', compact(
            'assignedLops',
            'totalAssigned',
            'activeProjectsCount',
            'statOnProgress',
            'statWaitingApproval',
            'statFinish',
            'progressDone',
            'progressPercent',
            'lastUpdate'
        ));
    }

    public function inbox(Request $request)
    {
        $user = Auth::user();

        $query = Pt2Lop::with([
            'project',
            'surveys',
            'assignment'
        ])
        ->whereHas('assignment', function ($query) use ($user) {
            $query->where('teknisi_id', $user->id_user);
        });

        /*
        |--------------------------------------------------------------------------
        | FILTER LIST SELESAI
        |--------------------------------------------------------------------------
        */

        if ($request->get('status') === 'finish') {

            $query->where('is_golive', 1);

        } else {

            // Inbox normal hanya menampilkan
            // LOP yang belum Go Live
            $query->where('is_golive', 0);
        }

        $assignedLops = $query
            ->orderBy('created_at', 'desc')
            ->get();

        return view(
            'teknisi.pt2.inbox',
            compact('assignedLops')
        );
    }

    public function step1($lop_id)
    {
        $lop = Pt2Lop::with(['surveys', 'boqItems.designatorData', 'project'])->findOrFail($lop_id);
        
        $designators = Designator::where('type', 'material')
                        ->orWhere('designator', 'LIKE', 'M-%')
                        ->orderBy('designator')
                        ->get();

        return view('teknisi.pt2.step1', compact('lop', 'designators'));
    }

    public function storeStep1(Request $request, $lop_id)
    {
        $lop = Pt2Lop::with('project')->findOrFail($lop_id);

        $request->validate([
            'status_survey' => 'required|in:kendala,eksekusi',
            'kendala_note' => 'required_if:status_survey,kendala',
            'mode' => 'required_if:status_survey,eksekusi|in:A,B,C',
        ]);

        $hasKendala = ($request->status_survey === 'kendala') ? 1 : 0;
        $pmApproval = $hasKendala ? 'pending' : 'approved'; 

        SurveyPt2::updateOrCreate(
            ['pt2_lop_id' => $lop->id_pt2_lop],
            [
                'pt2_project_id' => $lop->pt2_project_id,
                'has_kendala' => $hasKendala,
                'kendala_note' => $hasKendala ? $request->kendala_note : null,
                'pm_approval_status' => $pmApproval,
                'mode' => $hasKendala ? null : $request->mode,
                'sub_mode_a' => $hasKendala ? null : $request->sub_mode_a,
                'odp_name' => $hasKendala ? null : $request->odp_name,
                'distribusi' => $hasKendala ? null : $request->distribusi,
                'core_ex' => $hasKendala ? null : $request->core_ex,
                'power_out' => $hasKendala ? null : $request->power_out,
                'power_in_feeder' => $hasKendala ? null : $request->power_in_feeder,
                'tipe_kabel' => $hasKendala ? null : $request->tipe_kabel,
                'kesimpulan' => $hasKendala ? null : $request->kesimpulan,
                'detail_data' => $hasKendala ? null : json_encode([
                    'possible_add' => $request->possible_add,
                    'opsi_simple' => $request->opsi_simple,
                ]),
            ]
        );

        if (!$hasKendala && $request->has('materials')) {
            // Hapus BOQ lama khusus LOP ini, lalu masukkan yang baru
            \App\Models\Pt2BoqItem::where('pt2_lop_id', $lop->id_pt2_lop)->delete();

            $materials = $request->materials;
            $qtys = $request->qty;

            foreach ($materials as $index => $designator_id) {
                if (!empty($designator_id) && !empty($qtys[$index])) {
                    $master = Designator::where('id_designator', $designator_id)->first();
                    if ($master) {
                        \App\Models\Pt2BoqItem::create([
                            'pt2_project_id' => $lop->pt2_project_id,
                            'pt2_lop_id' => $lop->id_pt2_lop,
                            'designator_id' => $designator_id,
                            'designator' => $master->designator, 
                            'item_name' => $master->item_name, 
                            'unit' => $master->unit,
                            'quantity_plan' => $qtys[$index],
                        ]);
                    }
                }
            }
        } elseif ($hasKendala) {
            \App\Models\Pt2BoqItem::where('pt2_lop_id', $lop->id_pt2_lop)->delete();
        }

        if ($hasKendala) {
             return redirect()->route('teknisi.pt2.inbox')->with('warning', 'Survey terkendala dilaporkan. Menunggu Approval PM.');
        }

        return redirect()->route('teknisi.pt2.step1Eviden', $lop->id_pt2_lop)
                         ->with('success', 'Data Survey & BOQ Material berhasil disimpan! Silakan upload eviden.');
    }

    public function step1Eviden($lop_id)
    {
        $lop = Pt2Lop::with(['surveys', 'project'])->findOrFail($lop_id);
        $survey = $lop->surveys()->first();

        if (!$survey || $survey->has_kendala) {
            return redirect()->route('teknisi.pt2.inbox')->with('error', 'Project ini terkendala atau survey belum lengkap.');
        }

        $mode = $survey->mode;
        $requiredEvidences = [];

        if ($mode === 'A') {
            $requiredEvidences = ['power_in' => 'Foto Eviden Power IN', 'power_out' => 'Foto Eviden Power OUT'];
        } elseif ($mode === 'B') {
            $requiredEvidences = [
                'base_tray_feeder' => 'Foto Eviden Base Tray Feeder', 'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi',
                'power_in_feeder' => 'Foto Power IN Feeder', 'power_out_splitter' => 'Foto Power OUT Splitter Ex'
            ];
        } elseif ($mode === 'C') {
            $requiredEvidences = ['base_tray_feeder' => 'Foto Eviden Base Tray Feeder', 'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi'];
        }

        $existingEvidences = Pt2Evidence::where('pt2_lop_id', $lop_id)
            ->where('stage', 'persiapan')
            ->get()
            ->groupBy('evidence_type');

        $project = $lop->project; // Untuk kompatibilitas view
        return view('teknisi.pt2.step1_eviden', compact('lop', 'project', 'mode', 'requiredEvidences', 'existingEvidences'));
    }

    public function deleteEvidence($id)
    {
        $evidence = Pt2Evidence::findOrFail($id); 
        
        if (Storage::disk('public')->exists($evidence->file_path)) {
            Storage::disk('public')->delete($evidence->file_path);
        }
        
        $evidence->delete();

        return back()->with('success', 'Foto eviden berhasil dihapus.');
    }

    public function storeStep1Eviden(Request $request, $lop_id)
    {
        $lop = Pt2Lop::findOrFail($lop_id);

        $request->validate([
            'evidences' => 'required|array',
            'evidences.*.*' => 'image|mimes:jpeg,jpg,png|max:5120', 
        ]);

        $stage = 'persiapan';
        $evidences = $request->file('evidences');

        if (!empty($evidences)) {
            foreach ($evidences as $type => $files) {
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('evidences/pt2/' . $lop->id_pt2_lop, $filename, 'public');

                        Pt2Evidence::create([
                            'pt2_project_id' => $lop->pt2_project_id,
                            'pt2_lop_id' => $lop->id_pt2_lop,
                            'stage' => $stage,
                            'evidence_type' => $type,
                            'file_path' => $path,
                            'status' => 'pending', 
                            'uploaded_by' => Auth::user()->id_user,
                        ]);
                    }
                }
            }
            
            return redirect()->route('teknisi.pt2.step2Eviden', $lop->id_pt2_lop)
                             ->with('success', 'Eviden Survey berhasil disimpan! Lanjut Step 2.');
        }

        return back()->with('error', 'Gagal mengupload eviden. Pastikan foto sudah dipilih.');
    }

    public function replaceEvidence(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $evidence = Pt2Evidence::findOrFail($id);

        if ($evidence->file_path && Storage::disk('public')->exists($evidence->file_path)) {
            Storage::disk('public')->delete($evidence->file_path);
        }

        $file = $request->file('file');
        $originalExtension = strtolower($file->getClientOriginalExtension());
        $extension = $originalExtension ?: 'jpg';
        
        $filename = now()->format('Ymd_His') . '_replace_' . uniqid() . '.' . $extension;

        $path = $file->storeAs(
            'evidences/pt2/' . $evidence->pt2_lop_id,
            $filename,
            'public'
        );

        $evidence->file_path = $path;
        $evidence->status = 'pending';
        $evidence->review_note = null;
        $evidence->uploaded_by = Auth::user()->id_user ?? $evidence->uploaded_by;
        $evidence->save();

        return back()->with('success', 'Eviden berhasil diperbarui. Status kembali pending.');
    }

    public function step2Eviden($lop_id)
    {
        $lop = Pt2Lop::with(['surveys', 'project'])->findOrFail($lop_id);
        $survey = $lop->surveys()->first();

        if (!$survey) {
            return redirect()->route('teknisi.pt2.inbox')->with('error', 'Survey belum dilakukan.');
        }

        $mode = $survey->mode;
        
        $requiredEvidences = [
            'material' => 'Foto Material / Barang Tiba',
            'progress_instalasi' => 'Foto Progress Instalasi',
        ];

        $existingEvidences = Pt2Evidence::where('pt2_lop_id', $lop_id)
            ->where('stage', 'instalasi') 
            ->get()
            ->groupBy('evidence_type');

        $project = $lop->project;
        return view('teknisi.pt2.step2_eviden', compact('lop', 'project', 'mode', 'requiredEvidences', 'existingEvidences'));
    }

    public function storeStep2Eviden(Request $request, $lop_id)
    {
        $lop = Pt2Lop::findOrFail($lop_id);

        $request->validate([
            'evidences' => 'required|array',
            'evidences.*.*' => 'image|mimes:jpeg,jpg,png|max:5120', 
        ]);

        $stage = 'instalasi'; 
        $evidences = $request->file('evidences');

        if (!empty($evidences)) {
            foreach ($evidences as $type => $files) {
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('evidences/pt2/' . $lop->id_pt2_lop, $filename, 'public');

                        Pt2Evidence::create([
                            'pt2_project_id' => $lop->pt2_project_id,
                            'pt2_lop_id' => $lop->id_pt2_lop,
                            'stage' => $stage,
                            'evidence_type' => $type,
                            'file_path' => $path,
                            'status' => 'pending', 
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }
            
            return back()->with('success', 'Eviden Instalasi berhasil diupload!');
        }

        return back()->with('error', 'Gagal mengupload eviden. Pastikan foto sudah dipilih.');
    }

    public function step3Eviden($lop_id)
    {
        $lop = Pt2Lop::with(['surveys', 'project'])->findOrFail($lop_id);
        $survey = $lop->surveys()->first();

        if (!$survey) {
            return redirect()->route('teknisi.pt2.inbox')->with('error', 'Survey belum dilakukan.');
        }

        $mode = $survey->mode;
        
        $requiredEvidences = [];
        $targetPortCount = '0';

        if ($mode === 'A') {
            $targetPortCount = '16';
            $requiredEvidences['redaman_port'] = 'Foto Redaman ODP (Wajib 16 Port)';
        } elseif ($mode === 'B') {
            $targetPortCount = '8';
            $requiredEvidences['redaman_port'] = 'Foto Redaman (Wajib 8 Port Terbaru)';
        } else {
            $targetPortCount = '8_atau_16';
            $requiredEvidences['redaman_port'] = 'Foto Redaman (Pilih 8 atau 16 Port)';
        }

        $optionalEvidences = [
            'foto_lainnya' => 'Foto Tambahan / Lainnya (Opsional)',
        ];

        $existingEvidences = Pt2Evidence::where('pt2_lop_id', $lop_id)
            ->where('stage', 'finishing')
            ->get()
            ->groupBy('evidence_type');

        $project = $lop->project;
        return view('teknisi.pt2.step3_eviden', compact('lop', 'project', 'mode', 'requiredEvidences', 'optionalEvidences', 'existingEvidences', 'targetPortCount'));
    }

    public function storeStep3Eviden(Request $request, $lop_id)
    {
        $lop = Pt2Lop::findOrFail($lop_id);

        $request->validate([
            'evidences' => 'required|array',
            'evidences.*.*' => 'image|mimes:jpeg,jpg,png|max:5120', 
        ]);

        $stage = 'finishing'; 
        $evidences = $request->file('evidences');

        if (!empty($evidences)) {
            foreach ($evidences as $type => $files) {
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('evidences/pt2/' . $lop->id_pt2_lop, $filename, 'public');

                        Pt2Evidence::create([
                            'pt2_project_id' => $lop->pt2_project_id,
                            'pt2_lop_id' => $lop->id_pt2_lop,
                            'stage' => $stage,
                            'evidence_type' => $type,
                            'file_path' => $path,
                            'status' => 'pending', 
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }
            return redirect(url('teknisi/pt2/survey/'.$lop->id_pt2_lop.'/step4'))->with('success', 'Eviden Redaman berhasil disimpan! Lanjut ke Step 4.');
        }

        return back()->with('error', 'Gagal mengupload eviden. Pastikan foto sudah dipilih.');
    }

    public function step4Eviden($lop_id)
    {
        $lop = Pt2Lop::with('project')->findOrFail($lop_id);

        $dismantles = DismantlePt2::where('pt2_lop_id', $lop_id)->get();
        $odpData = $dismantles->where('category', 'ODP')->first();
        
        $existingEvidences = Pt2Evidence::where('pt2_lop_id', $lop_id)
            ->where('stage', 'finishing')
            ->get()
            ->groupBy('evidence_type');

        $project = $lop->project;
        return view('teknisi.pt2.step4_eviden', compact('lop', 'project', 'dismantles', 'odpData', 'existingEvidences'));
    }

    public function storeStep4Eviden(Request $request, $lop_id)
    {
        $lop = Pt2Lop::findOrFail($lop_id);

        DismantlePt2::where('pt2_lop_id', $lop->id_pt2_lop)->delete();

        if ($request->odp_item && $request->odp_item !== 'none') {
            DismantlePt2::create([
                'pt2_project_id' => $lop->pt2_project_id,
                'pt2_lop_id' => $lop->id_pt2_lop,
                'category' => 'ODP',
                'item_name' => $request->odp_item,
                'qty' => $request->odp_qty ?? 1,
            ]);
        }

        if ($request->has('splitters')) {
            foreach ($request->splitters as $sp => $val) {
                $qty = $request->input('qty_splitter_' . $sp);
                if ($qty) {
                    DismantlePt2::create([
                        'pt2_project_id' => $lop->pt2_project_id,
                        'pt2_lop_id' => $lop->id_pt2_lop,
                        'category' => 'Splitter',
                        'item_name' => 'Splitter ' . str_replace('_', ':', $sp),
                        'qty' => $qty,
                    ]);
                }
            }
        }

        $evidences = $request->file('evidences');
        if (!empty($evidences)) {
            foreach ($evidences as $type => $files) {
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('evidences/pt2/' . $lop->id_pt2_lop, $filename, 'public');

                        Pt2Evidence::create([
                            'pt2_project_id' => $lop->pt2_project_id,
                            'pt2_lop_id' => $lop->id_pt2_lop,
                            'stage' => 'finishing',
                            'evidence_type' => $type,
                            'file_path' => $path,
                            'status' => 'pending', 
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }
        }

        return redirect(url('teknisi/pt2/survey/'.$lop->id_pt2_lop.'/step5'))->with('success', 'Data Dismantle & Eviden berhasil disimpan! Lanjut Step 5.');
    }

    public function step5($lop_id)
    {
        $lop = Pt2Lop::with('project')->findOrFail($lop_id);
        
        $mancore = MancorePt2::where('pt2_lop_id', $lop_id)->first();
        $project = $lop->project;

        return view('teknisi.pt2.step5', compact('lop', 'project', 'mancore'));
    }

    public function storeStep5(Request $request, $lop_id)
    {
        $lop = Pt2Lop::findOrFail($lop_id);

        $request->validate([
            'odp_label' => 'required|string|max:255',
            'odc_label' => 'required|string|max:255',
            'distribusi_core' => 'required|string|max:255',
            'feeder_core' => 'required|string|max:255',
        ]);

        MancorePt2::updateOrCreate(
            ['pt2_lop_id' => $lop->id_pt2_lop],
            [
                'pt2_project_id' => $lop->pt2_project_id,
                'odp_label' => $request->odp_label,
                'odc_label' => $request->odc_label,
                'distribusi_core' => $request->distribusi_core,
                'feeder_core' => $request->feeder_core,
            ]
        );

        return redirect()->route('teknisi.pt2.inbox')
                         ->with('success', '🎉 Luar biasa! Data LOP PT 2 berhasil di-submit dan sedang menunggu Approval Admin.');
    }
}