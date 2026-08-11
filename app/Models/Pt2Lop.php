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
        // 1. Cek Prioritas Tertinggi: GO-LIVE (Mutlak 100%)
        if ($this->is_golive == 1 || $this->sdi_approval_status === 'approved') {
            return [
                'progress' => 100,
                'stageLabel' => 'GO-LIVE',
                'color' => 'bg-emerald-500',
                'badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200'
            ];
        }

        // 2. Cek Prioritas Kedua: Menunggu SDI (Mutlak 100%)
        if ($this->sdi_approval_status === 'pending') {
            return [
                'progress' => 100,
                'stageLabel' => 'Menunggu SDI',
                'color' => 'bg-indigo-500',
                'badge' => 'bg-indigo-100 text-indigo-700 border-indigo-200'
            ];
        }

        // 3. Baca tahapan dari database (progress teknisi)
        $status = strtolower($this->status_progress ?? '');

        switch ($status) {
            case 'done':
            case 'mancore':
            case 'complete':
                return [
                    'progress' => 100,
                    'stageLabel' => 'Complete',
                    'color' => 'bg-green-500',
                    'badge' => 'bg-green-100 text-green-700 border-green-200'
                ];
            case 'dismantle':
                return [
                    'progress' => 80,
                    'stageLabel' => 'Dismantle',
                    'color' => 'bg-purple-500',
                    'badge' => 'bg-purple-100 text-purple-700 border-purple-200'
                ];
            case 'finish':
            case 'redaman':
                return [
                    'progress' => 60,
                    'stageLabel' => 'Finish',
                    'color' => 'bg-blue-500',
                    'badge' => 'bg-blue-100 text-blue-700 border-blue-200'
                ];
            case 'progress':
            case 'instalasi':
                return [
                    'progress' => 40,
                    'stageLabel' => 'Progress',
                    'color' => 'bg-amber-500',
                    'badge' => 'bg-amber-100 text-amber-700 border-amber-200'
                ];
            case 'survey':
                return [
                    'progress' => 20,
                    'stageLabel' => 'Survey',
                    'color' => 'bg-yellow-500',
                    'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-200'
                ];
            default:
                return [
                    'progress' => 0,
                    'stageLabel' => 'Persiapan',
                    'color' => 'bg-gray-400',
                    'badge' => 'bg-gray-100 text-gray-600 border-gray-200'
                ];
        }
    }
}