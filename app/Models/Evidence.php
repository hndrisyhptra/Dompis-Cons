<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    protected $table = 'evidences';

    protected $primaryKey = 'id_evidence';

    public $timestamps = true;

    protected $fillable = [
        'project_id',
        'boq_item_id',
        'uploaded_by',
        'stage',
        'evidence_type',
        'file_path',
        'latitude',
        'longitude',
        'description',
        'status',
        'created_at',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }

    public function boqItem()
    {
        return $this->belongsTo(BoqItem::class, 'boq_item_id', 'id_boq');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_user');
    }

    public function revisionHistories()
    {
        return $this->hasMany(EvidenceRevisionHistory::class, 'evidence_id', 'id_evidence');
    }


}