<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectIssue extends Model
{
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