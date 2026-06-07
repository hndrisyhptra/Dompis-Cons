<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Notification;
use App\Models\Evidence;
use App\Models\EvidenceRevisionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class WaspangController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->user()->id_user;

        $assignedProjectIds = ProjectAssignment::where('waspang_id', $userId)
            ->pluck('project_id');

        $projects = Project::with(['boqItems', 'evidences'])
            ->whereIn('id_project', $assignedProjectIds)
            ->latest()
            ->get();

        $totalAssigned = $projects->count();

        $readyProjects = $projects->filter(function ($project) {
            return $this->isProjectReadyUt($project);
        });

        $ongoingProjects = $projects->filter(function ($project) {
            return !$this->isProjectReadyUt($project);
        });

        $activeProjectsCount = $ongoingProjects->count();
        $readyUtCount = $readyProjects->count();

        $latestProjects = $ongoingProjects->take(3);

       $preparation = 0;
        $installation = 0;
        $finish = 0;

        foreach ($projects as $project) {

            $evidences = $project->evidences ?? collect();
            $boqItems = $project->boqItems ?? collect();

            $persiapanApproved =
                $evidences->where('stage', 'persiapan')
                    ->where('evidence_type', 'barang_tiba')
                    ->where('status', 'approved')
                    ->count() > 0
                &&
                $evidences->where('stage', 'persiapan')
                    ->where('evidence_type', 'perizinan')
                    ->where('status', 'approved')
                    ->count() > 0;

            $boqTotal = $boqItems->count();

            $boqApproved = $boqItems->filter(function ($boq) use ($evidences) {
                return $evidences
                    ->where('stage', 'instalasi')
                    ->where('evidence_type', 'progress_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->where('status', 'approved')
                    ->count() > 0;
            })->count();

            $instalasiApproved =
                $boqTotal > 0 &&
                $boqApproved == $boqTotal;

            if ($this->isProjectReadyUt($project)) {
                $finish++;
            } elseif ($instalasiApproved) {
                $installation++;
            } elseif ($persiapanApproved) {
                $preparation++;
            } else {
                $preparation++;
            }
        }
        

        return view('waspang.dashboard', [
            'projects' => $projects,
            'totalAssigned' => $totalAssigned,
            'preparation' => $preparation,
            'installation' => $installation,
            'finish' => $finish,
            'latestProjects' => $latestProjects,
            'activeProjectsCount' => $activeProjectsCount,
            'readyUtCount' => $readyUtCount,
        ]);
    }


    //WASPANG MOBILE
    public function show($id)
    {
        return redirect()->route('waspang.projects.persiapan', $id);
    }

    //AKSI CEPAT WASPANG MOBILE
    public function inbox()
    {
        $search = request('search');

        $projects = Project::with([
                'lop',
                'evidences',
                'boqItems',
            ])
            ->whereHas('assignments', function ($q) {
                $q->where('waspang_id', auth()->user()->id_user);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('project_name', 'like', "%{$search}%")
                        ->orWhereHas('lop', function ($lop) use ($search) {
                            $lop->where('sto', 'like', "%{$search}%")
                                ->orWhere('branch', 'like', "%{$search}%")
                                ->orWhere('mitra_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('updated_at')
            ->get()
            ->filter(function ($project) {
                return !$this->isProjectReadyUt($project);
            });

        return view('waspang.inbox', compact('projects', 'search'));
    }

    public function readyUt()
    {
        $search = request('search');

        $projects = Project::with([
            'evidences',
            'boqItems',
        ])
        ->whereHas('assignments', function ($q) {
            $q->where('waspang_id', auth()->user()->id_user);
        })
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                ->orWhere('sto', 'like', "%{$search}%")
                ->orWhere('branch', 'like', "%{$search}%")
                ->orWhere('mitra_name', 'like', "%{$search}%");
            });
        })
        ->latest('updated_at')
        ->get()
        ->filter(function ($project) {
            return $this->isProjectReadyUt($project);
        });

        return view('waspang.ready-ut', compact('projects', 'search'));
    }

    private function isProjectReadyUt($project): bool
    {
        $evidences = $project->evidences ?? collect();
        $boqItems = $project->boqItems ?? collect();

        $persiapanDone =
            $evidences->where('stage', 'persiapan')
                ->where('evidence_type', 'barang_tiba')
                ->where('status', 'approved')
                ->count() > 0
            &&
            $evidences->where('stage', 'persiapan')
                ->where('evidence_type', 'perizinan')
                ->where('status', 'approved')
                ->count() > 0;

        $boqTotal = $boqItems->count();

        $boqApproved = $boqItems->filter(function ($boq) use ($evidences) {
            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->where('status', 'approved')
                ->count() > 0;
        })->count();

        $instalasiDone = $boqTotal > 0 && $boqApproved == $boqTotal;

        $pengukuranDone =
            $evidences->where('stage', 'pengukuran')
                ->where('evidence_type', 'otdr')
                ->where('status', 'approved')
                ->count() > 0
            &&
            $evidences->where('stage', 'pengukuran')
                ->where('evidence_type', 'opm')
                ->where('status', 'approved')
                ->count() > 0
            &&
            $evidences->where('stage', 'pengukuran')
                ->where('evidence_type', 'kedalaman')
                ->where('status', 'approved')
                ->count() > 0;

        $finishingDone =
        $evidences->where('stage', 'finishing')
            ->where('status', 'approved')
            ->count() > 0;

        return $persiapanDone &&
            $instalasiDone &&
            $pengukuranDone &&
            $finishingDone;
    }

    //WASPANG STAGE PERSIAPAN, INSTALASI, PENGUKURAN, FINISHING
    public function persiapan($id)
    {
        $userId = auth()->user()->id_user;

        $isAssigned = ProjectAssignment::where('project_id', $id)
            ->where('waspang_id', $userId)
            ->exists();

        abort_if(!$isAssigned, 403);

        $project = Project::with(['boqItems', 'evidences'])
            ->findOrFail($id);

        $barangTibaApproved = Evidence::where('project_id', $id)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'barang_tiba')
            ->where('status', 'approved')
            ->exists();

        $perizinanApproved = Evidence::where('project_id', $id)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'perizinan')
            ->where('status', 'approved')
            ->exists();

        $persiapanComplete = $barangTibaApproved && $perizinanApproved;

        $boqTotal = $project->boqItems->count();

        $boqUploaded = Evidence::where('project_id', $id)
            ->where('stage', 'instalasi')
            ->where('evidence_type', 'progress_boq')
            ->whereNotNull('boq_item_id')
            ->distinct('boq_item_id')
            ->count('boq_item_id');

        $boqApproved = Evidence::where('project_id', $id)
            ->where('stage', 'instalasi')
            ->where('evidence_type', 'progress_boq')
            ->where('status', 'approved')
            ->whereNotNull('boq_item_id')
            ->distinct('boq_item_id')
            ->count('boq_item_id');

        $instalasiComplete = $boqTotal > 0 && $boqApproved >= $boqTotal;

        $opmApproved = Evidence::where('project_id', $id)
            ->where('stage', 'pengukuran')
            ->where('evidence_type', 'opm')
            ->where('status', 'approved')
            ->exists();

        $otdrApproved = Evidence::where('project_id', $id)
            ->where('stage', 'pengukuran')
            ->where('evidence_type', 'otdr')
            ->where('status', 'approved')
            ->exists();

        $pengukuranComplete = $opmApproved && $otdrApproved;

        $finishingComplete = Evidence::where('project_id', $id)
            ->where('stage', 'finishing')
            ->where('status', 'approved')
            ->exists();

        if (!$persiapanComplete) {
            $currentStage = 'persiapan';
        } elseif (!$instalasiComplete) {
            $currentStage = 'instalasi';
        } elseif (!$pengukuranComplete) {
            $currentStage = 'pengukuran';
        } elseif (!$finishingComplete) {
            $currentStage = 'finishing';
        } else {
            $currentStage = 'selesai';
        }

        $barangTibaHistories = EvidenceRevisionHistory::where('project_id', $project->id_project)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'barang_tiba')
            ->latest()
            ->get();

        $perizinanHistories = EvidenceRevisionHistory::where('project_id', $project->id_project)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'perizinan')
            ->latest()
            ->get();

        return view('waspang.show', compact(
            'project',
            'barangTibaApproved',
            'perizinanApproved',
            'persiapanComplete',
            'boqTotal',
            'boqUploaded',
            'boqApproved',
            'instalasiComplete',
            'opmApproved',
            'otdrApproved',
            'pengukuranComplete',
            'finishingComplete',
            'currentStage',
            'barangTibaHistories',
            'perizinanHistories'
        ));
    
    }

    public function instalasi($id)
    {
        $project = $this->getAssignedProject($id);

        $materialBoqItems = $project->boqItems->filter(function ($boq) {
            return str_starts_with($boq->designator, 'M-');
        })->values();

        $persiapanComplete = $this->isPersiapanUploaded($id);

        abort_if(!$persiapanComplete, 403);

        $boqTotal = $materialBoqItems->count();

        $boqUploaded = 0;

        foreach ($materialBoqItems as $boq) {
            $hasEvidence = $project->evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->count() > 0;

            if ($hasEvidence) {
                $boqUploaded++;
            }
        }

        $instalasiComplete =
            $boqTotal > 0 &&
            $boqUploaded >= $boqTotal;

        $pengukuranComplete = $this->isPengukuranUploaded($id);
        $finishingComplete = $this->isFinishingUploaded($id);

        $revisionHistories = [];

        foreach ($materialBoqItems as $boq) {
            $revisionHistories[$boq->id_boq] = EvidenceRevisionHistory::where('project_id', $project->id_project)
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->whereHas('evidence', function ($q) use ($boq) {
                    $q->where('boq_item_id', $boq->id_boq);
                })
                ->latest()
                ->get();
        }

        $project->setRelation('boqItems', $materialBoqItems);

        return view('waspang.steps.instalasi', compact(
            'project',
            'boqTotal',
            'boqUploaded',
            'persiapanComplete',
            'instalasiComplete',
            'pengukuranComplete',
            'finishingComplete',
            'revisionHistories'
        ));
    }

    public function pengukuran($id)
    {
        $project = Project::with([
            'evidences',
            'boqItems',
        ])->findOrFail($id);

        $revisionHistories = [
            'otdr' => EvidenceRevisionHistory::where('project_id', $project->id_project)
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'otdr')
                ->latest()
                ->get(),

            'opm' => EvidenceRevisionHistory::where('project_id', $project->id_project)
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'opm')
                ->latest()
                ->get(),

            'kedalaman' => EvidenceRevisionHistory::where('project_id', $project->id_project)
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'kedalaman')
                ->latest()
                ->get(),
        ];

        return view('waspang.steps.pengukuran', compact(
            'project',
            'revisionHistories'
            ));
    }

    public function finishing($id)
    {
        $project = Project::with([
            'evidences',
            'boqItems',
        ])->findOrFail($id);

        return view('waspang.steps.finishing', compact('project'));
    }

    //WASPANG STAGE HELPER PRIVATE
    private function getAssignedProject($id)
    {
        $userId = auth()->user()->id_user;

        $isAssigned = ProjectAssignment::where('project_id', $id)
            ->where('waspang_id', $userId)
            ->exists();

        abort_if(!$isAssigned, 403);

        return Project::with(['boqItems', 'evidences'])
            ->findOrFail($id);
    }

    //HELPER
    private function isPersiapanComplete($projectId)
    {
        $barangTibaUploaded = Evidence::where('project_id', $projectId)
        ->where('stage', 'persiapan')
        ->where('evidence_type', 'barang_tiba')
        ->exists();

        $perizinanUploaded = Evidence::where('project_id', $projectId)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'perizinan')
            ->exists();

        return $barangTibaUploaded && $perizinanUploaded;
    }

    private function isPersiapanUploaded($projectId)
    {
        $barangTibaUploaded = Evidence::where('project_id', $projectId)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'barang_tiba')
            ->exists();

        $perizinanUploaded = Evidence::where('project_id', $projectId)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'perizinan')
            ->exists();

        return $barangTibaUploaded && $perizinanUploaded;
    }

    private function isInstalasiUploaded($project)
    {
        $boqTotal = $project->boqItems->count();

        if ($boqTotal == 0) {
            return false;
        }

        $uploaded = 0;

        foreach ($project->boqItems as $boq) {

            $exists = Evidence::where('project_id', $project->id_project)
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->exists();

            if ($exists) {
                $uploaded++;
            }
        }

        return $uploaded >= $boqTotal;
    }

    private function isPengukuranUploaded($projectId)
    {
        $otdr = Evidence::where('project_id', $projectId)
            ->where('stage', 'pengukuran')
            ->where('evidence_type', 'otdr')
            ->exists();

        $opm = Evidence::where('project_id', $projectId)
            ->where('stage', 'pengukuran')
            ->where('evidence_type', 'opm')
            ->exists();

        $kedalaman = Evidence::where('project_id', $projectId)
            ->where('stage', 'pengukuran')
            ->where('evidence_type', 'kedalaman')
            ->exists();

        return $otdr && $opm && $kedalaman;
    }

    private function isFinishingUploaded($projectId)
    {
        return Evidence::where('project_id', $projectId)
            ->where('stage', 'finishing')
            ->exists();
    }

    private function isPersiapanApproved($project)
    {
        $evidences = $project->evidences ?? collect();

        return $evidences->where('stage', 'persiapan')
                ->where('evidence_type', 'barang_tiba')
                ->where('status', 'approved')
                ->count() > 0
            &&
            $evidences->where('stage', 'persiapan')
                ->where('evidence_type', 'perizinan')
                ->where('status', 'approved')
                ->count() > 0;
    }

    private function isInstalasiApproved($project)
    {
        $evidences = $project->evidences ?? collect();
        $boqItems = $project->boqItems ?? collect();

        $boqTotal = $boqItems->count();

        if ($boqTotal == 0) {
            return false;
        }

        $boqApproved = $boqItems->filter(function ($boq) use ($evidences) {
            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->where('status', 'approved')
                ->count() > 0;
        })->count();

        return $boqApproved == $boqTotal;
    }

    //UPLOAD FOTO di FOLDER 
    public function uploadEvidence(Request $request, $id)
    {
        $project = $this->getAssignedProject($id);

        $request->validate([
            'stage' => 'required|in:persiapan,instalasi,pengukuran,finishing',
            'evidence_type' => 'required|string|max:100',
            'photos' => 'required|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'description' => 'nullable|string',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);

        //FOLDER BERDASARKAN ID PROJECT
        $projectFolder = 'project-' . $project->id_project;
        $stage = $request->stage;
        $type = $request->evidence_type;

        foreach ($request->file('photos') as $photo) {
            $filename = now()->format('Ymd_His') . '_' . uniqid() . '.jpg';

            $path = $photo->storeAs(
                "evidences/{$projectFolder}/{$stage}/{$type}",
                $filename,
                'public'
            );

            Evidence::create([
                'project_id' => $project->id_project,
                'boq_item_id' => $request->boq_item_id,
                'uploaded_by' => auth()->user()->id_user,
                'stage' => $stage,
                'evidence_type' => $type,
                'file_path' => $path,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'description' => $request->description,
                'status' => 'pending',
            ]);
        }

        if ($request->boq_item_id && $request->quantity_actual !== null) {
            \App\Models\BoqItem::where('id_boq', $request->boq_item_id)
                ->update([
                    'quantity_actual' => $request->quantity_actual
                ]);
        }

        return back()->with('success', 'Eviden berhasil diupload dan menunggu approval');
    }

    public function deleteEvidence($id)
    {
        $evidence = Evidence::findOrFail($id);

        if ($evidence->uploaded_by != auth()->user()->id_user) {
            abort(403);
        }

        if ($evidence->status === 'approved') {
            return back()->with('error', 'Eviden yang sudah approved tidak bisa dihapus');
        }

        Storage::disk('public')->delete($evidence->file_path);

        $evidence->delete();

        return back()->with('success', 'Eviden berhasil dihapus');
    }

    //NOTIFICATION
    public function notifications()
    {
        $notifications = Notification::where('user_id', auth()->user()->id_user)
            ->latest()
            ->limit(15)
            ->get();

        return view('waspang.notifications', compact('notifications'));
    }

    public function clearNotifications()
    {
        Notification::where('user_id', auth()->user()->id_user)->delete();

        return back()->with('success', 'Semua notifikasi berhasil dibersihkan');
    }

    public function deleteNotification($id)
    {
        Notification::where('id_notification', $id)
            ->where('user_id', auth()->user()->id_user)
            ->delete();

        return back()->with('success', 'Notifikasi berhasil di besihkan');
    }

    public function profile()
    {
        return view('waspang.profile');
    }


}