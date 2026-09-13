<?php

namespace App\Http\Controllers;

use App\Models\BoqItem;
use App\Models\BoqSurveyRound;
use App\Models\BoqSurveyRoundItem;
use App\Models\Designator;
use App\Models\Evidence;
use App\Models\EvidenceRevisionHistory;
use App\Models\KendalaCategory;
use App\Models\Lop;
use App\Models\LopKronologi;
use App\Models\LopMeasurementCheck;
use App\Models\Notification;
use App\Models\PermitCategory;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectAssignment;
use App\Models\ProjectIssue;
use App\Models\SiteSurvey;
use App\Models\User;
use App\Services\ProjectActivityService;
use App\Services\SurveyPreparationService;
use App\Services\TelegramWebhookEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class WaspangController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->user()->id_user;

        $assignedProjectIds = ProjectAssignment::where('waspang_id', $userId)
            ->pluck('project_id');

        $projects = Project::with(['boqItems.designatorData', 'evidences', 'lop.stage'])
            ->whereIn('id_project', $assignedProjectIds)
            ->latest()
            ->get();

        $totalAssigned = $projects->count();

        $readyProjects = $projects->filter(function ($project) {
            return $this->isProjectReadyUt($project);
        });

        $ongoingProjects = $projects->filter(function ($project) {
            return ! $this->isProjectReadyUt($project);
        });

        $activeProjectsCount = $ongoingProjects->count();
        $readyUtCount = $readyProjects->count();

        $progressDone = $readyUtCount;
        $progressTotal = $totalAssigned;

        $progressPercent = $progressTotal > 0
            ? round(($progressDone / $progressTotal) * 100)
            : 0;

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

            // Item material = designator berawalan "M-" (konvensi lama) ATAU
            // type = 'material' di tabel master designators (customer/import
            // yang tidak pakai prefix M-/J-, mis. Konstruksi Eksternal) --
            // kalau cuma cek prefix, item material tanpa prefix "M-" akan
            // hilang total dari progress instalasi meski BOQ-nya valid.
            // quantity_plan !== null WAJIB: item tambahan hasil BOQ Survey
            // (addSurveyBoqItem(), tidak punya Plan) bukan bagian checklist
            // progress Instalasi -- lihat ANALISA_REFACTOR_PERSIAPAN.md bag. AC.
            $materialBoqItems = $boqItems->filter(function ($boq) {
                return ($boq->quantity_plan !== null)
                    && (str_starts_with($boq->designator, 'M-')
                        || optional($boq->designatorData)->type === 'material');
            });

            $boqTotal = $materialBoqItems->count();

            $boqApproved = $materialBoqItems->filter(function ($boq) use ($evidences) {
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
            'progressDone' => $progressDone,
            'progressTotal' => $progressTotal,
            'progressPercent' => $progressPercent,
        ]);
    }

    // WASPANG MOBILE
    public function show($id)
    {
        return redirect()->route('waspang.projects.persiapan', $id);
    }

    // AKSI CEPAT WASPANG MOBILE
    public function inbox()
    {
        $search = request('search');

        // Permintaan user: toggle filter Active/Complete di atas search
        // (sebelumnya inbox() SELALU exclude LOP yang sudah "Ready UT",
        // versi lengkapnya cuma bisa dilihat lewat menu terpisah
        // readyUt()/waspang.ready-ut -- sekarang toggle di halaman yang
        // SAMA, "Active" = perilaku lama (default, exclude ready UT),
        // "Complete" = pakai gate isProjectReadyUt() yang SAMA persis dgn
        // readyUt() supaya konsisten 1 definisi "selesai").
        $filter = request('filter', 'active') === 'complete' ? 'complete' : 'active';

        $projects = Project::with([
            'lop.stage',
            'evidences',
            'boqItems',
            'issues',
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
            ->filter(function ($project) use ($filter) {
                $isReadyUt = $this->isProjectReadyUt($project);

                return $filter === 'complete' ? $isReadyUt : ! $isReadyUt;
            });

        return view('waspang.inbox', compact('projects', 'search', 'filter'));
    }

    public function readyUt()
    {
        $search = request('search');

        $projects = Project::with([
            'evidences',
            'boqItems',
            'lop.stage',
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

        // Flow baru (Inisiasi/Survey/Perizinan/Material
        // Delivery) tidak lagi menulis eviden stage='persiapan' -- lihat
        // catatan sequence-based di Project::progressSummary(). Tanpa fallback
        // ini, LOP flow baru TIDAK PERNAH dianggap persiapanDone sehingga
        // TIDAK PERNAH masuk daftar Ready UT walau sudah benar2 selesai.
        $seq = $project->lop?->stage?->sequence;

        if ($seq !== null && $seq > 6) {
            $persiapanDone = true;
        } else {
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
        }

        // HANYA MATERIAL -- lihat catatan di dashboard() soal kenapa dicek
        // dua-duanya (prefix "M-" ATAU type master designator = 'material').
        // quantity_plan !== null WAJIB (lihat bag. AC): item tambahan BOQ
        // Survey bukan bagian checklist progress Instalasi.
        $materialBoqItems = $boqItems->filter(function ($boq) {
            return ($boq->quantity_plan !== null)
                && (str_starts_with($boq->designator, 'M-')
                    || optional($boq->designatorData)->type === 'material');
        });

        $boqTotal = $materialBoqItems->count();

        $boqApproved = $materialBoqItems->filter(function ($boq) use ($evidences) {
            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->where('status', 'approved')
                ->count() > 0;
        })->count();

        $instalasiDone = $boqTotal > 0 && $boqApproved >= $boqTotal;

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
            // $pengukuranDone &&
            $finishingDone;
    }

    // WASPANG STAGE PERSIAPAN, INSTALASI, PENGUKURAN, FINISHING
    /**
     * Persiapan terdiri dari Inisiasi, Survey, Perizinan, dan Material
     * Delivery. Kode DRM lama hanya diperlakukan sebagai alias Perizinan agar
     * LOP historis tetap dapat melanjutkan proses tanpa kehilangan data.
     */
    public function persiapan($id)
    {
        $project = $this->getAssignedProject($id);
        $lop = $this->getSingleSurveyLop($project);
        $project->setRelation('lop', $lop);

        $summary = $project->progressSummary();
        $seq = $summary['effectiveStageSequence'];
        $stageCode = $summary['effectiveStageCode'];

        // Kompatibilitas data sebelum DRM dihapus dari alur aktif. Jangan
        // mengubah data pada GET; LOP lama akan dinormalisasi saat aksi
        // Perizinan selesai dijalankan.
        if ($stageCode === 'drm') {
            $stageCode = 'perizinan';
            $seq = 4;
            $summary['effectiveStageCode'] = 'perizinan';
            $summary['effectiveStageSequence'] = 4;
            $summary['effectiveStageLabel'] = 'Perizinan';
            $summary['effectiveStageColor'] = 'amber';
            $summary['effectivePhaseGroup'] = 'persiapan';
            $summary['progress'] = 30;
        }

        // Status "done"/"active" mengikuti status_progress LOP sebagai
        // sumber tunggal alur kerja.
        $step = [
            'inisiasi' => [
                'done' => $seq !== null && $seq > 1,
                'active' => $stageCode === 'inisiasi',
            ],
            'survey' => [
                'done' => $seq !== null && $seq > 2,
                'active' => $stageCode === 'survey',
            ],
            'perizinan' => [
                'done' => $seq !== null && $seq > 4,
                'active' => $stageCode === 'perizinan',
            ],
            'material_delivery' => [
                'done' => $seq !== null && $seq > 5,
                'active' => $stageCode === 'material_delivery',
            ],
        ];

        $evidences = $project->evidences ?? collect();

        $perizinanEvidences = $evidences->where('stage', 'perizinan')
            ->where('evidence_type', '!=', 'ba_kp')
            ->sortByDesc('created_at')->values();
        $baKpEvidences = $evidences->where('stage', 'perizinan')
            ->where('evidence_type', 'ba_kp')
            ->sortByDesc('created_at')->values();
        $materialDeliveryEvidences = $evidences->where('stage', 'material_delivery')
            ->sortByDesc('created_at')->values();

        $boqItems = ($project->boqItems ?? collect())
            ->where('lop_id', $lop->id_lop)
            ->sortByDesc('id_boq')
            ->values();
        $surveyPreparation = app(SurveyPreparationService::class);
        $surveyBoqGroups = $surveyPreparation->groupBoqItems($boqItems);

        $surveyMapVersions = $this->surveyMapVersions($project);
        $currentSurveyMap = $surveyMapVersions->last();
        $surveyMapConfirmed = $currentSurveyMap
            ? $this->isSurveyMapConfirmed($project, $lop, $currentSurveyMap['key'])
            : false;

        $surveyDraft = ProjectActivityLog::query()
            ->where('project_id', $project->id_project)
            ->where('lop_id', $lop->id_lop)
            ->where('activity_type', 'survey_boq_draft_saved')
            ->latest('id_project_activity')
            ->first();
        $surveyDraftVolumes = collect($surveyDraft?->meta['volumes'] ?? []);

        // Daftar master designator utk picker TomSelect "+ Tambah Item BOQ"
        // (Mode Survey manual) -- ikut pola PT2 (App\Http\Controllers\
        // TeknisiPt2Controller::step1()) tapi pakai TomSelect (sudah dipakai
        // di admin/projects, lihat layouts/waspang.blade.php) drpd filter-list
        // custom, supaya tidak reinvent & tetap 1 keluarga UX dgn admin.
        $designators = Designator::forCustomer($project->customer_id)
            ->orderBy('designator')
            ->get(['id_designator', 'customer_id', 'designator', 'item_name', 'unit', 'type', 'pair_code']);
        $additionalDesignatorOptions = $surveyPreparation->groupDesignatorOptions($designators, $surveyBoqGroups);

        $kronologis = $lop->kronologis()->with('creator')->get();

        $permitCategories = PermitCategory::active()->get();
        $kendalaCategories = KendalaCategory::active()->get();

        // Re Survey (lihat startReSurvey()): tombol tampil begitu ada
        // minimal 1 ronde selesai & tidak ada ronde lain yg masih berjalan
        // / menunggu Approval Redesign -- berlaku dari tahap manapun
        // setelah Survey pertama tuntas, tidak dibatasi hanya saat aktif.
        // ensureBaselineSurveyRound() backfill round 1 utk LOP lama yg
        // sudah lewat Survey SEBELUM fitur ini ada (lihat catatan di sana).
        $this->ensureBaselineSurveyRound($lop);
        $surveyRounds = $lop->surveyRounds()->with('items')->get();
        $canStartReSurvey = $surveyRounds->where('status', 'completed')->isNotEmpty()
            && $surveyRounds->where('status', 'in_progress')->isEmpty()
            && ! $lop->survey_redesign_required;

        return view('waspang.show', compact(
            'project',
            'lop',
            'summary',
            'seq',
            'stageCode',
            'step',
            'perizinanEvidences',
            'baKpEvidences',
            'materialDeliveryEvidences',
            'boqItems',
            'designators',
            'surveyBoqGroups',
            'surveyMapVersions',
            'currentSurveyMap',
            'surveyMapConfirmed',
            'surveyDraftVolumes',
            'additionalDesignatorOptions',
            'kronologis',
            'permitCategories',
            'kendalaCategories',
            'surveyRounds',
            'canStartReSurvey'
        ));
    }

    /**
     * STEP 2 -- Persiapan Instalasi (revisi stepper: sebelumnya sequence 6
     * ini murni pass-through, sekarang jadi HALAMAN SENDIRI dgn 2 kartu
     * eviden -- Barang Tiba & Perizinan -- persis pola Step 1 Persiapan
     * SEBELUM refactor 5 sub-step (Stage 4d). Sengaja REUSE stage='persiapan'
     * evidence_type='barang_tiba'/'perizinan' (bukan kode baru) supaya:
     * 1. Fallback evidence-based `persiapanDone` di Project::progressSummary()
     *    (sequence<=6 -> cek 2 boolean ini) otomatis tetap akurat tanpa ubah.
     * 2. UI approval admin yg SUDAH ADA (cek stage='persiapan') otomatis
     *    berfungsi utk step ini juga, tidak perlu bikin approval flow baru.
     */
    public function persiapanInstalasi($id)
    {
        $project = $this->getAssignedProject($id);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        // Gate: cuma bisa diakses kalau Step 1 (5 sub-step Persiapan) sudah
        // tuntas (sequence > 5 = sudah di persiapan_instalasi atau lebih).
        $seq = $lop->stage?->sequence;
        abort_if($seq !== null && $seq < 6, 403, 'Selesaikan Step 1 Persiapan terlebih dahulu.');

        $evidences = $project->evidences ?? collect();

        $barangTibaPhotos = $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba')->sortByDesc('created_at')->values();
        $barangTibaUploaded = $barangTibaPhotos->count() > 0;
        $barangTibaStatus = null;
        if ($barangTibaUploaded) {
            if ($barangTibaPhotos->where('status', 'rejected')->count() > 0) {
                $barangTibaStatus = 'rejected';
            } elseif ($barangTibaPhotos->where('status', 'pending')->count() > 0) {
                $barangTibaStatus = 'pending';
            } else {
                $barangTibaStatus = 'approved';
            }
        }

        $perizinanPhotos = $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan')->sortByDesc('created_at')->values();
        $perizinanUploaded = $perizinanPhotos->count() > 0;
        $perizinanStatus = null;
        if ($perizinanUploaded) {
            if ($perizinanPhotos->where('status', 'rejected')->count() > 0) {
                $perizinanStatus = 'rejected';
            } elseif ($perizinanPhotos->where('status', 'pending')->count() > 0) {
                $perizinanStatus = 'pending';
            } else {
                $perizinanStatus = 'approved';
            }
        }

        $persiapanInstalasiUploadedComplete = $barangTibaUploaded && $perizinanUploaded;

        // Kendala & Kronologi universal (sama pola dgn persiapan()) -- Step 2
        // ini juga wajib punya tombol Lapor Kendala/Update Kronologi.
        $kronologis = $lop->kronologis()->with('creator')->get();
        $kendalaCategories = KendalaCategory::active()->get();

        return view('waspang.steps.persiapan-instalasi', compact(
            'project',
            'lop',
            'barangTibaPhotos',
            'barangTibaUploaded',
            'barangTibaStatus',
            'perizinanPhotos',
            'perizinanUploaded',
            'perizinanStatus',
            'persiapanInstalasiUploadedComplete',
            'kronologis',
            'kendalaCategories'
        ));
    }

    public function instalasi($id)
    {
        $project = $this->getAssignedProject($id);

        // Section AD: sumber item Material = ronde BOQ Survey TERBARU yang
        // sudah selesai (kalau ada) -- else fallback BOQ Plan murni
        // (quantity_plan !== null, bag. AC). Lihat Project::
        // materialProgressItems() utk alasan lengkap. Prefix "M-" ATAU type
        // master designator = 'material' tetap dipakai sbg filter Material
        // (lihat catatan lama di dashboard()) supaya item non-"M-" (mis.
        // project Konstruksi Eksternal) tetap muncul.
        $materialSource = $project->materialProgressItems();
        $materialBoqItems = $materialSource['items'];
        $materialSourceType = $materialSource['source'];
        $materialSourceRound = $materialSource['round'];

        $persiapanComplete = $this->isPersiapanUploaded($id);

        abort_if(! $persiapanComplete, 403);

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

        $instalasiComplete = $boqTotal > 0 && $boqUploaded >= $boqTotal;
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
            'project', 'boqTotal', 'boqUploaded', 'persiapanComplete',
            'instalasiComplete', 'pengukuranComplete', 'finishingComplete', 'revisionHistories',
            'materialSourceType', 'materialSourceRound'
        ));
    }

    public function pengukuran($id)
    {

        // Self-heal: kalau semua item Pengukuran sudah lengkap/Tidak Ada
        // tapi status_progress LOP masih nyangkut di 'pengukuran' (mis. LOP
        // lama yg item2-nya ditandai N/A SEBELUM gate auto-advance di
        // toggleMeasurementCheck() ada, atau transisi lain yg sempat
        // terlewat), majukan DULU di sini sebelum $project utk view
        // di-fetch -- supaya "Posisi" di header stepper (resources/views/
        // waspang/partials/stepper.blade.php) tidak pernah nyangkut
        // permanen walau Waspang sudah benar2 selesai. Pakai instance
        // TERPISAH (bukan $project di bawah) supaya progressSummary()
        // cache milik $project yg dipakai view tidak ikut basi kalau
        // status_progress berubah di tengah request ini.
        $this->maybeAdvancePengukuranStage(
            Project::with(['evidences', 'lop.stage'])->findOrFail($id)
        );
        $project = Project::with([
            'evidences',
            'boqItems',
            'lop.stage',
        ])->findOrFail($id);

        $revisionHistories = [
            'otdr' => EvidenceRevisionHistory::where('project_id', $project->id_project)
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'otdr')
                ->latest()
                ->get(),

            // File SOR & Eviden Lainnya sekarang dicek dgn evidence_type
            // KANONIK (file_sor/eviden_lainnya, samakan dgn
            // LopMeasurementCheck::ITEMS) TAPI eviden lama sebelum Stage 4
            // masih tersimpan dgn nama lama (otdr_sor/lainnya) -- gabungkan
            // riwayatnya (whereIn) supaya histori revisi lama tidak hilang.
            'file_sor' => EvidenceRevisionHistory::where('project_id', $project->id_project)
                ->where('stage', 'pengukuran')
                ->whereIn('evidence_type', ['file_sor', 'otdr_sor'])
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

            'eviden_lainnya' => EvidenceRevisionHistory::where('project_id', $project->id_project)
                ->where('stage', 'pengukuran')
                ->whereIn('evidence_type', ['eviden_lainnya', 'lainnya'])
                ->latest()
                ->get(),
        ];

        // Stage 4: gate nyata pengukuran (lihat Project::progressSummary())
        // -- ambil baris lop_measurement_checks yang SUDAH ADA per item
        // (tidak auto-create di sini, murni baca; baris baru hanya dibuat
        // saat waspang menandai "Tidak Ada" atau saat admin approve eviden,
        // lihat toggleMeasurementCheck() & ProjectController::approveEvidence()).
        $measurementChecks = $project->lop
            ? LopMeasurementCheck::where('lop_id', $project->lop->id_lop)->get()->keyBy('item_key')
            : collect();

        return view('waspang.steps.pengukuran', compact(
            'project',
            'revisionHistories',
            'measurementChecks'
        ));
    }

    /**
     * Gate tunggal "boleh maju status_progress pengukuran -> finishing?"
     * (Section AJ) -- dipakai baik oleh toggleMeasurementCheck() (dipicu
     * user menandai/batal-menandai item Tidak Ada) maupun oleh
     * pengukuran() (self-heal saat halaman dibuka, jaga-jaga kalau ada LOP
     * yg transisinya sempat terlewat, mis. semua item ditandai N/A sebelum
     * gate ini ada). Sama persis syaratnya dgn barier di ProjectController
     * (approveEvidence()/submitGoliveDocuments()): bukan PT2, belum
     * drop/golive, tidak sedang hold/drop, persis di sequence 8
     * (Pengukuran), dan pengukuranDone (5 item lop_measurement_checks
     * sudah lengkap/Tidak Ada semua). Idempotent -- aman dipanggil
     * berkali-kali walau LOP sudah lewat tahap ini.
     */
    private function maybeAdvancePengukuranStage(Project $project): void
    {
        $lop = $project->lop;

        if (! $lop) {
            return;
        }

        $programSap = strtoupper($lop->program_sap ?? '');
        $isPt2 = str_contains($programSap, 'PT2') || str_contains($programSap, 'PT-2') || str_contains($programSap, 'PT 2');
        $isAlreadyClosed = in_array($lop->status_progress, ['drop', 'golive'], true) || (bool) $lop->is_golive;
        $currentStage = $lop->stage;
        $currentSequence = $currentStage?->sequence;
        $isPausedOrDropped = (bool) ($currentStage?->is_pause_type || $currentStage?->is_terminal);

        if ($isPt2 || $isAlreadyClosed || $isPausedOrDropped || $currentSequence !== 8) {
            return;
        }

        $summary = $project->progressSummary();

        if (! ($summary['pengukuranDone'] ?? false)) {
            return;
        }

        $lop->advanceStage('finishing', auth()->id());

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'lop_stage_advance',
            'title' => 'LOP Maju ke Finishing',
            'description' => 'Seluruh item Pengukuran sudah lengkap/Tidak Ada, LOP maju otomatis ke tahap Finishing.',
            'status_before' => 'pengukuran',
            'status_after' => 'finishing',
        ]);
    }

    /**
     * Toggle "Tidak Ada" (N/A) untuk 1 item pengukuran (Stage 4 -- gate
     * nyata lop_measurement_checks, lihat Project::progressSummary()).
     * Hanya boleh ditandai N/A kalau item tsb BELUM ada eviden sama sekali
     * (apapun statusnya) -- kalau sudah ada foto/file, waspang harus hapus
     * dulu (atau eviden itu nanti di-approve admin & otomatis mengisi
     * evidence_id lewat ProjectController::approveEvidence()).
     */
    public function toggleMeasurementCheck(Request $request, $project, $itemKey)
    {
        $project = $this->getAssignedProject($project);

        abort_unless(in_array($itemKey, LopMeasurementCheck::ITEMS, true), 404);

        $request->validate([
            'is_not_applicable' => 'required|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $lop = Lop::where('project_id', $project->id_project)->first();

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        $wantsNotApplicable = $request->boolean('is_not_applicable');

        if ($wantsNotApplicable) {
            // Alias nama lama -> nama kanonik, lihat catatan di pengukuran().
            $legacyAliases = [
                'file_sor' => ['file_sor', 'otdr_sor'],
                'eviden_lainnya' => ['eviden_lainnya', 'lainnya'],
            ];
            $typesToCheck = $legacyAliases[$itemKey] ?? [$itemKey];

            $hasAnyEvidence = Evidence::where('project_id', $project->id_project)
                ->where('stage', 'pengukuran')
                ->whereIn('evidence_type', $typesToCheck)
                ->exists();

            if ($hasAnyEvidence) {
                return back()->with('error', 'Item ini sudah punya eviden terupload. Hapus dulu eviden yang ada sebelum menandai "Tidak Ada".');
            }
        }

        $check = LopMeasurementCheck::firstOrNew([
            'lop_id' => $lop->id_lop,
            'item_key' => $itemKey,
        ]);

        $check->is_not_applicable = $wantsNotApplicable;
        $check->note = $wantsNotApplicable ? $request->note : null;
        $check->checked_by = auth()->user()->id_user;
        $check->save();

        // Gate "boleh maju ke Finishing?" dipusatkan di
        // maybeAdvancePengukuranStage() -- dipakai bersama oleh toggle ini
        // maupun self-heal saat halaman Pengukuran dibuka (lihat
        // WaspangController::pengukuran()), supaya aturannya selalu 1 sumber
        // & konsisten (PT2/hold/drop/golive tidak disentuh, urutan sequence
        // tidak boleh dilompati).
        $this->maybeAdvancePengukuranStage($project);

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'toggle_measurement_na',
            'title' => $wantsNotApplicable ? 'Item Pengukuran Ditandai Tidak Ada' : 'Batal Tandai Tidak Ada',
            'description' => 'Waspang menandai item pengukuran "'.(LopMeasurementCheck::LABELS[$itemKey] ?? $itemKey).'" sebagai '.($wantsNotApplicable ? 'Tidak Ada (N/A)' : 'berlaku kembali').'.',
            'stage' => 'pengukuran',
            'meta' => [
                'item_key' => $itemKey,
                'is_not_applicable' => $wantsNotApplicable,
                'note' => $check->note,
            ],
        ]);

        return back()->with('success', $wantsNotApplicable ? 'Item ditandai Tidak Ada.' : 'Penanda Tidak Ada dibatalkan.');
    }

    // ==================================================================
    // STAGE 4d -- SUB-STEP PERSIAPAN BARU (Survey/Perizinan/Material
    // Delivery). Lihat ANALISA_REFACTOR_PERSIAPAN.md bag. Q.2 utk spec asal.
    // ==================================================================

    /**
     * Sub-step Survey (Mode Input Manual): tambah 1 item BOQ dgn memilih
     * designator (TomSelect, lihat resources/views/waspang/show.blade.php)
     * + qty manual. Upsert per lop_id+designator_id -- kalau item yg sama
     * sudah pernah ditambah, qty-nya DITAMBAHKAN (bukan ditimpa), supaya
     * waspang bisa nambah bertahap tanpa harus tahu qty sebelumnya.
     */
    public function storeBoqItemManual(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        $request->validate([
            'designator_id' => 'required|exists:designators,id_designator',
            'quantity_plan' => 'required|numeric|min:0.01',
        ]);

        $designator = Designator::findOrFail($request->designator_id);

        $existing = BoqItem::where('lop_id', $lop->id_lop)
            ->where('designator_id', $designator->id_designator)
            ->first();

        if ($existing) {
            $existing->quantity_plan = (float) $existing->quantity_plan + (float) $request->quantity_plan;
            $existing->save();
            $boq = $existing;
            $actionLabel = 'ditambah qty (item sudah ada)';
        } else {
            $boq = BoqItem::create([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'designator_id' => $designator->id_designator,
                'designator' => $designator->designator,
                'item_name' => $designator->item_name,
                'unit' => $designator->unit,
                'quantity_plan' => $request->quantity_plan,
                'quantity_actual' => 0,
            ]);
            $actionLabel = 'ditambah baru';
        }

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'survey_boq_add',
            'title' => 'Tambah Item BOQ (Survey)',
            'description' => "Waspang menambahkan item BOQ survey: {$designator->designator} - {$designator->item_name} ({$actionLabel}), qty {$request->quantity_plan} {$designator->unit}.",
            'stage' => 'survey',
            'meta' => [
                'boq_item_id' => $boq->id_boq,
                'designator_id' => $designator->id_designator,
                'quantity_plan' => $request->quantity_plan,
            ],
        ]);

        return back()->with('success', 'Item BOQ berhasil ditambahkan.');
    }

    /**
     * Hapus 1 item BOQ hasil Survey -- hanya boleh selama belum ada eviden
     * instalasi yg menempel ke item tsb (supaya tidak menghapus riwayat
     * progress yg sudah berjalan).
     */
    public function deleteBoqItemSurvey($project, $boq)
    {
        $project = $this->getAssignedProject($project);

        $boqItem = BoqItem::where('id_boq', $boq)
            ->where('project_id', $project->id_project)
            ->firstOrFail();

        $hasEvidence = Evidence::where('boq_item_id', $boqItem->id_boq)->exists();

        if ($hasEvidence) {
            return back()->with('error', 'Item ini sudah punya eviden instalasi terkait, tidak bisa dihapus.');
        }

        $lopId = $boqItem->lop_id;
        $label = $boqItem->designator.' - '.$boqItem->item_name;
        $boqItem->delete();

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lopId,
            'activity_type' => 'survey_boq_delete',
            'title' => 'Hapus Item BOQ (Survey)',
            'description' => "Waspang menghapus item BOQ survey: {$label}.",
            'stage' => 'survey',
        ]);

        return back()->with('success', 'Item BOQ berhasil dihapus.');
    }

    /**
     * Template Excel flat (2 kolom) khusus Survey waspang -- BEDA dari
     * template Bulk Import BOQ admin (ImportController::downloadBoqTemplate,
     * matriks 1 kolom per LOP) yg tidak cocok utk konteks 1 project/waspang.
     */
    public function downloadBoqTemplateWaspang()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template BOQ Survey');

        $sheet->setCellValue('A1', 'Kode Designator');
        $sheet->setCellValue('B1', 'Quantity Plan');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $sheet->setCellValue('A2', 'M-CONTOH-001');
        $sheet->setCellValue('B2', 10);

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(16);

        $filename = 'template_boq_survey_waspang.xlsx';
        $tmpDir = storage_path('app/tmp');

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $path = $tmpDir.'/'.uniqid().'_'.$filename;

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Import massal item BOQ Survey dari file Excel (template flat 2 kolom
     * di atas) -- upsert per lop_id+designator_id (sama seperti input
     * manual), designator dicocokkan dari master `designators` via kode
     * persis (kolom A). Baris dgn kode designator yg tidak ditemukan di
     * master dilewati & dilaporkan balik ke waspang.
     */
    public function importBoqExcelWaspang(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        $request->validate([
            'boq_file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $spreadsheet = IOFactory::load($request->file('boq_file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedLabels = [];

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex == 1) {
                continue; // baris header
            }

            $designatorCode = trim((string) ($row['A'] ?? ''));
            $qty = $row['B'] ?? null;

            if ($designatorCode === '' || $qty === null || $qty === '') {
                continue;
            }

            $designator = Designator::where('designator', $designatorCode)->first();

            if (! $designator) {
                $skipped++;
                $skippedLabels[] = $designatorCode;

                continue;
            }

            $existing = BoqItem::where('lop_id', $lop->id_lop)
                ->where('designator_id', $designator->id_designator)
                ->first();

            if ($existing) {
                $existing->quantity_plan = (float) $qty;
                $existing->save();
                $updated++;
            } else {
                BoqItem::create([
                    'project_id' => $project->id_project,
                    'lop_id' => $lop->id_lop,
                    'designator_id' => $designator->id_designator,
                    'designator' => $designator->designator,
                    'item_name' => $designator->item_name,
                    'unit' => $designator->unit,
                    'quantity_plan' => $qty,
                    'quantity_actual' => 0,
                ]);
                $created++;
            }
        }

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'survey_boq_import',
            'title' => 'Import Excel BOQ (Survey)',
            'description' => "Waspang import BOQ via Excel: {$created} baru, {$updated} diperbarui, {$skipped} dilewati (kode designator tidak ditemukan).",
            'stage' => 'survey',
            'meta' => [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'skipped_labels' => $skippedLabels,
            ],
        ]);

        if ($skipped > 0) {
            $preview = implode(', ', array_slice($skippedLabels, 0, 5)).(count($skippedLabels) > 5 ? ', ...' : '');

            return back()->with('error', "Import selesai: {$created} baru, {$updated} diperbarui. {$skipped} baris dilewati -- kode designator tidak ditemukan di master ({$preview}).");
        }

        return back()->with('success', "Import BOQ berhasil: {$created} item baru, {$updated} item diperbarui.");
    }

    /**
     * Buat/lanjutkan draft redesign dari accordion Persiapan > Survey.
     * Nama survey selalu mengikuti LOP, bukan input bebas pengguna.
     */
    public function startSurveyRedesign($project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $this->getSingleSurveyLop($project);

        if ($lop->status_progress !== 'survey') {
            return back()->with('error', 'Redesign hanya dapat dilakukan saat LOP berada di tahap Survey.');
        }

        if ($this->surveyMapVersions($project)->isEmpty()) {
            return back()->with('error', 'KML desain awal dari Admin belum tersedia.');
        }

        $survey = SiteSurvey::query()
            ->where('project_id', $project->id_project)
            ->where('surveyor_id', auth()->user()->id_user)
            ->where('status', 'draft')
            ->latest('id_site_surveys')
            ->first();

        if (! $survey) {
            $survey = SiteSurvey::create([
                'project_id' => $project->id_project,
                'project_name' => $lop->lop_name,
                'title' => $lop->lop_name,
                'surveyor_id' => auth()->user()->id_user,
                'status' => 'draft',
                'notes' => 'Redesign dari sub-step Persiapan > Survey.',
            ]);

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => 'survey_redesign_started',
                'title' => 'Redesign Survey Dimulai',
                'description' => 'Waspang memulai redesign peta untuk LOP '.$lop->lop_name.'.',
                'stage' => 'survey',
                'meta' => ['site_survey_id' => $survey->id],
            ]);
        }

        return redirect()->route('surveyor.show', $survey->id);
    }

    /** Simpan keputusan "Sesuai" untuk versi peta yang sedang aktif. */
    public function confirmSurveyMap($project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $this->getSingleSurveyLop($project);

        if ($lop->status_progress !== 'survey') {
            return back()->with('error', 'LOP sudah tidak berada di tahap Survey.');
        }

        $currentMap = $this->surveyMapVersions($project)->last();

        if (! $currentMap) {
            return back()->with('error', 'Belum ada peta yang dapat dikonfirmasi.');
        }

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'survey_map_confirmed',
            'title' => 'Desain Survey Dinyatakan Sesuai',
            'description' => 'Waspang menyatakan desain peta aktif sudah sesuai.',
            'stage' => 'survey',
            'meta' => [
                'map_key' => $currentMap['key'],
                'map_label' => $currentMap['label'],
            ],
        ]);

        return back()->with('success', 'Desain peta dikonfirmasi sesuai. Silakan finalisasi volume Survey.');
    }

    /** Tambah pasangan designator baru dengan Volume Plan kosong. */
    public function addSurveyBoqItem(Request $request, $project, SurveyPreparationService $surveyPreparation)
    {
        $project = $this->getAssignedProject($project);
        $lop = $this->getSingleSurveyLop($project);

        if ($lop->status_progress !== 'survey') {
            return back()->with('error', 'BOQ Survey hanya dapat diubah pada tahap Survey.');
        }

        if (! $this->currentSurveyMapIsConfirmed($project, $lop)) {
            return back()->with('error', 'Konfirmasi desain peta dengan tombol Sesuai terlebih dahulu.');
        }

        $validated = $request->validate([
            'designator_id' => 'required|integer|exists:designators,id_designator',
            'volume_survey' => 'required|integer|min:0',
        ]);

        $selected = Designator::forCustomer($project->customer_id)
            ->where('id_designator', $validated['designator_id'])
            ->firstOrFail();
        $matchingDesignators = $surveyPreparation->matchingDesignators($selected);
        $designatorIds = $matchingDesignators->pluck('id_designator');

        $alreadyExists = BoqItem::query()
            ->where('project_id', $project->id_project)
            ->where('lop_id', $lop->id_lop)
            ->whereIn('designator_id', $designatorIds)
            ->exists();

        if ($alreadyExists) {
            return back()->with('error', 'Designator atau pasangannya sudah ada di BOQ Plan.');
        }

        DB::transaction(function () use ($matchingDesignators, $project, $lop, $validated): void {
            foreach ($matchingDesignators as $designator) {
                BoqItem::create([
                    'project_id' => $project->id_project,
                    'lop_id' => $lop->id_lop,
                    'designator_id' => $designator->id_designator,
                    'designator' => $designator->designator,
                    'item_name' => $designator->item_name,
                    'unit' => $designator->unit,
                    'quantity_plan' => null,
                    // Volume BOQ Survey -- KOLOM TERPISAH dari quantity_actual
                    // (lihat bag. AB): quantity_actual murni utk progress
                    // Instalasi Step 3, baru diisi nanti di sana, BUKAN di sini.
                    'quantity_survey' => $validated['volume_survey'],
                ]);
            }

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => 'survey_boq_additional_added',
                'title' => 'Tambah Designator Survey',
                'description' => 'Waspang menambahkan designator hasil Survey: '.$matchingDesignators->pluck('designator')->implode(' / ').'.',
                'stage' => 'survey',
                'meta' => [
                    'designator_ids' => $matchingDesignators->pluck('id_designator')->values()->all(),
                    'volume_survey' => (float) $validated['volume_survey'],
                ],
            ]);
        });

        return back()->with('success', 'Designator tambahan berhasil ditambahkan ke draf Survey.');
    }

    /** Hapus hanya item tambahan; item BOQ Plan dari Admin tetap terkunci. */
    public function deleteSurveyBoqItem($project, $boq, SurveyPreparationService $surveyPreparation)
    {
        $project = $this->getAssignedProject($project);
        $lop = $this->getSingleSurveyLop($project);

        if ($lop->status_progress !== 'survey') {
            return back()->with('error', 'BOQ Survey hanya dapat diubah pada tahap Survey.');
        }

        $groups = $surveyPreparation->groupBoqItems(
            BoqItem::with(['designatorData', 'designatorDataByCode'])->where('project_id', $project->id_project)->where('lop_id', $lop->id_lop)->get()
        );
        $group = $groups->first(fn (array $item) => in_array((int) $boq, $item['item_ids'], true));

        if (! $group || ! $group['is_additional']) {
            return back()->with('error', 'BOQ Plan dari Admin terkunci dan tidak dapat dihapus.');
        }

        if (Evidence::whereIn('boq_item_id', $group['item_ids'])->exists()) {
            return back()->with('error', 'Designator sudah mempunyai eviden dan tidak dapat dihapus.');
        }

        DB::transaction(function () use ($project, $lop, $group): void {
            BoqItem::query()
                ->where('project_id', $project->id_project)
                ->where('lop_id', $lop->id_lop)
                ->whereIn('id_boq', $group['item_ids'])
                ->delete();

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => 'survey_boq_additional_deleted',
                'title' => 'Hapus Designator Tambahan Survey',
                'description' => 'Waspang menghapus designator tambahan '.$group['designator'].'.',
                'stage' => 'survey',
                'meta' => ['item_ids' => $group['item_ids']],
            ]);
        });

        return back()->with('success', 'Designator tambahan berhasil dihapus.');
    }

    /** Simpan volume sementara tanpa memindahkan tahapan. */
    public function saveSurveyBoqDraft(Request $request, $project, SurveyPreparationService $surveyPreparation)
    {
        $project = $this->getAssignedProject($project);
        $lop = $this->getSingleSurveyLop($project);

        if ($lop->status_progress !== 'survey') {
            return back()->with('error', 'LOP sudah tidak berada di tahap Survey.');
        }

        if (! $this->currentSurveyMapIsConfirmed($project, $lop)) {
            return back()->with('error', 'Konfirmasi desain peta dengan tombol Sesuai terlebih dahulu.');
        }

        $validated = $request->validate([
            'volumes' => 'required|array|min:1',
            'volumes.*' => 'nullable|integer|min:0',
        ]);
        $groups = $this->currentSurveyBoqGroups($project, $lop, $surveyPreparation);
        $volumes = DB::transaction(function () use ($project, $lop, $groups, $validated): array {
            $volumes = $this->persistSurveyVolumes($groups, $validated['volumes'], false);

            if ($volumes === []) {
                return [];
            }

            $previousVolumes = ProjectActivityLog::query()
                ->where('project_id', $project->id_project)
                ->where('lop_id', $lop->id_lop)
                ->where('activity_type', 'survey_boq_draft_saved')
                ->latest('id_project_activity')
                ->value('meta');
            $previousVolumes = is_string($previousVolumes) ? json_decode($previousVolumes, true) : $previousVolumes;
            $previousVolumes = is_array($previousVolumes) ? $previousVolumes : [];
            $allVolumes = array_replace($previousVolumes['volumes'] ?? [], $volumes);

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => 'survey_boq_draft_saved',
                'title' => 'Draf Finalisasi Survey Disimpan',
                'description' => 'Waspang menyimpan sementara volume hasil Survey.',
                'stage' => 'survey',
                'meta' => ['volumes' => $allVolumes],
            ]);

            return $volumes;
        });

        if ($volumes === []) {
            return back()->with('error', 'Isi minimal satu Volume Survey sebelum menyimpan draf.');
        }

        return back()->with('success', 'Draf Volume Survey berhasil disimpan.');
    }

    /**
     * Ambang deviasi nominal BOQ Survey vs BOQ Plan yg memicu Approval
     * Redesign (Stage 4e). Lihat ANALISA_REFACTOR_PERSIAPAN.md bag. U.
     */
    private const SURVEY_DEVIATION_THRESHOLD = 0.10;

    /**
     * Finalisasi Survey: seluruh volume wajib diisi. Kalau deviasi nominal
     * (vs BOQ Plan, dihitung dari designator_package_prices) di ambang
     * batas, status TETAP di 'survey' dan menunggu Approval Redesign
     * (lihat uploadSurveyRedesignApproval()) -- selain itu langsung
     * berpindah ke Perizinan. BOQ Plan tidak pernah diubah; hanya
     * quantity_actual yang disimpan.
     */
    public function finishSurvey(Request $request, $project, SurveyPreparationService $surveyPreparation)
    {
        $project = $this->getAssignedProject($project);
        $lop = $this->getSingleSurveyLop($project);

        if ($lop->status_progress !== 'survey') {
            return back()->with('error', 'LOP sudah tidak berada di tahap Survey.');
        }

        if (! $this->currentSurveyMapIsConfirmed($project, $lop)) {
            return back()->with('error', 'Konfirmasi desain peta dengan tombol Sesuai terlebih dahulu.');
        }

        $validated = $request->validate([
            'volumes' => 'required|array|min:1',
            'volumes.*' => 'required|integer|min:0',
        ]);
        $groups = $this->currentSurveyBoqGroups($project, $lop, $surveyPreparation);

        if ($groups->isEmpty()) {
            return back()->with('error', 'BOQ Plan belum tersedia untuk LOP ini.');
        }

        // Stage 4e: hitung deviasi nominal SEBELUM commit apapun -- kalau
        // package/harga belum lengkap, Selesai Survey diblokir total (sesuai
        // keputusan user), waspang dipersilakan pakai "Simpan Draf" dulu.
        $boqItems = BoqItem::where('lop_id', $lop->id_lop)->get(['id_boq', 'designator_id', 'designator']);
        $nominal = $surveyPreparation->evaluateNominalDeviation($groups, $validated['volumes'], $boqItems, $lop->package_id);

        if (in_array('__no_package__', $nominal['missing'], true)) {
            return back()->with('error', 'Package LOP ini belum diisi Admin -- dibutuhkan untuk menghitung nilai nominal BOQ (Survey vs Plan). Hubungi Admin untuk melengkapi Package sebelum Selesai Survey.')
                ->withInput();
        }

        if ($nominal['missing'] !== []) {
            $preview = implode(', ', array_slice($nominal['missing'], 0, 5)).(count($nominal['missing']) > 5 ? ', ...' : '');

            return back()->with('error', "Harga sebagian designator belum tersedia untuk Package LOP ini ({$preview}). Hubungi Admin untuk melengkapi harga sebelum Selesai Survey.")
                ->withInput();
        }

        $planTotal = $nominal['plan_total'];
        $surveyTotal = $nominal['survey_total'];
        $deviation = $planTotal > 0.0
            ? abs($surveyTotal - $planTotal) / $planTotal
            : ($surveyTotal > 0.0 ? 1.0 : 0.0);
        $deviationPercent = round($deviation * 100, 2);
        $needsRedesignApproval = $deviation > self::SURVEY_DEVIATION_THRESHOLD;

        DB::transaction(function () use ($project, $lop, $groups, $validated, $planTotal, $surveyTotal, $deviationPercent, $needsRedesignApproval): void {
            $volumes = $this->persistSurveyVolumes($groups, $validated['volumes'], true);

            // Re Survey (lihat startReSurvey()): ronde berjalan sudah dibuat
            // saat tombol "Re Survey" ditekan (atau round 1 dibuat otomatis
            // di sini utk LOP lama yg belum pernah punya ronde). Simpan
            // total nominal & deviasi ronde ini SEBELUM ditutup.
            $round = $this->currentSurveyRound($lop);
            $round->plan_total = $planTotal;
            $round->survey_total = $surveyTotal;
            $round->deviation_percent = $deviationPercent;
            $round->redesign_required = $needsRedesignApproval;
            $round->save();

            $lop->survey_deviation_percent = $deviationPercent;
            $lop->survey_redesign_required = $needsRedesignApproval;

            if (! $needsRedesignApproval) {
                $lop->advanceStage('perizinan', auth()->id());
                $this->completeSurveyRound($round, $lop);
            }

            $lop->save();

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => $needsRedesignApproval ? 'survey_deviation_detected' : 'survey_finalized',
                'title' => $needsRedesignApproval ? 'Deviasi BOQ Survey Terdeteksi' : 'Survey Selesai',
                'description' => $needsRedesignApproval
                    ? "Waspang memfinalisasi {$groups->count()} baris BOQ Survey (ronde {$round->round_number}) -- nilai nominal menyimpang {$deviationPercent}% dari Plan (>10%), menunggu upload bukti persetujuan Redesign."
                    : "Waspang memfinalisasi {$groups->count()} baris BOQ Survey (ronde {$round->round_number}) dan melanjutkan ke Perizinan.",
                'status_before' => 'survey',
                'status_after' => $needsRedesignApproval ? 'survey' : 'perizinan',
                'stage' => 'survey',
                'meta' => ['volumes' => $volumes, 'deviation_percent' => $deviationPercent, 'round_number' => $round->round_number],
            ]);
        });

        if ($needsRedesignApproval) {
            return back()->with('warning', "Volume Survey tersimpan, namun nilai BOQ Survey menyimpang {$deviationPercent}% dari Plan (di atas ambang 10%). Upload bukti persetujuan Redesign untuk melanjutkan ke Perizinan.");
        }

        return back()->with('success', 'Survey selesai. Lanjut ke Perizinan.');
    }

    /**
     * Stage 4e -- waspang mengunggah bukti foto/capture persetujuan Redesign
     * (self-declare, tidak ada langkah approve terpisah oleh role lain).
     * Begitu tersimpan, langsung melanjutkan status_progress ke Perizinan.
     */
    public function uploadSurveyRedesignApproval(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $this->getSingleSurveyLop($project);

        if ($lop->status_progress !== 'survey') {
            return back()->with('error', 'LOP sudah tidak berada di tahap Survey.');
        }

        if (! $lop->survey_redesign_required) {
            return back()->with('error', 'Tidak ada deviasi Survey yang perlu disetujui saat ini.');
        }

        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:10240',
        ]);

        $projectFolder = $this->evidenceLopFolder($project->id_project);
        $deviationPercent = $lop->survey_deviation_percent;

        DB::transaction(function () use ($request, $project, $lop, $projectFolder, $deviationPercent): void {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->storeAs(
                    "evidences/{$projectFolder}/survey/redesign_approval",
                    now()->format('Ymd_His').'_'.uniqid().'.jpg',
                    'public'
                );

                Evidence::create([
                    'project_id' => $project->id_project,
                    'uploaded_by' => auth()->user()->id_user,
                    'stage' => 'survey',
                    'evidence_type' => 'redesign_approval',
                    'file_path' => $path,
                    // Self-declare waspang -- tidak ada langkah approve role
                    // lain utk bukti ini, jadi langsung ditandai approved
                    // supaya tidak nyangkut di antrean Approval Eviden admin.
                    'status' => 'approved',
                ]);
            }

            $lop->survey_redesign_required = false;
            $lop->advanceStage('perizinan', auth()->id());
            $lop->save();

            // Ronde ini baru benar-benar tuntas setelah bukti Approval
            // Redesign diupload -- baru sekarang snapshot histori dibuat.
            $round = $this->currentSurveyRound($lop);
            $this->completeSurveyRound($round, $lop);

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => 'survey_redesign_approved',
                'title' => 'Redesign Survey Disetujui',
                'description' => "Waspang mengunggah bukti persetujuan deviasi BOQ Survey ({$deviationPercent}%, ronde {$round->round_number}) dan melanjutkan ke Perizinan.",
                'status_before' => 'survey',
                'status_after' => 'perizinan',
                'stage' => 'survey',
                'meta' => ['round_number' => $round->round_number],
            ]);
        });

        return back()->with('success', 'Bukti persetujuan tersimpan. Lanjut ke Perizinan.');
    }

    /**
     * Re Survey -- bisa dipicu dari tahap manapun SETELAH Survey pertama
     * kali selesai (tidak dibatasi hanya saat LOP masih di 'survey'),
     * sesuai keputusan user. Klik tombol ini:
     * 1. Membuka ronde BOQ Survey baru (boq_survey_rounds, round_number
     *    bertambah) -- ronde SEBELUMNYA tetap tersimpan utuh, tidak
     *    dihapus/ditimpa.
     * 2. Mengembalikan status_progress LOP ke 'survey' supaya UI Survey
     *    (termasuk daftar item BOQ) tampil seperti kondisi awal lagi utk
     *    diisi ulang -- boq_items TIDAK diduplikasi, quantity_actual-nya
     *    hanya akan ditimpa nanti saat ronde baru ini di-Selesai-kan
     *    (lihat finishSurvey()).
     */
    public function startReSurvey($project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $this->getSingleSurveyLop($project);

        $this->ensureBaselineSurveyRound($lop);

        $hasCompletedRound = BoqSurveyRound::where('lop_id', $lop->id_lop)
            ->where('status', 'completed')
            ->exists();

        if (! $hasCompletedRound) {
            return back()->with('error', 'Re Survey hanya dapat dilakukan setelah Survey pertama selesai.');
        }

        $hasInProgressRound = BoqSurveyRound::where('lop_id', $lop->id_lop)
            ->where('status', 'in_progress')
            ->exists();

        if ($hasInProgressRound || $lop->survey_redesign_required) {
            return back()->with('error', 'Masih ada ronde Survey yang berjalan/menunggu Approval Redesign. Selesaikan ronde tersebut terlebih dahulu.');
        }

        $statusBefore = $lop->status_progress;

        DB::transaction(function () use ($project, $lop, $statusBefore): void {
            $lastRoundNumber = (int) (BoqSurveyRound::where('lop_id', $lop->id_lop)->max('round_number') ?? 0);

            $round = BoqSurveyRound::create([
                'lop_id' => $lop->id_lop,
                'round_number' => $lastRoundNumber + 1,
                'status' => 'in_progress',
                'started_by' => auth()->user()->id_user,
                'started_at' => now(),
            ]);

            $lop->advanceStage('survey', auth()->id());
            $lop->survey_redesign_required = false;
            $lop->survey_deviation_percent = null;
            $lop->save();

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => 're_survey_started',
                'title' => 'Re Survey Dimulai',
                'description' => "Waspang memulai Re Survey (ronde {$round->round_number}) untuk LOP {$lop->lop_name}. Status dikembalikan ke Survey dari '{$statusBefore}'.",
                'status_before' => $statusBefore,
                'status_after' => 'survey',
                'stage' => 'survey',
                'meta' => ['round_number' => $round->round_number],
            ]);
        });

        return back()->with('success', 'Re Survey dimulai. Silakan isi ulang volume Survey.');
    }

    /**
     * LOP yang sudah menyelesaikan Survey SEBELUM fitur Re Survey ini ada
     * tidak pernah tercatat di boq_survey_rounds sama sekali -- tanpa
     * backfill ini, tombol Re Survey tidak akan pernah muncul utk LOP
     * lama walau Survey-nya sudah lama tuntas. Dipanggil dari persiapan()
     * (supaya kepakai lewat GET biasa) & startReSurvey() (jaga-jaga kalau
     * halaman belum sempat di-refresh). Idempotent -- no-op kalau LOP ini
     * sudah pernah punya baris ronde apapun.
     */
    private function ensureBaselineSurveyRound(Lop $lop): void
    {
        if (BoqSurveyRound::where('lop_id', $lop->id_lop)->exists()) {
            return;
        }

        // Masih di Inisiasi/Survey berarti belum pernah Selesai Survey sama
        // sekali -- bukan kandidat backfill, tombol Re Survey memang belum
        // seharusnya muncul.
        if (in_array($lop->status_progress, ['inisiasi', 'survey'], true)) {
            return;
        }

        $round = BoqSurveyRound::create([
            'lop_id' => $lop->id_lop,
            'round_number' => 1,
            'status' => 'completed',
            'deviation_percent' => $lop->survey_deviation_percent,
            'redesign_required' => false,
            'finished_at' => $lop->perizinan_completed_at ?? $lop->updated_at ?? now(),
            'note' => 'Dibuat otomatis (backfill) -- LOP ini menyelesaikan Survey sebelum fitur Re Survey tersedia.',
        ]);

        // quantity_survey SEHARUSNYA sudah dipisah migration 160000, tapi kalau
        // LOP ini belum pernah punya baris ronde SAAT migration jalan, migration
        // itu tidak sempat memprosesnya -- fallback ke quantity_actual (satu2nya
        // sisa data lama) DI SINI, sekaligus pindahkan permanen spy tidak
        // tertinggal lagi ke depannya.
        foreach (BoqItem::where('lop_id', $lop->id_lop)->get() as $item) {
            $surveyValue = $item->quantity_survey ?? $item->quantity_actual;

            BoqSurveyRoundItem::create([
                'boq_survey_round_id' => $round->id,
                'boq_item_id' => $item->id_boq,
                'designator_id' => $item->designator_id,
                'designator' => $item->designator,
                'item_name' => $item->item_name,
                'unit' => $item->unit,
                'quantity_plan' => $item->quantity_plan,
                'quantity_survey' => $surveyValue,
            ]);

            if ($item->quantity_survey === null && $item->quantity_actual !== null) {
                $hasRealInstalasiProgress = ProjectActivityLog::where('activity_type', 'update_quantity_actual')
                    ->where('meta->boq_item_id', $item->id_boq)
                    ->exists();

                $item->quantity_survey = $surveyValue;

                if (! $hasRealInstalasiProgress) {
                    $item->quantity_actual = null;
                }

                $item->save();
            }
        }
    }

    /** Ronde in_progress LOP saat ini; auto-buat round 1 utk LOP lama yg belum pernah punya ronde (backward compatibility). */
    private function currentSurveyRound(Lop $lop): BoqSurveyRound
    {
        $round = BoqSurveyRound::where('lop_id', $lop->id_lop)
            ->where('status', 'in_progress')
            ->latest('round_number')
            ->first();

        if ($round) {
            return $round;
        }

        $lastRoundNumber = (int) (BoqSurveyRound::where('lop_id', $lop->id_lop)->max('round_number') ?? 0);

        return BoqSurveyRound::create([
            'lop_id' => $lop->id_lop,
            'round_number' => $lastRoundNumber + 1,
            'status' => 'in_progress',
            'started_by' => auth()->user()->id_user,
            'started_at' => now(),
        ]);
    }

    /** Tutup ronde + snapshot seluruh boq_items LOP ini sebagai histori ronde tsb. */
    private function completeSurveyRound(BoqSurveyRound $round, Lop $lop): void
    {
        $round->status = 'completed';
        $round->finished_by = auth()->user()->id_user;
        $round->finished_at = now();
        $round->save();

        $items = BoqItem::where('lop_id', $lop->id_lop)->get();

        foreach ($items as $item) {
            BoqSurveyRoundItem::create([
                'boq_survey_round_id' => $round->id,
                'boq_item_id' => $item->id_boq,
                'designator_id' => $item->designator_id,
                'designator' => $item->designator,
                'item_name' => $item->item_name,
                'unit' => $item->unit,
                'quantity_plan' => $item->quantity_plan,
                'quantity_survey' => $item->quantity_survey,
            ]);
        }
    }

    /**
     * Redesign accordion Perizinan (Stage 4f) -- "Add Perizinan": 1
     * submission = pilih kategori perizinan + catatan kronologi (wajib) +
     * eviden foto/PDF (opsional, boleh campur & lebih dari 1 file
     * sekaligus). Bisa dipanggil berkali-kali selama LOP di tahap
     * Perizinan -- tiap submission jadi 1 baris baru `lop_kronologis`
     * (stage_code='perizinan'), TIDAK menimpa entri sebelumnya.
     * `lops.permit_category_id` SELALU ikut kategori TERBARU yang dipilih,
     * sementara kategori tiap entri sendiri tetap tercatat utuh di
     * `lop_kronologis.permit_category_id` sbg histori.
     */
    public function addPerizinan(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        if (! in_array($lop->status_progress, ['drm', 'perizinan'], true)) {
            return back()->with('error', 'LOP sudah tidak berada di tahap Perizinan.');
        }

        $validated = $request->validate([
            'permit_category_id' => 'required|exists:permit_categories,id',
            'event_date' => 'required|date',
            'note' => 'required|string|max:2000',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $projectFolder = $this->evidenceLopFolder($project->id_project);

        DB::transaction(function () use ($request, $project, $lop, $validated, $projectFolder): void {
            $kronologi = LopKronologi::create([
                'lop_id' => $lop->id_lop,
                'project_id' => $project->id_project,
                'stage_code' => 'perizinan',
                'permit_category_id' => $validated['permit_category_id'],
                'event_date' => $validated['event_date'],
                'note' => $validated['note'],
                'created_by' => auth()->user()->id_user,
            ]);

            $lop->update(['permit_category_id' => $validated['permit_category_id']]);

            foreach ($request->file('files') ?? [] as $file) {
                $isPdf = strtolower($file->getClientOriginalExtension()) === 'pdf';
                $path = $file->storeAs(
                    "evidences/{$projectFolder}/perizinan/add_perizinan",
                    now()->format('Ymd_His').'_'.uniqid().($isPdf ? '.pdf' : '.jpg'),
                    'public'
                );

                Evidence::create([
                    'project_id' => $project->id_project,
                    'lop_kronologi_id' => $kronologi->id,
                    'uploaded_by' => auth()->user()->id_user,
                    'stage' => 'perizinan',
                    'evidence_type' => $isPdf ? 'ba_kp' : 'eviden_perizinan',
                    'file_path' => $path,
                    'status' => 'pending',
                ]);
            }

            ProjectActivityService::log([
                'project_id' => $project->id_project,
                'lop_id' => $lop->id_lop,
                'activity_type' => 'add_perizinan',
                'title' => 'Add Perizinan',
                'description' => 'Waspang menambahkan aktivitas Perizinan ('.optional(PermitCategory::find($validated['permit_category_id']))->name.'): '.Str::limit($validated['note'], 150),
                'stage' => 'perizinan',
                'meta' => [
                    'kronologi_id' => $kronologi->id,
                    'permit_category_id' => $validated['permit_category_id'],
                    'event_date' => $validated['event_date'],
                ],
            ]);
        });

        return back()->with('success', 'Perizinan berhasil ditambahkan.');
    }

    /**
     * DIPERTAHANKAN utk kompatibilitas route lama -- sejak Stage 4f, kategori
     * perizinan dipilih langsung dari form "Add Perizinan" (lihat
     * addPerizinan() di atas), method & route ini sudah tidak dipanggil dari
     * UI manapun.
     */
    public function updatePerizinanCategory(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        $request->validate([
            'permit_category_id' => 'required|exists:permit_categories,id',
        ]);

        $lop->update(['permit_category_id' => $request->permit_category_id]);

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'update_permit_category',
            'title' => 'Update Kategori Perizinan',
            'description' => 'Waspang memilih kategori perizinan: '.optional(PermitCategory::find($request->permit_category_id))->name,
            'stage' => 'perizinan',
        ]);

        return back()->with('success', 'Kategori perizinan berhasil disimpan.');
    }

    /**
     * Tombol "Perizinan Selesai" (Stage 4f -- revisi: sebelumnya checkbox +
     * form upload BA KP wajib, SEKARANG langsung tombol aksi tanpa upload
     * tambahan lagi, krn eviden BA KP/foto sudah bisa dilampirkan per entri
     * lewat "Add Perizinan"). Syarat: minimal 1x Add Perizinan (baris
     * lop_kronologis stage_code='perizinan') sudah pernah disimpan. Klik
     * langsung menandai lops.perizinan_completed_at & transisi
     * status_progress: perizinan -> material_delivery.
     */
    public function togglePerizinanSelesai($project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        if (! in_array($lop->status_progress, ['drm', 'perizinan'], true)) {
            return back()->with('error', 'LOP sudah tidak berada di tahap Perizinan.');
        }

        $statusBefore = $lop->status_progress;

        $hasKronologi = LopKronologi::where('lop_id', $lop->id_lop)
            ->where('stage_code', 'perizinan')
            ->exists();

        if (! $hasKronologi) {
            return back()->with('error', 'Tambahkan minimal 1x Add Perizinan sebelum menandai Perizinan Selesai.');
        }

        $lop->update([
            'perizinan_completed_at' => now(),
        ]);
        $lop->advanceStage('material_delivery', auth()->id());

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'stage_transition',
            'title' => 'Perizinan Selesai',
            'description' => 'Waspang menandai Perizinan selesai, lanjut ke Material Delivery.',
            'status_before' => $statusBefore,
            'status_after' => 'material_delivery',
            'stage' => 'perizinan',
        ]);

        return back()->with('success', 'Perizinan selesai. Lanjut ke Material Delivery.');
    }

    /**
     * Tombol "Selesai Material Delivery" -- syarat: minimal 1 eviden foto
     * material delivery sudah diunggah. Transisi status_progress:
     * material_delivery -> persiapan_instalasi (Persiapan tuntas, gate
     * halaman Instalasi otomatis terbuka -- lihat isPersiapanUploaded()).
     */
    public function finishMaterialDelivery(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        if ($lop->status_progress !== 'material_delivery') {
            return back()->with('error', 'LOP sudah tidak berada di tahap Material Delivery.');
        }

        $hasEvidence = Evidence::where('project_id', $project->id_project)
            ->where('stage', 'material_delivery')
            ->exists();

        if (! $hasEvidence) {
            return back()->with('error', 'Upload minimal 1 eviden foto material delivery sebelum melanjutkan.');
        }

        $lop->advanceStage('persiapan_instalasi', auth()->id());

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'stage_transition',
            'title' => 'Material Delivery Selesai',
            'description' => 'Waspang menyelesaikan Material Delivery. Persiapan tuntas, lanjut ke Instalasi.',
            'status_before' => 'material_delivery',
            'status_after' => 'persiapan_instalasi',
            'stage' => 'material_delivery',
        ]);

        return back()->with('success', 'Material Delivery selesai. Persiapan tuntas, lanjut ke Instalasi.');
    }

    /**
     * Tombol final "Next Step 3 - Instalasi" di halaman Step 2 Persiapan
     * Instalasi (2 kartu Barang Tiba/Perizinan) -- syarat SAMA seperti pola
     * halaman Persiapan lama sebelum refactor Stage 4d: kedua eviden sudah
     * ter-upload (status apapun, TIDAK wajib approved dulu) & tidak ada yg
     * berstatus rejected. Transisi status_progress: persiapan_instalasi ->
     * instalasi, lalu REDIRECT ke halaman Instalasi (bukan back(), krn
     * tujuannya memang pindah halaman).
     */
    public function finishPersiapanInstalasi(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        if ($lop->status_progress !== 'persiapan_instalasi') {
            return back()->with('error', 'LOP sudah tidak berada di tahap Persiapan Instalasi.');
        }

        $barangTibaEvidences = Evidence::where('project_id', $project->id_project)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'barang_tiba')
            ->get();

        $perizinanEvidences = Evidence::where('project_id', $project->id_project)
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'perizinan')
            ->get();

        if ($barangTibaEvidences->isEmpty() || $perizinanEvidences->isEmpty()) {
            return back()->with('error', 'Upload Eviden Barang Tiba dan Eviden Perizinan terlebih dahulu.');
        }

        if ($barangTibaEvidences->where('status', 'rejected')->isNotEmpty() || $perizinanEvidences->where('status', 'rejected')->isNotEmpty()) {
            return back()->with('error', 'Perbaiki dulu eviden yang ditolak (upload ulang) sebelum melanjutkan.');
        }

        $lop->advanceStage('instalasi', auth()->id());

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'stage_transition',
            'title' => 'Persiapan Instalasi Selesai',
            'description' => 'Waspang menyelesaikan Step 2 Persiapan Instalasi (Barang Tiba & Perizinan), lanjut ke Step 3 Instalasi.',
            'status_before' => 'persiapan_instalasi',
            'status_after' => 'instalasi',
            'stage' => 'persiapan_instalasi',
        ]);

        return redirect()->route('waspang.projects.instalasi', $project->id_project)
            ->with('success', 'Step 2 Persiapan Instalasi selesai. Lanjut ke Step 3 Instalasi.');
    }

    /**
     * Tombol "Update Kronologi" UNIVERSAL -- muncul di setiap
     * step/sub-step (lihat waspang/partials/kronologi-modal.blade.php),
     * tag stage_code diisi otomatis oleh JS sesuai konteks step yg sedang
     * dibuka saat tombol ditekan.
     */
    public function storeKronologi(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);
        $lop = $project->lop;

        abort_if(! $lop, 404, 'LOP belum tersedia untuk project ini.');

        $request->validate([
            'stage_code' => 'required|string|max:50',
            'event_date' => 'required|date',
            'note' => 'required|string|max:2000',
        ]);

        $kronologi = LopKronologi::create([
            'lop_id' => $lop->id_lop,
            'project_id' => $project->id_project,
            'stage_code' => $request->stage_code,
            'event_date' => $request->event_date,
            'note' => $request->note,
            'created_by' => auth()->user()->id_user,
        ]);

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lop->id_lop,
            'activity_type' => 'update_kronologi',
            'title' => 'Update Kronologi',
            'description' => 'Waspang menambahkan kronologi ('.$request->stage_code.'): '.Str::limit($request->note, 150),
            'stage' => $request->stage_code,
            'meta' => [
                'kronologi_id' => $kronologi->id,
                'event_date' => $request->event_date,
            ],
        ]);

        return back()->with('success', 'Kronologi berhasil disimpan.');
    }

    public function finishing($id)
    {
        $project = Project::with([
            'evidences',
            'boqItems',
            'lop.stage',
        ])->findOrFail($id);

        return view('waspang.steps.finishing', compact('project'));
    }

    public function reviewFinal($id)
    {
        // 1. Validasi hak akses penugasan waspang
        $project = $this->getAssignedProject($id);

        // 2. Load ulang relasi secara eksplisit untuk menjamin keakuratan data
        $project->load([
            'lop',
            'boqItems' => function ($query) {
                // Ikut sertakan data master designator untuk mendapatkan nama, unit, dan tipe
                $query->with('designatorData');
            },
            'evidences' => function ($query) {
                $query->where('status', 'approved');
            },
        ]);

        // 3. Pisahkan item berdasarkan arsitektur modul Anda (KPI vs Non-KPI / Material)
        $boqItems = $project->boqItems ?? collect();

        // Section AQ (permintaan user): pembanding Review BOQ Final BUKAN lagi
        // selalu BOQ Plan -- prioritas BOQ Survey TERBARU (quantity_survey,
        // kolom ini SELALU disinkronkan ke ronde survey terakhir, lihat
        // ensureBaselineSurveyRound()/updateSurveyBoq()), fallback ke BOQ
        // Plan (quantity_plan) HANYA kalau item itu tidak punya data Survey
        // sama sekali. Item tanpa Plan MAUPUN Survey tetap dikecualikan
        // (tidak ada dasar pembanding sama sekali).
        $materialBoqItems = $boqItems->filter(function ($boq) {
            return ($boq->quantity_survey !== null || $boq->quantity_plan !== null)
                && (str_starts_with($boq->designator, 'M-')
                    || optional($boq->designatorData)->type === 'material');
        })->values();

        // Lampirkan nilai & sumber pembanding per item (atribut transient,
        // TIDAK disimpan ke DB) supaya view tidak perlu hitung ulang & tetap
        // konsisten dengan summary di bawah.
        $materialBoqItems->each(function ($item) {
            $item->compare_qty = $item->quantity_survey ?? $item->quantity_plan;
            $item->compare_source = $item->quantity_survey !== null ? 'survey' : 'plan';
        });

        // 4. Hitung ringkasan akumulasi total untuk widget pencapaian di atas halaman
        $summary = [
            'total_items' => $materialBoqItems->count(),
            'total_plan' => $materialBoqItems->sum('compare_qty'),
            'total_actual' => $materialBoqItems->sum('quantity_actual'),
            'matched' => $materialBoqItems->filter(function ($item) {
                return (float) $item->quantity_actual >= (float) $item->compare_qty;
            })->count(),
        ];

        // 5. Lempar ke view review komparasi khusus mobile
        return view('waspang.steps.review-final', compact('project', 'materialBoqItems', 'summary'));
    }

    /**
     * Ambiguitas LOP harus dihentikan sebelum ada mutasi Survey. Ini adalah
     * pengaman sementara sampai seluruh route lama dipindah dari project_id
     * ke lop_id secara eksplisit.
     */
    private function getSingleSurveyLop(Project $project): Lop
    {
        $lops = $project->relationLoaded('lops')
            ? $project->lops
            : $project->lops()->limit(2)->get();

        abort_if($lops->isEmpty(), 404, 'LOP belum tersedia untuk project ini.');
        abort_if(
            $lops->count() !== 1,
            409,
            'Project memiliki lebih dari satu LOP. Pilih LOP secara eksplisit sebelum menjalankan Survey.'
        );

        return $lops->first();
    }

    private function surveyMapVersions(Project $project)
    {
        $versions = collect();
        $knownPaths = [];

        $adminMapLogs = ProjectActivityLog::query()
            ->where('project_id', $project->id_project)
            ->whereIn('activity_type', ['survey_admin_kml_archived', 'survey_admin_kml_uploaded'])
            ->oldest('id_project_activity')
            ->get();

        foreach ($adminMapLogs as $log) {
            $path = $log->meta['kml_path'] ?? null;

            if (! $path || isset($knownPaths[$path]) || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $knownPaths[$path] = true;
            $versions->push([
                'key' => 'admin:'.sha1($path),
                'label' => 'Desain Admin',
                'source' => 'admin',
                'url' => Storage::url($path),
                'created_at' => $log->created_at?->toIso8601String(),
                'sort_at' => $log->created_at?->getTimestamp() ?? 0,
            ]);
        }

        if ($project->kml_file && ! isset($knownPaths[$project->kml_file]) && Storage::disk('public')->exists($project->kml_file)) {
            $versions->push([
                'key' => 'admin:'.sha1($project->kml_file),
                'label' => 'Desain Admin',
                'source' => 'admin',
                'url' => Storage::url($project->kml_file),
                'created_at' => $project->created_at?->toIso8601String(),
                'sort_at' => $project->created_at?->getTimestamp() ?? 0,
            ]);
        }

        $redesignNumber = 0;
        $completedSurveys = SiteSurvey::query()
            ->where('project_id', $project->id_project)
            ->where('status', 'completed')
            ->oldest('completed_at')
            ->oldest('id_site_surveys')
            ->get();

        foreach ($completedSurveys as $survey) {
            $redesignNumber++;
            $versions->push([
                'key' => 'redesign:'.$survey->id,
                'label' => 'Redesign '.$redesignNumber,
                'source' => 'redesign',
                'url' => route('surveyor.kml', $survey->id),
                'created_at' => $survey->completed_at?->toIso8601String(),
                'sort_at' => $survey->completed_at?->getTimestamp() ?? $survey->id,
            ]);
        }

        $versions = $versions->sortBy('sort_at')->values();
        $adminTotal = $versions->where('source', 'admin')->count();
        $adminNumber = 0;

        return $versions->map(function (array $version) use ($adminTotal, &$adminNumber): array {
            if ($version['source'] === 'admin' && $adminTotal > 1) {
                $adminNumber++;
                $version['label'] = 'Desain Admin '.$adminNumber;
            }

            unset($version['sort_at']);

            return $version;
        });
    }

    private function isSurveyMapConfirmed(Project $project, Lop $lop, string $mapKey): bool
    {
        $confirmation = ProjectActivityLog::query()
            ->where('project_id', $project->id_project)
            ->where('lop_id', $lop->id_lop)
            ->where('activity_type', 'survey_map_confirmed')
            ->latest('id_project_activity')
            ->first();

        return ($confirmation?->meta['map_key'] ?? null) === $mapKey;
    }

    private function currentSurveyMapIsConfirmed(Project $project, Lop $lop): bool
    {
        $currentMap = $this->surveyMapVersions($project)->last();

        return $currentMap
            ? $this->isSurveyMapConfirmed($project, $lop, $currentMap['key'])
            : false;
    }

    private function currentSurveyBoqGroups(Project $project, Lop $lop, SurveyPreparationService $surveyPreparation)
    {
        return $surveyPreparation->groupBoqItems(
            BoqItem::with(['designatorData', 'designatorDataByCode'])
                ->where('project_id', $project->id_project)
                ->where('lop_id', $lop->id_lop)
                ->get()
        );
    }

    private function persistSurveyVolumes($groups, array $submittedVolumes, bool $requireAll): array
    {
        $saved = [];

        foreach ($groups as $group) {
            $field = (string) $group['representative_id'];
            $hasValue = array_key_exists($field, $submittedVolumes)
                && $submittedVolumes[$field] !== null
                && $submittedVolumes[$field] !== '';

            if ($requireAll && ! $hasValue) {
                throw ValidationException::withMessages([
                    "volumes.{$field}" => 'Volume Survey wajib diisi.',
                ]);
            }

            if (! $hasValue) {
                continue;
            }

            $value = (float) $submittedVolumes[$field];
            BoqItem::query()
                ->whereIn('id_boq', $group['item_ids'])
                ->update(['quantity_survey' => $value]);
            $saved[$field] = $value;
        }

        return $saved;
    }

    // WASPANG STAGE HELPER PRIVATE
    private function getAssignedProject($id)
    {
        $userId = auth()->user()->id_user;

        $isAssigned = ProjectAssignment::where('project_id', $id)
            ->where('waspang_id', $userId)
            ->exists();

        abort_if(! $isAssigned, 403);

        return Project::with(['boqItems.designatorData', 'boqItems.designatorDataByCode', 'evidences', 'lop.stage', 'lops.stage'])
            ->findOrFail($id);
    }

    // HELPER
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
        // Revisi stepper (Step 2 "Persiapan Instalasi" kini halaman sendiri,
        // BUKAN pass-through lagi): gerbang ke halaman Instalasi (Step 3)
        // sekarang baru terbuka begitu LOP BENAR-BENAR sudah di 'instalasi'
        // atau lebih (sequence > 6) -- bukan lagi cuma sequence > 5
        // (persiapan_instalasi), krn sequence 6 sekarang py syarat sendiri
        // (2 eviden Barang Tiba & Perizinan, lihat
        // WaspangController::finishPersiapanInstalasi()). Fallback ke 2
        // eviden lama HANYA utk LOP lama sebelum flow baru ini / kode tak
        // dikenal.
        $lopStageSequence = Lop::where('project_id', $projectId)
            ->first()?->stage?->sequence;

        if ($lopStageSequence !== null && $lopStageSequence > 6) {
            return true;
        }

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

        // Section AD: item acuan approval Instalasi DISAMAKAN dgn item yang
        // ditampilkan di Step 3 (ronde BOQ Survey terbaru kalau ada, else
        // BOQ Plan) -- lihat Project::materialProgressItems().
        $materialBoqItems = $project->materialProgressItems()['items'];

        $boqTotal = $materialBoqItems->count();

        if ($boqTotal == 0) {
            return false;
        }

        $boqApproved = $materialBoqItems->filter(function ($boq) use ($evidences) {
            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->where('status', 'approved')
                ->count() > 0;
        })->count();

        return $boqApproved == $boqTotal;
    }

    // UPLOAD FOTO di FOLDER
    public function uploadEvidence(Request $request, $id)
    {
        $project = $this->getAssignedProject($id);

        $request->validate([
            // Sub-step Persiapan (Survey/Perizinan/Material Delivery) tidak
            // lagi menumpang di
            // stage='persiapan' generik, masing2 sub-step punya stage sendiri
            // -- lihat catatan sequence-based di Project::progressSummary()).
            'stage' => 'required|in:persiapan,instalasi,pengukuran,finishing,survey,perizinan,material_delivery',
            'evidence_type' => 'required|string|max:100',
            'photos' => 'required|array',
            'photos.*' => 'required|file|max:10240', // Max 10MB
            'description' => 'nullable|string',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'boq_item_id' => 'nullable',
            'quantity_actual' => 'nullable|numeric|min:0',
            'actual_reason' => 'nullable|string',
        ]);

        $projectFolder = $this->evidenceLopFolder($project->id_project);
        $stage = $request->stage;
        $type = $request->evidence_type;
        $lopId = Lop::where('project_id', $project->id_project)->value('id_lop');

        foreach ($request->file('photos') as $photo) {

            $originalExtension = strtolower($photo->getClientOriginalExtension());
            $isSor = ($originalExtension === 'sor');
            // Jika file dari JS (blob) kadang tidak punya ekstensi, kita default ke jpg
            $extension = $isSor ? 'sor' : ($originalExtension ?: 'jpg');

            $filename = now()->format('Ymd_His').'_'.uniqid().'.'.$extension;

            // Simpan ke direktori terstruktur
            $path = $photo->storeAs(
                "evidences/{$projectFolder}/{$stage}/{$type}",
                $filename,
                'public'
            );

            $evidence = Evidence::create([
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

            $evidence->load('boqItem');
            $evidenceLabel = $this->evidenceLabel($evidence);

            ProjectActivityService::log([
                'project_id' => $evidence->project_id,
                'lop_id' => $lopId,
                'evidence_id' => $evidence->id_evidence,
                'activity_type' => 'upload_evidence',
                'title' => 'Upload Eviden',
                'description' => 'Waspang upload eviden: '.$evidenceLabel.($isSor ? ' [File SOR]' : ''),
                'stage' => $evidence->stage,
                'status_after' => 'pending',
                'meta' => [
                    'evidence_type' => $evidence->evidence_type,
                    'boq_item_id' => $evidence->boq_item_id,
                    'boq_designator' => $evidence->boqItem?->designator,
                    'boq_item_name' => $evidence->boqItem?->item_name,
                    'file_path' => $evidence->file_path,
                    'latitude' => $evidence->latitude,
                    'longitude' => $evidence->longitude,
                ],
            ]);
        }

        // UPDATE QUANTITY ACTUAL & REASON
        if ($request->boq_item_id) {
            $boqItem = BoqItem::where('id_boq', $request->boq_item_id)->first();

            if ($boqItem) {
                $designator = DB::table('designators')
                    ->where('id_designator', $boqItem->designator_id)
                    ->first();

                if ($request->has('quantity_actual') && $request->quantity_actual !== null) {

                    $actualValue = (float) $request->quantity_actual;

                    $updateData = [
                        'quantity_actual' => $actualValue,
                    ];

                    if ($actualValue == 0 && $request->has('actual_reason')) {
                        $updateData['actual_reason'] = $request->actual_reason;
                    } else {
                        $updateData['actual_reason'] = null;
                    }

                    BoqItem::where('id_boq', $request->boq_item_id)
                        ->update($updateData);

                    ProjectActivityService::log([
                        'project_id' => $project->id_project,
                        'lop_id' => $lopId,
                        'activity_type' => 'update_quantity_actual',
                        'title' => 'Update Kuantitas Aktual',
                        'description' => 'Waspang mengupdate kuantitas aktual item: '.($designator->designator ?? '').' menjadi '.$actualValue,
                        'stage' => $stage,
                        'status_after' => 'updated',
                        'meta' => [
                            'boq_item_id' => $request->boq_item_id,
                            'quantity_actual' => $actualValue,
                            'actual_reason' => $updateData['actual_reason'],
                        ],
                    ]);
                } else {
                    ProjectActivityService::log([
                        'project_id' => $project->id_project,
                        'lop_id' => $lopId,
                        'activity_type' => 'upload_evidence_regular',
                        'title' => 'Upload Eviden Pendukung',
                        'description' => 'Waspang mengupload foto tambahan untuk item: '.($designator->designator ?? ''),
                        'stage' => $stage,
                        'status_after' => 'pending',
                        'meta' => [
                            'boq_item_id' => $request->boq_item_id,
                            'info' => 'Upload foto tanpa mengubah quantity',
                        ],
                    ]);
                }
            }
        }

        // WEBHOOK EVENT: kalau seluruh eviden wajib tahap ini sudah lengkap
        // diunggah (menunggu review), publish event SEKALI ke admin yang meng-assign.
        $this->publishStageSubmittedEvent($project, $stage);

        return back()->with('success', 'Eviden berhasil diunggah');
    }

    /**
     * Setelah upload eviden pada suatu stage, cek apakah SEMUA jenis eviden wajib
     * untuk stage tsb sudah diunggah (status apapun selain rejected -- artinya
     * sedang menunggu review admin), lalu publish event webhook SEKALI ke admin
     * yang meng-assign waspang ini ke project tsb. Ini TIDAK menunggu approval
     * admin -- itu urusan terpisah (lihat ProjectController::approveEvidence).
     */
    private function publishStageSubmittedEvent($project, string $stage): void
    {
        $isComplete = match ($stage) {
            'persiapan' => $this->stageHasSubmittedTypes($project->id_project, 'persiapan', ['barang_tiba', 'perizinan']),
            'pengukuran' => $this->stageHasSubmittedTypes($project->id_project, 'pengukuran', ['opm', 'otdr']),
            'instalasi' => $this->instalasiSubmittedComplete($project),
            'finishing' => Evidence::where('project_id', $project->id_project)
                ->where('stage', 'finishing')
                ->where('status', '!=', 'rejected')
                ->exists(),
            default => false,
        };

        if (! $isComplete) {
            return;
        }

        // Guard supaya tidak berulang kali publish event yang sama untuk stage yang sama.
        $alreadyPublished = ProjectActivityLog::where('project_id', $project->id_project)
            ->where('stage', $stage)
            ->where('activity_type', 'webhook_stage_uploaded_published')
            ->exists();

        if ($alreadyPublished) {
            return;
        }

        $assignment = ProjectAssignment::where('project_id', $project->id_project)->first();
        $admin = $assignment?->admin;

        if ($admin) {
            TelegramWebhookEventService::publishToUser(
                $admin,
                'evidence_step_uploaded',
                'Eviden Tahap Selesai Diupload',
                'Waspang '.(auth()->user()->name ?? '-')." telah menyelesaikan upload eviden tahap {$stage} untuk project {$project->project_name} (".($project->pid ?? '-').'). Eviden menunggu review Anda.',
                [
                    'stage' => $stage,
                    'project_name' => $project->project_name,
                    'pid' => $project->pid,
                    'uploader_name' => auth()->user()->name ?? null,
                    'uploader_role' => 'waspang',
                ],
                ['project_id' => $project->id_project]
            );
        }

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'activity_type' => 'webhook_stage_uploaded_published',
            'title' => 'Webhook Event: Eviden Tahap Lengkap',
            'description' => "Event webhook dipublish utk admin: eviden tahap {$stage} sudah lengkap diupload.",
            'stage' => $stage,
        ]);
    }

    /**
     * Cek apakah SEMUA evidence_type wajib pada $requiredTypes sudah punya minimal
     * satu baris eviden (status apapun selain rejected) untuk project+stage tsb.
     */
    private function stageHasSubmittedTypes(int $projectId, string $stage, array $requiredTypes): bool
    {
        $submittedTypes = Evidence::where('project_id', $projectId)
            ->where('stage', $stage)
            ->where('status', '!=', 'rejected')
            ->pluck('evidence_type')
            ->unique();

        foreach ($requiredTypes as $type) {
            if (! $submittedTypes->contains($type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Instalasi dianggap "selesai diupload" kalau setiap item BOQ material (designator
     * berawalan "M-", sama seperti Project::progressSummary()) sudah punya minimal satu
     * eviden progress_boq yang tidak rejected.
     */
    private function instalasiSubmittedComplete($project): bool
    {
        // quantity_plan !== null WAJIB (bag. AC): item tambahan BOQ Survey
        // bukan bagian checklist progress Instalasi.
        $materialIds = BoqItem::where('project_id', $project->id_project)
            ->where('designator', 'like', 'M-%')
            ->whereNotNull('quantity_plan')
            ->pluck('id_boq');

        if ($materialIds->isEmpty()) {
            return false;
        }

        $submittedBoqIds = Evidence::where('project_id', $project->id_project)
            ->where('stage', 'instalasi')
            ->where('evidence_type', 'progress_boq')
            ->where('status', '!=', 'rejected')
            ->pluck('boq_item_id')
            ->unique();

        foreach ($materialIds as $boqId) {
            if (! $submittedBoqIds->contains($boqId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Nama folder penyimpanan eviden berdasarkan NAMA LOP (bukan ID project),
     * supaya lebih mudah dicari manual di storage/app/public/evidences.
     * Fallback ke "project-{id}" kalau LOP belum ada / nama LOP kosong.
     */
    private function evidenceLopFolder(int $projectId): string
    {
        $lopName = Lop::where('project_id', $projectId)->value('lop_name');

        return $this->sanitizeFolderName($lopName, 'project-'.$projectId);
    }

    /**
     * Bersihkan nama LOP supaya aman dipakai sebagai nama folder (hilangkan
     * karakter path separator dkk), tapi tetap pertahankan spasi/huruf/angka
     * agar nama LOP masih mudah dibaca saat dicari manual.
     */
    private function sanitizeFolderName(?string $name, string $fallback): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return $fallback;
        }

        $safe = preg_replace('/[\/\\\\:*?"<>|]+/', '_', $name);
        $safe = trim($safe, ' ._');

        return $safe !== '' ? $safe : $fallback;
    }

    public function replace(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Maks 10MB
        ]);

        $evidence = Evidence::findOrFail($id);
        $projectFolder = $this->evidenceLopFolder($evidence->project_id);
        $stage = $evidence->stage;
        $type = $evidence->evidence_type;

        // 1. Hapus file fisik lama dari storage
        if ($evidence->file_path && Storage::disk('public')->exists($evidence->file_path)) {
            Storage::disk('public')->delete($evidence->file_path);
        }

        // 2. Siapkan file baru
        $file = $request->file('file');

        $originalExtension = strtolower($file->getClientOriginalExtension());
        $isSor = ($originalExtension === 'sor');
        // File dari JS kompresi (blob) akan dibaca tanpa ekstensi, jadikan default jpg.
        $extension = $isSor ? 'sor' : ($originalExtension ?: 'jpg');

        $filename = now()->format('Ymd_His').'_replace_'.uniqid().'.'.$extension;

        // 3. Simpan di direktori yang sama dengan tempat file lama bersarang
        $path = $file->storeAs(
            "evidences/{$projectFolder}/{$stage}/{$type}",
            $filename,
            'public'
        );

        // 4. Update database (Pastikan review_note masuk $fillable ya di Model Evidence)
        $evidence->file_path = $path;
        $evidence->status = 'pending';
        $evidence->review_note = null; // Menghapus alasan reject karena sudah diunggah ulang
        $evidence->save();

        // 5. Catat Log Aktivitas (Opsional agar History Rapi)
        ProjectActivityService::log([
            'project_id' => $evidence->project_id,
            'lop_id' => Lop::where('project_id', $evidence->project_id)->value('id_lop'),
            'evidence_id' => $evidence->id_evidence,
            'activity_type' => 'replace_evidence',
            'title' => 'Upload Ulang Eviden (Perbaikan)',
            'description' => 'Waspang mengunggah ulang eviden yang ditolak (ID-'.$evidence->id_evidence.').',
            'stage' => $evidence->stage,
            'status_after' => 'pending',
            'meta' => [
                'evidence_type' => $evidence->evidence_type,
                'boq_item_id' => $evidence->boq_item_id,
            ],
        ]);

        return back()->with('success', 'Eviden berhasil diperbarui. Status kembali pending.');
    }

    /**
     * Hapus satu foto eviden milik Waspang. Dipakai oleh tombol "×" di pojok
     * foto pada halaman persiapan/pengukuran/instalasi/finishing.
     */
    public function deleteEvidence($id)
    {
        $evidence = Evidence::findOrFail($id);

        // Eviden yang sudah approved tidak boleh dihapus (samakan dengan guard
        // di tampilan: @if($photo->status != 'approved')).
        if ($evidence->status === 'approved') {
            return back()->with('error', 'Eviden yang sudah disetujui tidak bisa dihapus.');
        }

        // 1. Hapus file fisik dari storage
        if ($evidence->file_path && Storage::disk('public')->exists($evidence->file_path)) {
            Storage::disk('public')->delete($evidence->file_path);
        }

        $projectId = $evidence->project_id;
        $stage = $evidence->stage;
        $evidenceId = $evidence->id_evidence;
        $evidenceType = $evidence->evidence_type;
        $boqItemId = $evidence->boq_item_id;

        // 2. Catat Log Aktivitas SEBELUM baris eviden dihapus, supaya evidence_id
        //    pada log masih menunjuk ke baris yang valid saat dicatat.
        ProjectActivityService::log([
            'project_id' => $projectId,
            'lop_id' => Lop::where('project_id', $projectId)->value('id_lop'),
            'evidence_id' => $evidenceId,
            'activity_type' => 'delete_evidence',
            'title' => 'Hapus Eviden',
            'description' => 'Waspang menghapus eviden (ID-'.$evidenceId.').',
            'stage' => $stage,
            'status_after' => null,
            'meta' => [
                'evidence_type' => $evidenceType,
                'boq_item_id' => $boqItemId,
            ],
        ]);

        // 3. Hapus baris database
        $evidence->delete();

        return back()->with('success', 'Foto eviden berhasil dihapus.');
    }

    // NOTIFICATION
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

    // UPDATE KENDALA
    public function storeIssue(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);

        $request->validate([
            // Stage 4d: tombol kendala BARU (di tiap sub-step Persiapan) pakai
            // dropdown master KendalaCategory -- tapi form kendala LAMA (lihat
            // waspang/inbox.blade.php) masih kirim `issue_type` bebas tanpa
            // `kendala_category_id` sama sekali. Supaya keduanya tetap jalan
            // tanpa saling mematahkan, wajibkan SALAH SATU dari keduanya
            // (bukan keduanya wajib).
            'kendala_category_id' => 'nullable|exists:kendala_categories,id',
            'issue_type' => 'nullable|string|max:100',
            // Stage 4d: tag opsional -- dari sub-step/step mana kendala ini
            // dilaporkan (survey/drm/perizinan/material_delivery/persiapan/
            // instalasi/pengukuran/finishing/dst). Nullable supaya tombol
            // kendala lama (tanpa konteks step) tetap jalan apa adanya.
            'stage_code' => 'nullable|string|max:50',
            'description' => 'required|string|max:2000',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if (! $request->kendala_category_id && ! $request->issue_type) {
            return back()->with('error', 'Pilih jenis/kategori kendala terlebih dahulu.');
        }

        $kendalaCategory = $request->kendala_category_id
            ? KendalaCategory::find($request->kendala_category_id)
            : null;

        // issue_type (kolom lama, teks bebas) diisi dari nama kategori kalau
        // kendala_category_id yg dikirim (tombol baru), atau langsung dari
        // input issue_type kalau form lama yg dipakai.
        $issueTypeValue = $kendalaCategory->name ?? $request->issue_type;

        $lopId = Lop::where('project_id', $project->id_project)->value('id_lop');

        $photoPaths = [];

        if ($request->hasFile('photos')) {
            $projectFolder = 'project-'.$project->id_project;

            foreach ($request->file('photos') as $photo) {
                $filename = now()->format('Ymd_His').'_'.uniqid().'.jpg';

                $path = $photo->storeAs(
                    "issues/{$projectFolder}",
                    $filename,
                    'public'
                );

                $photoPaths[] = $path;
            }
        }

        $issue = ProjectIssue::create([
            'project_id' => $project->id_project,
            'lop_id' => $lopId,
            'stage_code' => $request->stage_code,
            'kendala_category_id' => $request->kendala_category_id,
            'user_id' => auth()->user()->id_user,
            'issue_type' => $issueTypeValue,
            'description' => $request->description,
            'photo_paths' => $photoPaths,
            'status' => 'kendala',
        ]);

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $lopId,
            'activity_type' => 'update_kendala',
            'title' => 'Update Kendala',
            'description' => 'Waspang melaporkan kendala: '.$request->description,
            'status_after' => 'kendala',
            'meta' => [
                'issue_id' => $issue->id,
                'issue_type' => $issue->issue_type,
                'photo_paths' => $photoPaths,
            ],
        ]);

        $admins = User::roleCode(['admin', 'pm'])->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id_user,
                'project_id' => $project->id_project,
                'type' => 'kendala',
                'title' => 'Kendala Baru dari Waspang',
                'message' => 'Project '.$project->project_name.' terkendala: '.$request->description,
                'redirect_url' => route('admin.projects.tracking', $project->id_project),
            ]);
        }

        return back()->with('success', 'Kendala berhasil dikirim. Admin/PM sudah menerima notifikasi.');
    }

    public function resumeIssue(Request $request, $project)
    {
        $project = $this->getAssignedProject($project);

        $issue = ProjectIssue::where('project_id', $project->id_project)
            ->where('user_id', auth()->user()->id_user)
            ->where('status', 'kendala')
            ->latest()
            ->first();

        if (! $issue) {
            return back()->with('error', 'Tidak ada kendala aktif pada project ini.');
        }

        $issue->update([
            'status' => 'open',
            'resolution_note' => 'Waspang Resume Project dan lanjut upload eviden.',
        ]);

        ProjectActivityService::log([
            'project_id' => $project->id_project,
            'lop_id' => $issue->lop_id,
            'activity_type' => 'resume_project',
            'title' => 'Project Resume',
            'description' => 'Waspang melanjutkan project setelah update kendala.',
            'status_before' => 'kendala',
            'status_after' => 'open',
            'meta' => [
                'issue_id' => $issue->id_project_issues,
            ],
        ]);

        return back()->with('success', 'Project berhasil di-resume. Silakan lanjut upload eviden.');
    }

    public function profile()
    {
        return view('waspang.profile');
    }

    private function evidenceLabel($evidence)
    {
        $typeLabels = [
            'barang_tiba' => 'Barang Tiba',
            'perizinan' => 'Perizinan',
            'progress_boq' => 'Progress BOQ',
            'otdr' => 'OTDR',
            'opm' => 'OPM',
            'kedalaman' => 'Kedalaman Galian',
            'finishing' => 'Finishing',
        ];

        $stageLabel = ucfirst($evidence->stage ?? '-');
        $typeLabel = $typeLabels[$evidence->evidence_type] ?? ucfirst(str_replace('_', ' ', $evidence->evidence_type));

        if ($evidence->boqItem) {
            return $stageLabel.' | '.
                $typeLabel.' | '.
                ($evidence->boqItem->designator ?? '-').' - '.
                ($evidence->boqItem->item_name ?? '-');
        }

        return $stageLabel.' | '.$typeLabel;
    }
}
