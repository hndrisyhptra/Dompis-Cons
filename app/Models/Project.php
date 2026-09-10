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

        $materialIds = [];
        $finishingRequiredIds = [];

        foreach ($boqItems as $boq) {

            // Hanya material M-
            if (! str_starts_with((string) ($boq->designator ?? ''), 'M-')) {
                continue;
            }

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
}
