<?php

namespace App\Services\Gis;

use App\Models\GisCadExport;
use App\Models\SiteSurvey;
use App\Models\SiteSurveyPoint;

/**
 * Membangun dataset ternormalisasi (points[] + polylines[]) langsung dari
 * data survey yang SUDAH ADA di database (site_survey_points /
 * site_survey_routes) - untuk opsi "Menggunakan data survey existing dari
 * database Dompis" di flow user.
 *
 * Read-only. Tidak mengubah/menyentuh tabel site_surveys/site_survey_points/
 * site_survey_routes sama sekali (sesuai instruksi: jangan ubah fitur survey
 * yang sudah berjalan).
 *
 * Berbeda dengan KmlKmzReaderService, di sini TIDAK perlu klasifikasi
 * tebak-tebakan - tipe titik sudah tersimpan valid di kolom `type`/
 * `catuan_type`, jadi confidence selalu 'high'.
 */
class SiteSurveyDatasetService
{
    public function build(SiteSurvey $survey): array
    {
        $survey->loadMissing(['points', 'routes']);

        $points = [];
        $refCounter = 0;

        foreach ($survey->points as $point) {
            $type = $this->mapPointType($point);

            if ($type === null) {
                // Tipe titik yang belum dikenal modul GIS-to-CAD ini (kalau di
                // masa depan ada tipe baru di SiteSurveyPoint). Jangan bikin
                // proses gagal total - cukup lewati satu titik ini.
                continue;
            }

            $refCounter++;

            $points[] = [
                'ref' => 'p' . $refCounter,
                'type' => $type,
                'name' => $point->name ?: $point->typeLabel(),
                'lat' => (float) $point->latitude,
                'lng' => (float) $point->longitude,
                'source_name' => $point->name,
                'source_folder' => null,
                'confidence' => 'high',
            ];
        }

        // Ending site dari kolom di tabel site_surveys, HANYA kalau belum
        // ada titik bertipe ending_site (persis pola yang sama seperti
        // SiteSurveyKmlService::build()).
        $hasEndingSitePoint = $survey->points->contains('type', SiteSurveyPoint::TYPE_ENDING_SITE);

        if (!$hasEndingSitePoint && $survey->hasEndingSite()) {
            $refCounter++;

            $points[] = [
                'ref' => 'p' . $refCounter,
                'type' => 'ending_site',
                'name' => $survey->ending_site_name ?: 'Ending Site',
                'lat' => (float) $survey->ending_site_lat,
                'lng' => (float) $survey->ending_site_lng,
                'source_name' => $survey->ending_site_name,
                'source_folder' => null,
                'confidence' => 'high',
            ];
        }

        $polylines = [];
        $routeCounter = 0;

        foreach ($survey->routes as $route) {
            $coords = collect($route->path ?? [])
                ->filter(fn ($pair) => is_array($pair) && count($pair) >= 2)
                ->map(fn ($pair) => [(float) $pair[0], (float) $pair[1]])
                ->values()
                ->all();

            if (count($coords) < 2) {
                continue;
            }

            $routeCounter++;

            $polylines[] = [
                'ref' => 'r' . $routeCounter,
                'type' => 'cable',
                'name' => $route->name ?: ('Rute Kabel ' . $routeCounter),
                'coordinates' => $coords,
            ];
        }

        return [
            'meta' => [
                'source_type' => GisCadExport::SOURCE_SITE_SURVEY,
                'site_survey_id' => $survey->id,
                'survey_title' => $survey->displayTitle(),
            ],
            'points' => $points,
            'polylines' => $polylines,
            'skipped' => [],
        ];
    }

    private function mapPointType(SiteSurveyPoint $point): ?string
    {
        if ($point->type === SiteSurveyPoint::TYPE_TIANG) {
            return 'tiang';
        }

        if ($point->type === SiteSurveyPoint::TYPE_ENDING_SITE) {
            return 'ending_site';
        }

        if ($point->type === SiteSurveyPoint::TYPE_CATUAN) {
            // Dibaca apa adanya dari kolom catuan_type (ODC/ODP/OTB/JC), TIDAK
            // bergantung ke konstanta SiteSurveyPoint::CATUAN_TYPES - supaya
            // kalau suatu saat ada tipe catuan baru yang belum masuk daftar
            // konstanta, titik tsb tetap terbawa ke dataset GIS-to-CAD ini.
            $catuanType = strtolower((string) $point->catuan_type);

            return $catuanType !== '' ? $catuanType : null;
        }

        return null;
    }
}
