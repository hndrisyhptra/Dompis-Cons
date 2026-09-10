<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectIssue extends Model
{
    protected $primaryKey = 'id_project_issues';

    protected $fillable = [
        'project_id',
        'lop_id',
        'stage_code',
        'user_id',
        'issue_type',
        'kendala_category_id',
        'description',
        'photo_path',
        'photo_paths',
        'status',
        'resolution_note',
    ];

    protected $casts = [
        'photo_paths' => 'array',
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

    public function kendalaCategory()
    {
        return $this->belongsTo(KendalaCategory::class, 'kendala_category_id', 'id');
    }
}