<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectActivityLog extends Model
{
    protected $primaryKey = 'id_project_activity';

    protected $fillable = [
        'project_id',
        'lop_id',
        'user_id',
        'target_user_id',
        'evidence_id',
        'activity_type',
        'title',
        'description',
        'stage',
        'status_before',
        'status_after',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];


    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id', 'id_lop');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id', 'id_user');
    }

    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidence_id', 'id_evidence');
    }
}