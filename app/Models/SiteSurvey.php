<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSurvey extends Model
{
    protected $table = 'site_surveys';

    protected $primaryKey = 'id_site_surveys';

    // Supaya $survey->id tetap bisa dipakai di controller/blade/JS meski PK aslinya id_site_surveys
    protected $appends = ['id'];

    protected $fillable = [
        'project_id',
        'project_name',
        'title',
        'surveyor_id',
        'status',
        'notes',
        'ending_site_lat',
        'ending_site_lng',
        'ending_site_name',
        'kml_path',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'ending_site_lat' => 'float',
        'ending_site_lng' => 'float',
    ];

    public function getIdAttribute()
    {
        return $this->getKey();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id_project');
    }

    public function surveyor()
    {
        return $this->belongsTo(User::class, 'surveyor_id', 'id_user');
    }

    public function points()
    {
        return $this->hasMany(SiteSurveyPoint::class, 'site_survey_id', 'id_site_surveys')
            ->orderBy('order_index');
    }

    public function tiangPoints()
    {
        return $this->points()->where('type', 'tiang_eksisting');
    }

    public function catuanPoints()
    {
        return $this->points()->where('type', 'catuan');
    }

    public function routes()
    {
        return $this->hasMany(SiteSurveyRoute::class, 'site_survey_id', 'id_site_surveys')
            ->orderBy('order_index');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function hasEndingSite(): bool
    {
        return $this->ending_site_lat !== null && $this->ending_site_lng !== null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function totalPointsCount(): int
    {
        return $this->points()->count();
    }

    public function totalRouteDistanceMeters(): float
    {
        return (float) $this->routes()->sum('distance_meters');
    }

    public function displayTitle(): string
    {
        return $this->project?->project_name ?? $this->project_name ?? $this->title;
    }
}
