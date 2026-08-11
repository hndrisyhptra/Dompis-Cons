<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pt2Project extends Model
{
    protected $table = 'pt2_projects';
    protected $primaryKey = 'id_pt2_project';
    protected $guarded = [];

    public function lops()
    {
        return $this->hasMany(Pt2Lop::class, 'pt2_project_id', 'id_pt2_project');
    }

    public function boqItems()
    {
        return $this->hasMany(Pt2BoqItem::class, 'pt2_project_id', 'id_pt2_project');
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
    public function assignments()
    {
        return $this->hasMany(Pt2Assignment::class, 'pt2_project_id', 'id_pt2_project');
    }
}