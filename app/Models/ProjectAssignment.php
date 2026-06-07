<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project;


class ProjectAssignment extends Model
{
    protected $table = 'pro_assign';

    protected $primaryKey = 'id_proassign';

    public $timestamps = false;

    protected $fillable = [
    'project_id',
    'waspang_id',
    ];

    public function waspang()
    {
        return $this->belongsTo(User::class, 'waspang_id', 'id_user');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }
}