<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Pt2BoqItem extends Model
{
    protected $table = 'pt2_boq_items';
    protected $primaryKey = 'id_pt2_boq';
    protected $guarded = [];

    public function lop()
    {
        return $this->belongsTo(Pt2Lop::class, 'pt2_lop_id', 'id_pt2_lop');
    }

    public function designatorData()
    {
        return $this->belongsTo(Designator::class, 'designator_id', 'id_designator');
    }
}