<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoqItem extends Model
{
    protected $table = 'boq_items';

    protected $primaryKey = 'id_boq';

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'designator_id',
        'designator',
        'item_name',
        'unit',
        'quantity_plan',
        'quantity_actual',
    ];

    public function evidences()
    {
        return $this->hasMany(EvidenceFile::class, 'boq_item_id', 'id_boq');
    }

    public function designatorData()
    {
        return $this->belongsTo(Designator::class, 'designator_id', 'id_designator');
    }

    public function designatorDataByCode()
    {
        return $this->belongsTo(Designator::class, 'designator', 'designator');
    }
}