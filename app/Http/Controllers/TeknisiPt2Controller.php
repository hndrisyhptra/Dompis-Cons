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

        // 3. LOGIKA PENYIMPANAN BOQ MATERIAL (FIX LOP_ID & ITEM_NAME)
        if (!$hasKendala && $request->has('materials')) {
            
            // Hapus material lama untuk project ini agar tidak duplikat saat edit/update
            \App\Models\BoqItem::where('project_id', $project->id_project)->delete();

            $materials = $request->materials;
            $qtys = $request->qty;

            foreach ($materials as $index => $designator_id) {
                if (!empty($designator_id) && !empty($qtys[$index])) {
                    
                    // Ambil data master designator (sesuaikan nama Model jika milik Anda bukan 'Designator')
                    // Jika primary key master designator Anda adalah id_designator:
                    $master = \App\Models\Designator::where('id_designator', $designator_id)->first();

                    if ($master) {
                        \App\Models\BoqItem::create([
                            'project_id' => $project->id_project,
                            'lop_id' => $project->lop_id ?? optional($project->lop)->id_lop,
                            'designator_id' => $designator_id,
                            
                            // Masukkan nama item dan kode designator dari master data
                            'designator' => $master->designator, 
                            'item_name' => $master->item_name, // <--- INI SOLUSI DARI ERROR ANDA
                            
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

    public function step1Eviden($project_id)
    {
        $project = Project::with('pt2Survey')->findOrFail($project_id);
        $survey = $project->pt2Survey;

        if (!$survey || $survey->has_kendala) {
            return redirect()->route('teknisi.pt2.inbox')->with('error', 'Project ini terkendala atau survey belum lengkap.');
        }

        $mode = $survey->mode;
        $requiredEvidences = [];

        // Aturan Wajib Eviden Berdasarkan Mode
        if ($mode === 'A') {
            $requiredEvidences = [
                'power_in' => 'Foto Eviden Power IN',
                'power_out' => 'Foto Eviden Power OUT',
            ];
        } elseif ($mode === 'B') {
            $requiredEvidences = [
                'base_tray_feeder' => 'Foto Eviden Base Tray Feeder',
                'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi',
                'power_in_feeder' => 'Foto Power IN Feeder',
                'power_out_splitter' => 'Foto Power OUT Splitter Ex',
            ];
        } elseif ($mode === 'C') {
            $requiredEvidences = [
                'base_tray_feeder' => 'Foto Eviden Base Tray Feeder',
                'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi',
            ];
        }

        return view('teknisi.pt2.step1_eviden', compact('project', 'mode', 'requiredEvidences'));
    }

    public function storeStep1Eviden(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);
        
        $request->validate([
            'evidences.*.*' => 'image|mimes:jpeg,png,jpg|max:5120'
        ]);

        if ($request->hasFile('evidences')) {
            foreach ($request->file('evidences') as $type => $files) {
                foreach ($files as $file) {
                    $path = $file->store('evidences/survey', 'public');
                    
                    \App\Models\Evidence::create([
                        'project_id' => $project->id_project,
                        'stage' => 'survey', 
                        'evidence_type' => $type,
                        'file_path' => $path,
                        'status' => 'pending', 
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }
        }

        // Jika berhasil, diarahkan ke Inbox (Nanti bisa diganti ke Step 2 jika viewnya sudah ada)
        return redirect()->route('teknisi.pt2.inbox')->with('success', 'Eviden Survey berhasil diunggah! Lanjut ke Step 2.');
    }
}