<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pt2Survey extends Model
{
    protected $table = 'pt2_surveys';
    protected $guarded = ['id'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }
}