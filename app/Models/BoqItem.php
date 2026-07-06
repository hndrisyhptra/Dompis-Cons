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
        'lop_id',
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
    public function project()
    {
        return $this->belongsTo(
            Project::class,
            'project_id',
            'id_project'
        );
    }

    public function lop()
    {
        return $this->belongsTo(
            Lop::class,
            'lop_id',
            'id_lop'
        );
    }

    public function scopeCategory($query, $category)
    {
        return $query->whereHas('designatorData', function ($q) use ($category) {

            $q->where('progress_category', $category);

        });
    }

    public function scopeProject($query, $projectId)
    {
        return $query->where(
            'project_id',
            $projectId
        );
    }

    public function scopeLop($query, $lopId)
    {
        return $query->where(
            'lop_id',
            $lopId
        );
    }
}