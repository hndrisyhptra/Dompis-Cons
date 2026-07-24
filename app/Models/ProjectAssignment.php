<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project;


class ProjectAssignment extends Model
{
    protected $table = 'pro_assign';

    protected $primaryKey = 'id_proassign';

    public $timestamps = true;

    protected $fillable = [
    'project_id',
    'waspang_id',
    'teknisi_id',
    'assigned_by',
    ];

    public function waspang()
    {
        return $this->belongsTo(User::class, 'waspang_id', 'id_user');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id_user');
    }

    public function teknisi()
    {
        return $this->belongsTo(User::class, 'teknisi_id', 'id_user');
    }
}