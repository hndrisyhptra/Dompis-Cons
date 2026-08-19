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
        // 1. Prioritas Mutlak: GO-LIVE (Jika sudah di-approve SDI)
        if ($this->is_golive == 1 || $this->sdi_approval_status === 'approved') {
            return [
                'progress' => 100, 
                'stageLabel' => 'GO-LIVE', 
                'color' => 'bg-emerald-500', 
                'badge' => 'bg-emerald-100 text-emerald-700 border border-emerald-200'
            ];
        }
        
        // 2. Prioritas Mutlak: Menunggu SDI (Dikirim Admin, belum di-approve SDI)
        if ($this->sdi_approval_status === 'pending') {
            return [
                'progress' => 100, 
                'stageLabel' => 'Menunggu SDI', 
                'color' => 'bg-indigo-500', 
                'badge' => 'bg-indigo-100 text-indigo-700 border border-indigo-200'
            ];
        }

        $progress = 0;
        $stageLabel = 'Persiapan';

        // 3. DYNAMIC CHECKING FISIK DATA (TOP-DOWN)
        // Mengecek dari tahap paling akhir ke tahap paling awal. Jika tahap akhir ada, otomatis override tahap sebelumnya.

        // Step 5: Complete (Mancore)
        if (\App\Models\MancorePt2::where('pt2_lop_id', $this->id_pt2_lop)->exists()) {
            $progress = 100; 
            $stageLabel = 'Complete';
        }
        // Step 4: Dismantle
        elseif (\App\Models\DismantlePt2::where('pt2_lop_id', $this->id_pt2_lop)->exists() || \App\Models\Pt2Evidence::where('pt2_lop_id', $this->id_pt2_lop)->whereRaw("LOWER(stage) = 'dismantle'")->exists()) {
            $progress = 80; 
            $stageLabel = 'Dismantle';
        }
        // Step 3: Finish (Dulu Redaman)
        elseif (\App\Models\Pt2Evidence::where('pt2_lop_id', $this->id_pt2_lop)->whereRaw("LOWER(stage) IN ('redaman', 'finish')")->exists()) {
            $progress = 60; 
            $stageLabel = 'Finish';
        }
        // Step 2: Progress (Dulu Instalasi)
        elseif (\App\Models\Pt2Evidence::where('pt2_lop_id', $this->id_pt2_lop)->whereRaw("LOWER(stage) IN ('instalasi', 'progress')")->exists()) {
            $progress = 40; 
            $stageLabel = 'Progress';
        }
        // Step 1: Survey
        elseif (\App\Models\SurveyPt2::where('pt2_lop_id', $this->id_pt2_lop)->exists()) {
            $progress = 20; 
            $stageLabel = 'Survey';
        }

        // 4. FALLBACK: Cek kolom status_progress
        // Berjaga-jaga jika bukti fisik terhapus tapi status progress di database masih tinggi
        $dbStatus = strtolower($this->status_progress ?? '');
        $statusMap = [
            'preparation' => 0, 'persiapan' => 0,
            'survey' => 20, 
            'instalasi' => 40, 'progress' => 40, 
            'redaman' => 60, 'finish' => 60, 
            'dismantle' => 80, 
            'mancore' => 100, 'done' => 100, 'complete' => 100
        ];
        
        $dbProgress = $statusMap[$dbStatus] ?? 0;
        
        // Jika status_progress di database lebih tinggi dari fisik data (misal di-bypass / sinkronisasi ulang)
        if ($dbProgress > $progress) {
            $progress = $dbProgress;
            
            if ($dbProgress == 20) $stageLabel = 'Survey';
            elseif ($dbProgress == 40) $stageLabel = 'Progress';
            elseif ($dbProgress == 60) $stageLabel = 'Finish';
            elseif ($dbProgress == 80) $stageLabel = 'Dismantle';
            elseif ($dbProgress == 100) $stageLabel = 'Complete';
        }

        // 5. MAPPING WARNA SESUAI TAHAPAN BARU
        $colorMap = [
            100 => ['color' => 'bg-green-500', 'badge' => 'bg-green-100 text-green-700 border border-green-200'],
            80  => ['color' => 'bg-purple-500', 'badge' => 'bg-purple-100 text-purple-700 border border-purple-200'],
            60  => ['color' => 'bg-blue-500', 'badge' => 'bg-blue-100 text-blue-700 border border-blue-200'],
            40  => ['color' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-700 border border-amber-200'],
            20  => ['color' => 'bg-yellow-500', 'badge' => 'bg-yellow-100 text-yellow-800 border border-yellow-200'],
            0   => ['color' => 'bg-gray-400', 'badge' => 'bg-gray-100 text-gray-600 border border-gray-200'],
        ];

        return array_merge(['progress' => $progress, 'stageLabel' => $stageLabel], $colorMap[$progress] ?? $colorMap[0]);
    }
}