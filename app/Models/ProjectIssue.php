<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectIssue extends Model
{
    protected $primaryKey = 'id_project_issues';

    protected $fillable = [
        'project_id',
        'lop_id',
        'user_id',
        'issue_type',
        'description',
        'status',
        'resolution_note',
    ];
}