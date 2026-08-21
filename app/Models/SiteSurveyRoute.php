<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSurveyRoute extends Model
{
    protected $table = 'site_survey_routes';

    protected $primaryKey = 'id_site_survey_routes';

    // Supaya $route->id tetap bisa dipakai di controller/blade/JS meski PK aslinya id_site_survey_routes
    protected $appends = ['id'];

    protected $fillable = [
        'site_survey_id',
        'name',
        'path',
        'distance_meters',
        'order_index',
    ];

    protected $casts = [
        'path' => 'array',
        'distance_meters' => 'float',
    ];

    public function getIdAttribute()
    {
        return $this->getKey();
    }

    public function survey()
    {
        return $this->belongsTo(SiteSurvey::class, 'site_survey_id', 'id_site_surveys');
    }

    /**
     * Hitung panjang rute (meter) dari array path [[lat,lng], ...]
     * menggunakan formula Haversine.
     */
    public static function calculateDistanceMeters(array $path): float
    {
        $total = 0.0;
        $count = count($path);

        for ($i = 0; $i < $count - 1; $i++) {
            $from = $path[$i];
            $to = $path[$i + 1];

            $total += self::haversine(
                (float) $from[0],
                (float) $from[1],
                (float) $to[0],
                (float) $to[1]
            );
        }

        return round($total, 2);
    }

    private static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
