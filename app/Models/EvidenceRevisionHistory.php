<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceRevisionHistory extends Model
{
    protected $fillable = [
        'evidence_id',
        'project_id',
        'reviewed_by',
        'stage',
        'evidence_type',
        'review_note',
        'status',
    ];

    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidence_id', 'id_evidence');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id_user');
    }
}