<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';

    protected $primaryKey = 'id_project';

    protected $fillable = [
        'pid',
        'pid_sap',
        'project_name',
        'program',
        'branch',
        'sto',
        'mitra_name',
        'kml_file',
        'jenis_eksekusi',
        'execution_type',
        'status',
        'status_project',
        'latitude',
        'longitude',
        'location_address',
        'map_note'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function boqItems()
    {
        return $this->hasMany(BoqItem::class, 'project_id', 'id_project');
    }

    public function assignment()
    {
        return $this->hasOne(
            ProjectAssignment::class,
            'project_id',
            'id_project'
        );
    }

    public function assignments()
    {
        return $this->hasMany(
            ProjectAssignment::class,
            'project_id',
            'id_project'
        );
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class, 'project_id', 'id_project');
    }

    public function lop()
    {
        return $this->hasOne(Lop::class, 'project_id', 'id_project');
    }
}