<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSurveyPoint extends Model
{
    protected $table = 'site_survey_points';

    protected $primaryKey = 'id_site_survey_points';

    // Supaya $point->id tetap bisa dipakai di controller/blade/JS meski PK aslinya id_site_survey_points
    protected $appends = ['id'];

    public const TYPE_TIANG = 'tiang_eksisting';
    public const TYPE_CATUAN = 'catuan';
    public const TYPE_ENDING_SITE = 'ending_site';

    public const CATUAN_TYPES = ['ODC', 'ODP', 'JC'];

    protected $fillable = [
        'site_survey_id',
        'type',
        'catuan_type',
        'name',
        'latitude',
        'longitude',
        'photo_path',
        'notes',
        'order_index',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function getIdAttribute()
    {
        return $this->getKey();
    }

    public function survey()
    {
        return $this->belongsTo(SiteSurvey::class, 'site_survey_id', 'id_site_surveys');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_TIANG => 'Tiang Eksisting',
            self::TYPE_CATUAN => 'Catuan ' . ($this->catuan_type ?? ''),
            self::TYPE_ENDING_SITE => 'Ending Site',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
