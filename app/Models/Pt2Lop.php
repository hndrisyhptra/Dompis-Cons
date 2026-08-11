<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pt2Lop extends Model
{
    protected $table = 'pt2_lops';
    protected $primaryKey = 'id_pt2_lop';
    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Pt2Project::class, 'pt2_project_id', 'id_pt2_project');
    }

    public function boqItems()
    {
        return $this->hasMany(Pt2BoqItem::class, 'pt2_lop_id', 'id_pt2_lop');
    }
    public function surveys()
    {
        return $this->hasMany(SurveyPt2::class, 'pt2_lop_id', 'id_pt2_lop'); 
        // Ubah foreign key sesuaikan dengan posisi modelnya (pt2_project_id untuk Project, pt2_lop_id untuk LOP)
    }

    public function mancores()
    {
        return $this->hasMany(MancorePt2::class, 'pt2_lop_id', 'id_pt2_lop');
    }

    public function dismantles()
    {
        return $this->hasMany(DismantlePt2::class, 'pt2_lop_id', 'id_pt2_lop');
    }
    // Relasi HasOne (Karena 1 LOP biasanya di-assign ke 1 Teknisi/Tim)
    public function assignment()
    {
        return $this->hasOne(Pt2Assignment::class, 'pt2_lop_id', 'id_pt2_lop');
    }
    // Relasi ke Eviden PT 2
    public function evidences()
    {
        return $this->hasMany(Pt2Evidence::class, 'pt2_lop_id', 'id_pt2_lop');
    }

    // Fungsi Kalkulasi Progress LOP
    public function progressSummary()
    {
        $evidences = $this->evidences ?? collect();
        $boqItems = $this->boqItems ?? collect();

        // 1. Persiapan
        $persiapanDone = $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba')->where('status', 'approved')->count() > 0 
            && $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan')->where('status', 'approved')->count() > 0;

        // 2. Instalasi
        $materialBoqItems = $boqItems->filter(fn($boq) => str_starts_with($boq->designator, 'M-'));
        $boqTotal = $materialBoqItems->count();
        $boqApproved = $materialBoqItems->filter(function($boq) use ($evidences) {
            return $evidences->where('stage', 'instalasi')->where('evidence_type', 'progress_boq')->where('pt2_boq_id', $boq->id_pt2_boq)->where('status', 'approved')->count() > 0;
        })->count();
        $instalasiDone = $boqTotal > 0 && $boqApproved >= $boqTotal;

        // 3. Finishing
        $finishingDone = $evidences->where('stage', 'finishing')->where('status', 'approved')->count() > 0;

        $doneStep = 0;
        if ($persiapanDone) $doneStep++;
        if ($instalasiDone) $doneStep++;
        if ($finishingDone) $doneStep++;

        $progress = ($persiapanDone && $instalasiDone && $finishingDone) ? 100 : round(($doneStep / 3) * 100);
        
        $stageLabel = 'Persiapan';
        if ($progress == 100) $stageLabel = 'Selesai';
        elseif ($instalasiDone) $stageLabel = 'Finishing';
        elseif ($persiapanDone) $stageLabel = 'Instalasi';

        return [
            'progress' => $progress,
            'stageLabel' => $stageLabel
        ];
    }
}