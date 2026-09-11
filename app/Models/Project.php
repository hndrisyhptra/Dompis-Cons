<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';

    protected $primaryKey = 'id_project';

    protected $fillable = [
        'pid',
        'customer_id',
        'pid_sap',
        'project_name',
        'program',
        'branch',
        'sto',
        'mitra_name',
        'kml_file',
        'execution_type',
        'latitude',
        'longitude',
        'location_address',
        'map_note',
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if ($project->customer_id) {
                return;
            }

            $project->customer_id = Customer::where('customer_code', 'TIF')
                ->value('id_customer');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function boqItems()
    {
        return $this->hasMany(BoqItem::class, 'project_id', 'id_project');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id_customer');
    }

    public function assignment()
    {
        return $this->hasOne(
            ProjectAssignment::class,
            'project_id',
            'id_project'
        );
    }

    public function assignments()
    {
        return $this->hasMany(
            ProjectAssignment::class,
            'project_id',
            'id_project'
        );
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'project_id', 'id_project');
    }

    public function lop()
    {
        return $this->hasOne(Lop::class, 'project_id', 'id_project');
    }

    /**
     * Relasi kanonik untuk data baru. Relasi lop() dipertahankan sementara
     * untuk kompatibilitas halaman lama yang masih mengasumsikan 1 proyek
     * hanya memiliki 1 LOP.
     */
    public function lops()
    {
        return $this->hasMany(Lop::class, 'project_id', 'id_project');
    }

    protected ?array $progressSummaryCache = null;

    /**
     * FIX (2026-09-08, Stage 2 refactor "flow 11-tahap"): sebelumnya
     * `pengukuranDone` cuma alias dari `instalasiDone` ("Step 3 tidak wajib
     * upload") -- tidak ada pengecekan independen sama sekali. Sekarang
     * `pengukuranDone` dihitung dari 2 sumber:
     *   1. `lops.status_progress` (via project_stages.sequence) -- kalau LOP
     *      sudah berada di tahap SETELAH Pengukuran (Finishing/FI-OGP
     *      Golive/Golive), otomatis dianggap sudah lewat.
     *   2. Kalau LOP masih di tahap Pengukuran (atau sebelumnya), cek
     *      langsung ke `lop_measurement_checks` -- beres kalau kelima item
     *      (OTDR/File SOR/OPM/Kedalaman Galian/Eviden Lainnya) sudah
     *      "beres" (ada eviden ATAU ditandai "tidak ada").
     * `progress` (persentase) sekarang JUGA diambil dari urutan
     * `status_progress` (project_stages.sequence, 1-11) sebagai sumber utama
     * -- bukan lagi dari 4 boolean lama -- supaya konsisten dengan stepper
     * 11-tahap yang baru. Kalau LOP sedang HOLD/DROP, posisi yang dipakai
     * untuk progress bar adalah posisi SEBELUM di-hold/drop
     * (`status_progress_before_hold`) supaya progress tidak "hilang" cuma
     * karena sedang dijeda/dibatalkan sementara (drop TETAP bisa di-reset,
     * lihat catatan di migration project_stages).
     *
     * 4 boolean lama (persiapanDone/instalasiDone/pengukuranDone/
     * finishingDone) TETAP dipertahankan apa adanya sebagai key balikan,
     * karena masih dipakai beberapa view lama (project-card.blade.php dkk)
     * untuk 4 pil indikator warna. Kalau suatu saat view itu di-upgrade ke
     * stepper 11-tahap penuh (Stage 3-4 refactor), key baru di bagian bawah
     * (stageCode/stageSequence/phaseGroup/isHold/isDrop) sudah disiapkan.
     */
    /**
     * Section AD: sumber item Material (checklist Step 3 Instalasi & Step 5
     * Finishing). Dulu SELALU BOQ Plan (`quantity_plan !== null`, bag. AC).
     * Sekarang, kalau LOP sudah punya minimal 1 ronde BOQ Survey yang
     * SELESAI (`boq_survey_rounds.status = 'completed'`), item & angka
     * acuannya dipindah ke snapshot RONDE TERBARU
     * (`boq_survey_round_items`) -- karena itu revisi resmi hasil Survey
     * lapangan (termasuk kemungkinan re-design dari fitur Re Survey).
     * Kalau LOP belum pernah punya ronde Survey selesai sama sekali,
     * fallback ke BOQ Plan murni seperti sebelumnya.
     *
     * PENTING: setiap baris hasil TETAP objek BoqItem yang mengacu ke
     * id_boq ASLI (boq_items.id_boq) -- evidence upload (evidences.
     * boq_item_id) & seluruh gate approval TIDAK BERUBAH. Yang berubah
     * HANYA (a) daftar item mana yang dianggap "berlaku" hari ini, dan
     * (b) `quantity_plan` di objek clone-nya ditimpa pakai
     * `quantity_survey` ronde terbaru (dikonfirmasi user) supaya label
     * "Plan" yang dibaca semua view Instalasi/Finishing otomatis ikut
     * angka Survey tanpa perlu ubah blade satu-satu.
     *
     * @return array{items: \Illuminate\Support\Collection<int, BoqItem>, source: 'survey_round'|'plan', round: ?BoqSurveyRound}
     */
    public function materialProgressItems(): array
    {
        $this->loadMissing([
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
            'lop',
        ]);

        $lop = $this->lop;

        $latestRound = $lop
            ? BoqSurveyRound::where('lop_id', $lop->id_lop)
                ->where('status', 'completed')
                ->orderByDesc('round_number')
                ->first()
            : null;

        if ($latestRound) {
            $items = BoqSurveyRoundItem::where('boq_survey_round_id', $latestRound->id)
                ->whereNotNull('boq_item_id')
                ->with(['boqItem.designatorData', 'boqItem.designatorDataByCode'])
                ->get()
                ->filter(function (BoqSurveyRoundItem $roundItem) {
                    $boq = $roundItem->boqItem;

                    if (! $boq) {
                        // Item asli sudah dihapus sejak ronde ini -- skip
                        // drpd nunjukin baris yatim yang tidak bisa di-upload.
                        return false;
                    }

                    return str_starts_with((string) $roundItem->designator, 'M-')
                        || optional($boq->designatorData)->type === 'material'
                        || optional($boq->designatorDataByCode)->type === 'material';
                })
                ->map(function (BoqSurveyRoundItem $roundItem) {
                    /** @var BoqItem $boq */
                    $boq = clone $roundItem->boqItem;
                    // Angka acuan "Plan" yang dibaca view = quantity_survey
                    // ronde terbaru (keputusan user, lihat ANALISA_REFACTOR_
                    // PERSIAPAN.md bag. AD). Ini cuma objek clone in-memory,
                    // TIDAK disimpan ke DB.
                    $boq->quantity_plan = $roundItem->quantity_survey;

                    return $boq;
                })
                ->values();

            return [
                'items' => $items,
                'source' => 'survey_round',
                'round' => $latestRound,
            ];
        }

        $items = ($this->boqItems ?? collect())->filter(function (BoqItem $boq) {
            return $boq->quantity_plan !== null
                && (str_starts_with((string) $boq->designator, 'M-')
                    || optional($boq->designatorData)->type === 'material');
        })->values();

        return [
            'items' => $items,
            'source' => 'plan',
            'round' => null,
        ];
    }

    /**
     * Section AD: versi "sudah di-upload" (status apapun -- pending maupun
     * approved) dari flag persiapanDone/instalasiDone/pengukuranDone/
     * finishingDone di atas, yang SEMUANYA baru true setelah admin approve
     * semua eviden. Dipakai stepper (stepper.blade.php) utk checklist
     * KUNING ("sudah upload, menunggu admin approve") vs HIJAU (existing
     * $stepNDone -- approved/posisi sequence sudah lewat). Approval TETAP
     * jadi syarat utk status_progress LOP maju ke step berikutnya --
     * flag ini murni indikator visual progres upload Waspang sendiri.
     */
    public function stepUploadFlags(): array
    {
        $this->loadMissing(['evidences', 'lop']);
        $evidences = $this->evidences ?? collect();
        $lop = $this->lop;

        $barangTibaUploaded = $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba')->isNotEmpty();
        $perizinanUploaded = $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan')->isNotEmpty();
        // Step 1 & Step 2 (Persiapan Instalasi) sama2 baca eviden stage=
        // 'persiapan' yg sama (lihat WaspangController::persiapanInstalasi()),
        // jadi flag upload-nya memang identik -- bukan bug baru di sini.
        $persiapanUploaded = $barangTibaUploaded && $perizinanUploaded;

        $material = $this->materialProgressItems();
        $materialItems = $material['items'];

        $instalasiTotal = $materialItems->count();
        $instalasiUploaded = $materialItems->filter(function (BoqItem $boq) use ($evidences) {
            return $evidences->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->isNotEmpty();
        })->count();
        $instalasiUploadedComplete = $instalasiTotal > 0 && $instalasiUploaded >= $instalasiTotal;

        // Section AE: samakan dgn LopMeasurementCheck::ITEMS (5 item -- otdr,
        // file_sor, opm, kedalaman, eviden_lainnya), BUKAN cuma 3 evidence_
        // type lama. Item "sudah diisi Waspang" (kuning) = ada eviden APAPUN
        // statusnya (upload belum tentu di-approve admin) ATAU sudah
        // ditandai "Tidak Ada" (N/A) -- lihat WaspangController::
        // toggleMeasurementCheck(). Alias nama lama (otdr_sor/lainnya)
        // disamakan dgn pengukuran()/toggleMeasurementCheck().
        $legacyAliases = [
            'file_sor' => ['file_sor', 'otdr_sor'],
            'eviden_lainnya' => ['eviden_lainnya', 'lainnya'],
        ];
        $measurementChecks = $lop
            ? LopMeasurementCheck::where('lop_id', $lop->id_lop)->get()->keyBy('item_key')
            : collect();
        $pengukuranUploadedComplete = collect(LopMeasurementCheck::ITEMS)->every(function (string $itemKey) use ($measurementChecks, $evidences, $legacyAliases) {
            if ($measurementChecks->get($itemKey)?->is_not_applicable) {
                return true;
            }

            $typesToCheck = $legacyAliases[$itemKey] ?? [$itemKey];

            return $evidences->where('stage', 'pengukuran')
                ->whereIn('evidence_type', $typesToCheck)
                ->isNotEmpty();
        });

        $finishingRequired = $materialItems->filter(function (BoqItem $boq) {
            return (int) optional($boq->designatorData)->requires_finishing_evidence === 1
                || (int) optional($boq->designatorDataByCode)->requires_finishing_evidence === 1;
        });
        $finishingTotal = $finishingRequired->count();
        $finishingUploaded = $finishingRequired->filter(function (BoqItem $boq) use ($evidences) {
            return $evidences->where('stage', 'finishing')
                ->where('boq_item_id', $boq->id_boq)
                ->isNotEmpty();
        })->count();
        $finishingUploadedComplete = $finishingTotal === 0
            ? $evidences->where('stage', 'finishing')->isNotEmpty()
            : $finishingUploaded >= $finishingTotal;

        return [
            'persiapanUploaded' => $persiapanUploaded,
            'persiapanInstalasiUploaded' => $persiapanUploaded,
            'instalasiUploaded' => $instalasiUploadedComplete,
            'pengukuranUploaded' => $pengukuranUploadedComplete,
            'finishingUploaded' => $finishingUploadedComplete,
        ];
    }

    public function progressSummary(): array
    {
        // Jangan hitung ulang project yang sama dalam request yang sama
        if ($this->progressSummaryCache !== null) {
            return $this->progressSummaryCache;
        }

        // Jika controller sudah eager-load, ini tidak menjalankan query tambahan.
        // Jika belum, Laravel akan mengambil relasi yang dibutuhkan.
        $this->loadMissing([
            'evidences',
            'boqItems.designatorData',
            'boqItems.designatorDataByCode',
            'lop.stage',
        ]);

        $evidences = $this->evidences;
        $boqItems = $this->boqItems;
        $lop = $this->lop;

        /*
        |--------------------------------------------------------------------------
        | 1. Scan Evidence SATU KALI
        |--------------------------------------------------------------------------
        */

        $barangTibaApproved = false;
        $perizinanApproved = false;

        $instalasiStats = [];
        $finishingStats = [];

        foreach ($evidences as $evidence) {

            // Persiapan
            if (
                $evidence->stage === 'persiapan' &&
                $evidence->status === 'approved'
            ) {
                if ($evidence->evidence_type === 'barang_tiba') {
                    $barangTibaApproved = true;
                }

                if ($evidence->evidence_type === 'perizinan') {
                    $perizinanApproved = true;
                }
            }

            $boqItemId = $evidence->boq_item_id;

            if (! $boqItemId) {
                continue;
            }

            $boqKey = (string) $boqItemId;

            // Evidence instalasi
            if (
                $evidence->stage === 'instalasi' &&
                $evidence->evidence_type === 'progress_boq'
            ) {
                if (! isset($instalasiStats[$boqKey])) {
                    $instalasiStats[$boqKey] = [
                        'total' => 0,
                        'approved' => 0,
                    ];
                }

                $instalasiStats[$boqKey]['total']++;

                if ($evidence->status === 'approved') {
                    $instalasiStats[$boqKey]['approved']++;
                }
            }

            // Evidence finishing
            if ($evidence->stage === 'finishing') {
                if (! isset($finishingStats[$boqKey])) {
                    $finishingStats[$boqKey] = [
                        'total' => 0,
                        'approved' => 0,
                    ];
                }

                $finishingStats[$boqKey]['total']++;

                if ($evidence->status === 'approved') {
                    $finishingStats[$boqKey]['approved']++;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Scan BOQ SATU KALI
        |--------------------------------------------------------------------------
        */

        // Section AD: sumber "item Material yang berlaku" DISAMAKAN dgn yang
        // ditampilkan ke Waspang di Step 3 Instalasi/Step 5 Finishing --
        // ronde BOQ Survey terbaru kalau ada, else BOQ Plan (quantity_plan
        // !== null). Sebelumnya loop ini scan $boqItems MENTAH (cuma filter
        // prefix M-, tanpa quantity_plan !== null) -- item tambahan hasil
        // BOQ Survey ikut kehitung di materialTotal walau tidak pernah
        // tampil/diupload di Step 3, jadi instalasiApproved tidak akan
        // pernah capai materialTotal & LOP macet permanen di status_progress
        // 'instalasi' (checklist stepper tidak pernah hijau walau semua
        // item yang BENAR-BENAR tampil sudah di-approve semua).
        $materialItems = $this->materialProgressItems()['items'];

        $materialIds = [];
        $finishingRequiredIds = [];

        foreach ($materialItems as $boq) {

            $boqKey = (string) $boq->id_boq;

            $materialIds[] = $boqKey;

            $requiresFinishing =
                (int) optional($boq->designatorData)->requires_finishing_evidence === 1
                ||
                (int) optional($boq->designatorDataByCode)->requires_finishing_evidence === 1;

            if ($requiresFinishing) {
                $finishingRequiredIds[] = $boqKey;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3b. Posisi LOP di alur 11-tahap (project_stages) -- DIPINDAH ke sini
        |     (sebelumnya di bag. 4b, di bawah Instalasi) supaya $sequence bisa
        |     dipakai juga oleh Persiapan (bag. 3), bukan cuma Pengukuran.
        |--------------------------------------------------------------------------
        */

        $stage = $lop?->stage; // ProjectStage|null (via lops.status_progress)
        $effectiveStage = $stage;

        if ($stage && ($stage->is_pause_type || $stage->is_terminal) && $lop->status_progress_before_hold) {
            // HOLD/DROP: posisi utk progress bar pakai tahap SEBELUM
            // di-hold/drop, bukan status hold/drop itu sendiri (yang tidak
            // punya `sequence`).
            $effectiveStage = ProjectStage::where('code', $lop->status_progress_before_hold)->first() ?? $stage;
        }

        // DRM sudah dikeluarkan dari alur aktif. Nilai pada LOP lama dan
        // baris master dipertahankan untuk foreign key serta histori, tetapi
        // seluruh pembacaan status menampilkannya sebagai Perizinan sampai
        // rekonsiliasi data boleh dijalankan setelah migration freeze.
        if ($effectiveStage?->code === 'drm') {
            $effectiveStage = ProjectStage::where('code', 'perizinan')->first() ?? $effectiveStage;
        }

        $sequence = $effectiveStage?->sequence; // null = LOP belum ada / kode tak dikenal

        /*
        |--------------------------------------------------------------------------
        | 3. Persiapan
        |--------------------------------------------------------------------------
        */

        // Refactor Persiapan: Inisiasi/Survey/Perizinan/Material Delivery.
        // Flow baru tidak lagi menulis eviden
        // stage='persiapan' evidence_type='barang_tiba'/'perizinan' -- eviden
        // sub-step baru pakai stage='perizinan'/'material_delivery'
        // sendiri-sendiri (lihat WaspangController). Kalau persiapanDone HANYA
        // dihitung dari 2 boolean lama itu, LOP flow baru TIDAK PERNAH bisa
        // lolos Persiapan -> Instalasi/Pengukuran/Finishing juga ikut macet
        // permanen (sama persis kelas bug dgn regresi gate Pengukuran Stage 2,
        // lihat catatan pengukuranDone di bawah). Jadi sama seperti
        // pengukuranDone: kalau posisi LOP (sequence) SUDAH LEWAT seluruh fase
        // Persiapan (>6 = persiapan_instalasi, artinya sudah di Instalasi atau
        // lebih), otomatis dianggap selesai -- fallback ke 2 boolean eviden
        // lama HANYA untuk LOP yang masih di fase Persiapan/kode tak dikenal
        // (LOP lama sebelum flow baru berlaku).
        if ($sequence !== null && $sequence > 6) {
            $persiapanDone = true;
        } else {
            $persiapanDone =
                $barangTibaApproved &&
                $perizinanApproved;
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Instalasi
        |--------------------------------------------------------------------------
        */

        $materialTotal = count($materialIds);
        $instalasiApproved = 0;

        foreach ($materialIds as $boqKey) {

            $stats = $instalasiStats[$boqKey] ?? null;

            if (
                $stats &&
                $stats['total'] > 0 &&
                $stats['approved'] === $stats['total']
            ) {
                $instalasiApproved++;
            }
        }

        $instalasiDone =
            $materialTotal > 0 &&
            $instalasiApproved >= $materialTotal;

        /*
        |--------------------------------------------------------------------------
        | 5. Pengukuran (Opsi B -- gate nyata via lop_measurement_checks)
        |     ($stage/$effectiveStage/$sequence sudah dihitung di bag. 3b di atas)
        |--------------------------------------------------------------------------
        */

        if ($sequence !== null && $sequence > 8) {
            // 8 = sequence 'pengukuran'. Sudah di Finishing/FI-OGP Golive/Golive
            // berarti sudah pasti lewat Pengukuran.
            $pengukuranDone = true;
        } elseif ($lop) {
            $doneItemKeys = LopMeasurementCheck::where('lop_id', $lop->id_lop)
                ->get()
                ->filter(fn (LopMeasurementCheck $check) => $check->isDone())
                ->pluck('item_key');

            $pengukuranDone = collect(LopMeasurementCheck::ITEMS)->diff($doneItemKeys)->isEmpty();
        } else {
            // Project belum punya baris LOP sama sekali (edge-case/data
            // yatim) -- fallback ke perilaku lama supaya tidak crash.
            $pengukuranDone = $instalasiDone;
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Finishing
        |--------------------------------------------------------------------------
        */

        $finishingTotal = count($finishingRequiredIds);
        $finishingApproved = 0;

        foreach ($finishingRequiredIds as $boqKey) {

            $stats = $finishingStats[$boqKey] ?? null;

            if (
                $stats &&
                $stats['total'] > 0 &&
                $stats['approved'] === $stats['total']
            ) {
                $finishingApproved++;
            }
        }

        $finishingDone =
            $persiapanDone &&
            $instalasiDone &&
            $pengukuranDone &&
            (
                $finishingTotal === 0 ||
                $finishingApproved >= $finishingTotal
            );

        /*
        |--------------------------------------------------------------------------
        | 7. Progress
        |--------------------------------------------------------------------------
        */

        if ($sequence !== null) {
            // 11 tahap sequential (1..11) -> 0% di tahap pertama (Inisiasi),
            // 100% persis di tahap terakhir (Golive). 10 interval antar 11 titik.
            $progress = (int) round((($sequence - 1) / 10) * 100);
        } else {
            // Fallback lama (project belum punya LOP / kode status tak
            // dikenal) -- pakai formula 4-boolean supaya tidak crash.
            $doneStep =
                (int) $persiapanDone +
                (int) $instalasiDone +
                (int) $pengukuranDone +
                (int) $finishingDone;

            $progress = (int) round(($doneStep / 4) * 100);
        }

        $stageLabel = $effectiveStage?->label ?? match (true) {
            $finishingDone => 'Ready UT',
            $instalasiDone => 'Pengukuran',
            $persiapanDone => 'Instalasi',
            default => 'Persiapan',
        };

        return $this->progressSummaryCache = [
            'persiapanDone' => $persiapanDone,
            'instalasiDone' => $instalasiDone,
            'pengukuranDone' => $pengukuranDone,
            'finishingDone' => $finishingDone,
            'materialTotal' => $materialTotal,
            'instalasiApproved' => $instalasiApproved,
            'finishingApproved' => $finishingApproved,
            'finishingTotal' => $finishingTotal,
            'progress' => $progress,
            'stageLabel' => $stageLabel,
            // --- Key baru (Stage 2 refactor), disiapkan utk Stage 3-6 ---
            'stageCode' => $stage?->code,
            'stageSequence' => $stage?->sequence,
            'phaseGroup' => $stage?->phase_group,
            'isHold' => (bool) ($stage?->is_pause_type),
            'isDrop' => (bool) ($stage?->is_terminal),
            // --- Key baru (Stage 4 refactor) -- posisi EFEKTIF (fallback ke
            // status_progress_before_hold saat HOLD/DROP, lihat bag. 4b di
            // atas), dipakai stepper mobile Waspang supaya tetap tahu "lagi
            // di tahap apa sebenarnya" walau LOP sedang di-hold/drop. ---
            'effectiveStageCode' => $effectiveStage?->code,
            'effectiveStageSequence' => $effectiveStage?->sequence,
            'effectiveStageLabel' => $effectiveStage?->label,
            'effectiveStageColor' => $effectiveStage?->color,
            'effectivePhaseGroup' => $effectiveStage?->phase_group,
        ];
    }

    public function activityLogs()
    {
        return $this->hasMany(ProjectActivityLog::class, 'project_id', 'id_project')
            ->latest();
    }

    public function issues()
    {
        return $this->hasMany(ProjectIssue::class, 'project_id', 'id_project');
    }

    // NOTE (2026-09-08): relasi pt2Survey()/pt2Mancore() ke tabel
    // pt2_surveys/pt2_mancores dihapus -- dua tabel itu dikonfirmasi
    // legacy/tidak dipakai kode manapun (nama "pt2_*" tapi FK-nya justru ke
    // `projects` biasa, bukan pt2_projects) dan sudah di-drop lewat migration
    // 2026_09_08_090900_drop_pt2_mancores_and_pt2_surveys_tables.php.
    // Tabel PT2 yang sungguhan dipakai (mancores_pt2/surveys_pt2) TIDAK
    // terpengaruh -- itu relasinya ada di Pt2Lop, bukan di sini.

    public function siteSurveys()
    {
        return $this->hasMany(SiteSurvey::class, 'project_id', 'id_project');
    }

    /**
     * Section AF: 1 sumber warna Tailwind utk badge/aksen "tahapan LOP" di
     * SEMUA halaman admin (index, project-card, tracking, dsb), dipetakan
     * dari `project_stages.color` (dibaca via progressSummary()
     * ['effectiveStageColor']) -- BUKAN lagi if/elseif per-file yang
     * hardcode string label ('Finishing'/'Pengukuran'/'Instalasi', fallback
     * "else" ke merah) seperti sebelumnya. Fallback if/elseif lama itu tidak
     * ada cabang utk 'FI-OGP Golive'/'Golive' sama sekali -- keduanya
     * kejebak di cabang "else" (merah, warna utk LOP bermasalah), padahal
     * itu 2 tahap PALING AKHIR & justru paling positif.
     *
     * Daftar kelas di bawah SENGAJA ditulis statis per-warna (bukan
     * interpolasi "bg-{$color}-600") supaya tetap ke-scan Tailwind JIT --
     * samakan kalau ada warna baru ditambah ke tabel project_stages.
     *
     * @return array{accent: string, border: string, progress: string, badge: string, dot: string}
     */
    public static function stageColorClasses(?string $color): array
    {
        return match ($color) {
            'slate' => ['accent' => 'bg-slate-500', 'border' => 'border-l-slate-500', 'progress' => 'bg-slate-500', 'badge' => 'bg-slate-100 text-slate-700', 'dot' => 'bg-slate-300'],
            'amber' => ['accent' => 'bg-amber-500', 'border' => 'border-l-amber-500', 'progress' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-700', 'dot' => 'bg-amber-300'],
            'blue' => ['accent' => 'bg-blue-600', 'border' => 'border-l-blue-600', 'progress' => 'bg-blue-600', 'badge' => 'bg-blue-100 text-blue-700', 'dot' => 'bg-blue-300'],
            'indigo' => ['accent' => 'bg-indigo-600', 'border' => 'border-l-indigo-600', 'progress' => 'bg-indigo-600', 'badge' => 'bg-indigo-100 text-indigo-700', 'dot' => 'bg-indigo-300'],
            'emerald' => ['accent' => 'bg-emerald-600', 'border' => 'border-l-emerald-600', 'progress' => 'bg-emerald-600', 'badge' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-300'],
            'purple' => ['accent' => 'bg-purple-600', 'border' => 'border-l-purple-600', 'progress' => 'bg-purple-600', 'badge' => 'bg-purple-100 text-purple-700', 'dot' => 'bg-purple-300'],
            'green' => ['accent' => 'bg-green-600', 'border' => 'border-l-green-600', 'progress' => 'bg-green-600', 'badge' => 'bg-green-100 text-green-700', 'dot' => 'bg-green-300'],
            'orange' => ['accent' => 'bg-orange-500', 'border' => 'border-l-orange-500', 'progress' => 'bg-orange-500', 'badge' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-300'],
            'red' => ['accent' => 'bg-red-500', 'border' => 'border-l-red-500', 'progress' => 'bg-red-500', 'badge' => 'bg-red-100 text-red-700', 'dot' => 'bg-red-300'],
            default => ['accent' => 'bg-gray-400', 'border' => 'border-l-gray-400', 'progress' => 'bg-gray-400', 'badge' => 'bg-gray-100 text-gray-700', 'dot' => 'bg-gray-300'],
        };
    }
}
