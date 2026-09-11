<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Package;

class Lop extends Model
{
    protected $table = 'lops';
    protected $primaryKey = 'id_lop';

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
}
