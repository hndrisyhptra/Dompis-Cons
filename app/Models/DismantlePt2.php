<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DismantlePt2 extends Model
{
    protected $table = 'dismantles_pt2';
    protected $primaryKey = 'id_dismantle_pt2';
    protected $guarded = [];

    public function project() {
        return $this->belongsTo(Pt2Project::class, 'pt2_project_id', 'id_pt2_project');
    }

    public function lop() {
        return $this->belongsTo(Pt2Lop::class, 'pt2_lop_id', 'id_pt2_lop');
    }
}