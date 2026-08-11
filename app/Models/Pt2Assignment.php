<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pt2Assignment extends Model
{
    protected $table = 'pt2_assignments';
    protected $primaryKey = 'id_pt2_assignment';
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

    // Relasi ke User (Teknisi)
    public function teknisi()
    {
        return $this->belongsTo(User::class, 'teknisi_id', 'id_user');
    }

    // Relasi ke User (Admin yang meng-assign)
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id_user');
    }
}