<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Package;

class Lop extends Model
{
    protected $table = 'lops';
    protected $primaryKey = 'id_lop';

    /**
     * FIX (permintaan user): saat LOP baru DIBUAT (create()), otomatis
     * buka baris histori pertama di lop_stage_histories utk status_progress
     * awalnya (biasanya 'inisiasi') -- entered_at = created_at LOP itu
     * sendiri. Ini SATU-SATUNYA tempat pembuatan histori yang tidak lewat
     * advanceStage() (lihat method advanceStage() di bawah utk transisi
     * SESUDAH LOP dibuat -- WAJIB lewat situ, bukan assignment manual
     * status_progress).
     */
    protected static function booted(): void
    {
        static::created(function (self $lop) {
            if (! $lop->status_progress) {
                return;
            }

            $lop->stageHistories()->create([
                'stage_code' => $lop->status_progress,
                'entered_at' => $lop->created_at ?? now(),
            ]);
        });
    }

    protected $fillable = [
        'project_id',
        'id_ihld',
        'lop_name',
        'pid_sap',
        'program_sap',
        'tematik',
        'sto',
        'branch',
        'batch',
        'no_sp',
        'tgl_sp',
        'tgl_toc',
        'tahun_order',
        'start_tgl',
        'wo_smile',
        'nilai_material',
        'nilai_jasa',
        'nilai_total',
        'odp_8',
        'odp_16',
        'total_port',
        'plan_tiang',
        'realisasi_tiang',
        'plan_kabel',
        'realisasi_kabel',
        'plan_galian',
        'real_galian',
        'status_progress',
        'nama_waspang',
        'nik_waspang',
        'nama_admin',
        'nik_admin',
        'mitra_name',
        'est_prep',
        'est_izin',
        'est_delivery',
        'est_instalasi',
        'est_golive',
        'mapping_status',
        'package_id',
        'status_progress_before_hold',
        'sdi_approval_status',
        'is_golive',
        'golive_evidence_path',
        'golive_at',
        'permit_category_id',
        'perizinan_completed_at',
        'survey_deviation_percent',
        'survey_redesign_required',
    ];

    protected $casts = [
        'perizinan_completed_at' => 'datetime',
        'is_golive' => 'boolean',
        'golive_at' => 'datetime',
        'survey_redesign_required' => 'boolean',
        'survey_deviation_percent' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }


    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id_package');
    }

    public function boqItems()
    {
        return $this->hasMany(BoqItem::class, 'lop_id', 'id_lop');
    }

    public function stage()
    {
        return $this->belongsTo(ProjectStage::class, 'status_progress', 'code');
    }

    public function permitCategory()
    {
        return $this->belongsTo(PermitCategory::class, 'permit_category_id', 'id');
    }

    public function measurementChecks()
    {
        return $this->hasMany(LopMeasurementCheck::class, 'lop_id', 'id_lop');
    }

    public function stageHistories()
    {
        return $this->hasMany(LopStageHistory::class, 'lop_id', 'id_lop');
    }

    public function kronologis()
    {
        return $this->hasMany(LopKronologi::class, 'lop_id', 'id_lop')->latest('event_date')->latest('id');
    }

    public function goliveSubmission()
    {
        return $this->hasOne(LopGoliveSubmission::class, 'lop_id', 'id_lop');
    }

    public function goliveVerification()
    {
        return $this->hasOne(LopGoliveVerification::class, 'lop_id', 'id_lop');
    }

    /** Riwayat ronde BOQ Survey (round 1, 2, dst) -- lihat BoqSurveyRound. */
    public function surveyRounds()
    {
        return $this->hasMany(BoqSurveyRound::class, 'lop_id', 'id_lop')->orderBy('round_number');
    }

    /**
     * Permintaan user: log waktu masuk/selesai per staging supaya durasi
     * per tahap bisa dihitung (dipakai halaman Timeline per-LOP & laporan
     * agregat durasi per tahap). SEMUA tempat yang mengubah status_progress
     * SETELAH LOP dibuat WAJIB lewat method ini -- JANGAN pernah langsung
     * ->update(['status_progress' => ...]) atau assignment property manual
     * lagi, supaya lop_stage_histories selalu konsisten & lengkap.
     *
     * Idempotent -- kalau stage tujuan SAMA dgn stage sekarang, tidak
     * melakukan apa-apa (aman dipanggil berkali-kali/dari gate yang bisa
     * ke-trigger ulang).
     *
     * @param string $newStageCode kode stage tujuan (harus ada di project_stages.code)
     * @param int|null $userId auth()->id() user yang menyelesaikan tahap sebelumnya (nullable, mis. dipicu job/system)
     * @param string|null $note catatan opsional (mis. alasan drop/hold)
     */
    public function advanceStage(string $newStageCode, ?int $userId = null, ?string $note = null): void
    {
        if ($this->status_progress === $newStageCode) {
            return;
        }

        $now = now();

        $openHistory = $this->stageHistories()
            ->whereNull('completed_at')
            ->latest('entered_at')
            ->first();

        if ($openHistory) {
            $openHistory->update([
                'completed_at' => $now,
                'completed_by' => $userId,
            ]);
        }

        $this->stageHistories()->create([
            'stage_code' => $newStageCode,
            'entered_at' => $now,
            'note' => $note,
        ]);

        $this->status_progress = $newStageCode;
        $this->save();
    }
}
