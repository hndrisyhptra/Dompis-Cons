<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTracking extends Model
{
    protected $table = 'project_trackings';

    protected $primaryKey = 'id_tracking';

    protected $fillable = [
        'project_id',
        'user_id',
        'activity_type',
        'title',
        'description',
    ];

    public function project()
    {
        return $this->belongsTo(
            Project::class,
            'project_id',
            'id_project'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id_user'
        );
    }
}