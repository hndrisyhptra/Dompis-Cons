<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pt2Evidence extends Model
{
    protected $table = 'pt2_evidences';
    protected $primaryKey = 'id_pt2_evidence';
    protected $guarded = [];

    // Relasi ke Project PT 2
    public function project()
    {
        return $this->belongsTo(Pt2Project::class, 'pt2_project_id', 'id_pt2_project');
    }

    // Relasi ke LOP PT 2
    public function lop()
    {
        return $this->belongsTo(Pt2Lop::class, 'pt2_lop_id', 'id_pt2_lop');
    }

    // Relasi ke BOQ PT 2 (Jika foto terkait material)
    public function boqItem()
    {
        return $this->belongsTo(Pt2BoqItem::class, 'pt2_boq_id', 'id_pt2_boq');
    }

    // Relasi ke User (Teknisi yang upload)
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_user');
    }
}