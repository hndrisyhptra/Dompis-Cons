<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectIhld extends Model
{
    protected $table = 'project_ihlds';
    protected $guarded = ['id'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }
}