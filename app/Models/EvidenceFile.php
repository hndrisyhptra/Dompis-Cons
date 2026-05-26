<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceFile extends Model
{
    protected $primaryKey = 'id_evidence';

    protected $fillable = [
        'project_id',
        'boq_item_id',
        'uploaded_by',
        'file_path',
        'stage',
        'description',
        'latitude',
        'longitude',
        'status',
    ];

    public function boqItem()
    {
        return $this->belongsTo(BoqItem::class, 'boq_item_id', 'id_boq');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_user');
    }
}