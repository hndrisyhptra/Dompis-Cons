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

    //STEP 1 FORM SURVEY
    public function step1($project_id)
    {
        $project = Project::with(['pt2Survey', 'boqItems.designatorData'])->findOrFail($project_id);
        
        // Ambil master designator khusus material (Bisa disesuaikan filter query-nya)
        $designators = \App\Models\Designator::where('type', 'material')
                        ->orWhere('designator', 'LIKE', 'M-%')
                        ->orderBy('designator')
                        ->get();

        return view('teknisi.pt2.step1', compact('project', 'designators'));
    }

    public function storeStep1(Request $request, $project_id)
    {
        // 1. Ambil data project beserta relasi LOP-nya
        $project = Project::with('lop')->findOrFail($project_id);

        // Validasi Input Dasar
        $request->validate([
            'status_survey' => 'required|in:kendala,eksekusi',
            'kendala_note' => 'required_if:status_survey,kendala',
            'mode' => 'required_if:status_survey,eksekusi|in:A,B,C',
        ]);

        $hasKendala = ($request->status_survey === 'kendala') ? 1 : 0;
        $pmApproval = $hasKendala ? 'pending' : 'approved'; 

        // 2. Simpan Data Survey (Kode Anda sebelumnya)
        \App\Models\Pt2Survey::updateOrCreate(
            ['project_id' => $project->id_project],
            [
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
                
                // Simpan opsi spesifik mode B dan C ke detail_data JSON
                'detail_data' => $hasKendala ? null : json_encode([
                    'possible_add' => $request->possible_add,
                    'opsi_simple' => $request->opsi_simple,
                ]),
            ]
        );

        // 3. LOGIKA PENYIMPANAN BOQ MATERIAL (FIX LOP_ID, ITEM_NAME, & UNIT)
        if (!$hasKendala && $request->has('materials')) {
            
            // Hapus material lama untuk project ini agar tidak duplikat saat edit/update
            \App\Models\BoqItem::where('project_id', $project->id_project)->delete();

            $materials = $request->materials;
            $qtys = $request->qty;

            foreach ($materials as $index => $designator_id) {
                if (!empty($designator_id) && !empty($qtys[$index])) {
                    
                    // Ambil data master designator
                    $master = \App\Models\Designator::where('id_designator', $designator_id)->first();

                    if ($master) {
                        \App\Models\BoqItem::create([
                            'project_id' => $project->id_project,
                            'lop_id' => $project->lop_id ?? optional($project->lop)->id_lop,
                            'designator_id' => $designator_id,
                            
                            // Masukkan nama item, kode, dan UNIT dari master data
                            'designator' => $master->designator, 
                            'item_name' => $master->item_name, 
                            'unit' => $master->unit, // <--- TAMBAHKAN BARIS INI
                            
                            'quantity_plan' => $qtys[$index],
                            // 'quantity_actual' => $qtys[$index],
                        ]);
                    }
                }
            }
        } elseif ($hasKendala) {
            \App\Models\BoqItem::where('project_id', $project->id_project)->delete();
        }

        // 4. Redirect Lanjutan
        if ($hasKendala) {
             $project->update(['status_project' => 'pending_pm']);
             return redirect()->route('teknisi.pt2.index')->with('warning', 'Survey terkendala dilaporkan. Menunggu Approval PM.');
        }

        return redirect()->route('teknisi.pt2.step1Eviden', $project->id_project)
                         ->with('success', 'Data Survey & BOQ Material berhasil disimpan! Silakan upload eviden.');
    }

    //STEP 1 EVIDEN SURVEY
    public function step1Eviden($project_id)
    {
        $project = Project::with('pt2Survey')->findOrFail($project_id);
        $survey = $project->pt2Survey;

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

        // AMBIL EVIDEN YANG SUDAH DIUPLOAD (Fase 'persiapan')
        $existingEvidences = \App\Models\Evidence::where('project_id', $project_id)
            ->where('stage', 'persiapan')
            ->get()
            ->groupBy('evidence_type'); // Kelompokkan berdasarkan jenis/key nya

        return view('teknisi.pt2.step1_eviden', compact('project', 'mode', 'requiredEvidences', 'existingEvidences'));
    }

    // TAMBAHKAN FUNGSI BARU INI UNTUK MENGHAPUS FOTO
    public function deleteEvidence($id)
    {
        // Sesuaikan 'id_evidence' jika primary key Anda berbeda, atau pakai 'id'
        $evidence = \App\Models\Evidence::findOrFail($id); 
        
        // Hapus file fisik dari storage
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($evidence->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($evidence->file_path);
        }
        
        // Hapus dari database
        $evidence->delete();

        return back()->with('success', 'Foto eviden berhasil dihapus.');
    }

    public function storeStep1Eviden(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

        // 1. Validasi
        $request->validate([
            'evidences' => 'required|array',
            'evidences.*.*' => 'image|mimes:jpeg,jpg,png|max:5120', 
        ]);

        $stage = 'persiapan'; // stage survey menjadi 'persiapan' karena sesuai database Anda

        // 2. Ambil semua file dari array 'evidences' (Bypass kelemahan hasFile Laravel)
        $evidences = $request->file('evidences');

        // 3. Cek apakah array evidences tidak kosong
        if (!empty($evidences)) {
            
            // Looping untuk menyimpan setiap kategori dan foto ke tabel evidences
            foreach ($evidences as $type => $files) {
                foreach ($files as $file) {
                    
                    // Pastikan file tidak corrupt saat di-upload
                    if ($file->isValid()) {
                        // Generate nama file unik
                        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                        
                        // Simpan fisik file ke folder storage/app/public/evidences/{project_id}
                        $path = $file->storeAs('evidences/' . $project->id_project, $filename, 'public');

                        // Simpan data file ke database
                        \App\Models\Evidence::create([
                            'project_id' => $project->id_project,
                            'stage' => $stage,
                            'evidence_type' => $type, // Misal: power_in, base_tray_feeder, dll
                            'file_path' => $path,
                            'file_name' => $filename,
                            'status' => 'pending', 
                            'uploaded_by' => Auth::user()->id_user,
                        ]);
                    }
                }
            }
            
            // Redirect Lanjut ke Step 2 (Eviden Progress Instalasi)
            return redirect()->route('teknisi.pt2.step2Eviden', $project->id_project)
                             ->with('success', 'Eviden Survey berhasil disimpan! Lanjut Step 2.');
        }

        // Jika sampai di sini, berarti array kosong / file tidak terkirim
        return back()->with('error', 'Gagal mengupload eviden. Pastikan foto sudah dipilih.');
    }

    //STEP 2 EVIDEN MATERIAL dan PROGRESS INSTALASI
    public function step2Eviden($project_id)
    {
        $project = Project::with('pt2Survey')->findOrFail($project_id);
        $survey = $project->pt2Survey;

        if (!$survey) {
            return redirect()->route('teknisi.pt2.inbox')->with('error', 'Survey belum dilakukan.');
        }

        $mode = $survey->mode;
        
        // RULE STEP 2: HANYA MATERIAL DAN PROGRESS INSTALASI
        $requiredEvidences = [
            'material' => 'Foto Material / Barang Tiba',
            'progress_instalasi' => 'Foto Progress Instalasi',
        ];

        // AMBIL EVIDEN YANG SUDAH DIUPLOAD (Fase 'instalasi')
        $existingEvidences = \App\Models\Evidence::where('project_id', $project_id)
            ->where('stage', 'instalasi') // Stage untuk Step 2 adalah instalasi
            ->get()
            ->groupBy('evidence_type');

        return view('teknisi.pt2.step2_eviden', compact('project', 'mode', 'requiredEvidences', 'existingEvidences'));
    }

    public function storeStep2Eviden(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

        $request->validate([
            'evidences' => 'required|array',
            'evidences.*.*' => 'image|mimes:jpeg,jpg,png|max:5120', 
        ]);

        $stage = 'instalasi'; // Tahap Step 2

        $evidences = $request->file('evidences');

        if (!empty($evidences)) {
            foreach ($evidences as $type => $files) {
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('evidences/' . $project->id_project, $filename, 'public');

                        \App\Models\Evidence::create([
                            'project_id' => $project->id_project,
                            'stage' => $stage,
                            'evidence_type' => $type,
                            'file_path' => $path,
                            'file_name' => $filename,
                            'status' => 'pending', 
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }
            
            // Arahkan ke Step 3 jika sudah siap
            // return redirect()->route('teknisi.pt2.step3Eviden', $project->id_project)->with('success', 'Eviden Instalasi berhasil disimpan! Lanjut Step 3.');
            
            // Sementara kembalikan ke halaman yang sama dengan pesan sukses
            return back()->with('success', 'Eviden Instalasi berhasil diupload!');
        }

        return back()->with('error', 'Gagal mengupload eviden. Pastikan foto sudah dipilih.');
    }

    //STEP 3 EVIDEN FINISH INSTALASI
    public function step3Eviden($project_id)
    {
        $project = Project::with('pt2Survey')->findOrFail($project_id);
        $survey = $project->pt2Survey;

        if (!$survey) {
            return redirect()->route('teknisi.pt2.inbox')->with('error', 'Survey belum dilakukan.');
        }

        $mode = $survey->mode;
        
        $requiredEvidences = [];
        $targetPortCount = '0';

        // LOGIKA PENYESUAIAN JUMLAH PORT
        if ($mode === 'A') {
            $targetPortCount = '16';
            $requiredEvidences['redaman_port'] = 'Foto Redaman ODP (Wajib 16 Port)';
        } elseif ($mode === 'B') {
            $targetPortCount = '8';
            $requiredEvidences['redaman_port'] = 'Foto Redaman (Wajib 8 Port Terbaru)';
        } else {
            // Mode C
            $targetPortCount = '8_atau_16';
            $requiredEvidences['redaman_port'] = 'Foto Redaman (Pilih 8 atau 16 Port)';
        }

        // FOTO LAINNYA (TIDAK WAJIB)
        $optionalEvidences = [
            'foto_lainnya' => 'Foto Tambahan / Lainnya (Opsional)',
        ];

        $existingEvidences = \App\Models\Evidence::where('project_id', $project_id)
            ->where('stage', 'finishing') // Tahap Step 3 adalah finishing
            ->get()
            ->groupBy('evidence_type');

        return view('teknisi.pt2.step3_eviden', compact('project', 'mode', 'requiredEvidences', 'optionalEvidences', 'existingEvidences', 'targetPortCount'));
    }

    public function storeStep3Eviden(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

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
                        $path = $file->storeAs('evidences/' . $project->id_project, $filename, 'public');

                        \App\Models\Evidence::create([
                            'project_id' => $project->id_project,
                            'stage' => $stage,
                            'evidence_type' => $type,
                            'file_path' => $path,
                            'file_name' => $filename,
                            'status' => 'pending', 
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }
            // Arahkan ke Step 4 (URL sementara pakai url() jika routenya belum ada)
            return redirect(url('teknisi/pt2/survey/'.$project->id_project.'/step4'))->with('success', 'Eviden Redaman berhasil disimpan! Lanjut ke Step 4.');
        }

        return back()->with('error', 'Gagal mengupload eviden. Pastikan foto sudah dipilih.');
    }

    //STEP 4 BA DISMANTLE
    public function step4Eviden($project_id)
    {
        $project = Project::with('pt2Survey')->findOrFail($project_id);

        // Ambil data Dismantle yang sudah tersimpan
        $dismantles = \Illuminate\Support\Facades\DB::table('dismantles')->where('project_id', $project_id)->get();
        $odpData = $dismantles->where('category', 'ODP')->first();
        
        // Ambil data foto dismantle (Fase 'dismantle')
        $existingEvidences = \App\Models\Evidence::where('project_id', $project_id)
            ->where('stage', 'finishing')
            ->get()
            ->groupBy('evidence_type');

        return view('teknisi.pt2.step4_eviden', compact('project', 'dismantles', 'odpData', 'existingEvidences'));
    }

    public function storeStep4Eviden(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

        // 1. SIMPAN DATA TEKS (DISMANTLE ITEM & QTY)
        // Hapus data lama agar terganti dengan yang baru dari form (untuk fitur Edit/Update)
        \Illuminate\Support\Facades\DB::table('dismantles')->where('project_id', $project->id_project)->delete();

        // Simpan ODP jika dipilih
        if ($request->odp_item && $request->odp_item !== 'none') {
            \Illuminate\Support\Facades\DB::table('dismantles')->insert([
                'project_id' => $project->id_project,
                'category' => 'ODP',
                'item_name' => $request->odp_item,
                'qty' => $request->odp_qty ?? 1,
            ]);
        }

        // Simpan Splitter jika dicentang (Bisa multiple)
        if ($request->has('splitters')) {
            foreach ($request->splitters as $sp => $val) {
                $qty = $request->input('qty_splitter_' . $sp);
                if ($qty) {
                    \Illuminate\Support\Facades\DB::table('dismantles')->insert([
                        'project_id' => $project->id_project,
                        'category' => 'Splitter',
                        'item_name' => 'Splitter ' . str_replace('_', ':', $sp),
                        'qty' => $qty,
                    ]);
                }
            }
        }

        // 2. SIMPAN EVIDEN FOTO DISMANTLE (Auto-Compress dari JS)
        $evidences = $request->file('evidences');
        if (!empty($evidences)) {
            foreach ($evidences as $type => $files) {
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('evidences/' . $project->id_project, $filename, 'public');

                        \App\Models\Evidence::create([
                            'project_id' => $project->id_project,
                            'stage' => 'finishing',
                            'evidence_type' => $type, // cth: 'odp', 'splitter_1_2'
                            'file_path' => $path,
                            'file_name' => $filename,
                            'status' => 'pending', 
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }
        }

        return redirect(url('teknisi/pt2/survey/'.$project->id_project.'/step5'))->with('success', 'Data Dismantle & Eviden berhasil disimpan! Lanjut Step 5.');
    }

    public function step5($project_id)
    {
        $project = Project::findOrFail($project_id);
        
        // Ambil data mancore jika sebelumnya sudah pernah diisi (draft)
        $mancore = \Illuminate\Support\Facades\DB::table('pt2_mancores')->where('project_id', $project_id)->first();

        return view('teknisi.pt2.step5', compact('project', 'mancore'));
    }

    public function storeStep5(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

        // 1. Validasi Input
        $request->validate([
            'odp_label' => 'required|string|max:255',
            'odc_label' => 'required|string|max:255',
            'distribusi_core' => 'required|string|max:255',
            'feeder_core' => 'required|string|max:255',
        ]);

        // 2. Cek apakah data mancore sudah ada
        $existing = \Illuminate\Support\Facades\DB::table('pt2_mancores')->where('project_id', $project_id)->first();

        if ($existing) {
            // Update jika sudah ada
            \Illuminate\Support\Facades\DB::table('pt2_mancores')
                ->where('project_id', $project_id)
                ->update([
                    'odp_label' => $request->odp_label,
                    'odc_label' => $request->odc_label,
                    'distribusi_core' => $request->distribusi_core,
                    'feeder_core' => $request->feeder_core,
                    'updated_at' => now(),
                ]);
        } else {
            // Insert jika belum ada
            \Illuminate\Support\Facades\DB::table('pt2_mancores')->insert([
                'project_id' => $project_id,
                'odp_label' => $request->odp_label,
                'odc_label' => $request->odc_label,
                'distribusi_core' => $request->distribusi_core,
                'feeder_core' => $request->feeder_core,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Ubah status Project untuk dikirim ke Admin / PM
        // Sesuaikan 'waiting_ut' dengan status sistem Anda (misal: 'pending_pm' atau 'review_sdi')
        $project->update([
            'status' => 'waiting_ut' 
        ]);

        // 4. Redirect kembali ke Inbox dengan pesan sukses besar
        return redirect()->route('teknisi.pt2.inbox')
                         ->with('success', '🎉 Luar biasa! Data Project berhasil di-submit dan sedang menunggu Approval Admin.');
    }
}
