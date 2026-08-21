<?php

namespace App\Services;

use App\Models\SiteSurvey;
use App\Models\SiteSurveyPoint;

class SiteSurveyKmlService
{
    /**
     * Bangun konten file KML lengkap dari satu Site Survey.
     * Berisi: folder Tiang Eksisting, folder Titik Catuan (ODC/ODP/JC),
     * folder Rute Kabel, dan Placemark Ending Site.
     */
    public function build(SiteSurvey $survey): string
    {
        $survey->loadMissing(['points', 'routes']);

        $docName = $this->escape($survey->displayTitle());
        $docDesc = $this->escape(
            'Survey lapangan oleh ' . ($survey->surveyor->name ?? '-') .
            ' pada ' . $survey->created_at?->format('d M Y H:i')
        );

        $kml = [];
        $kml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $kml[] = '<kml xmlns="http://www.opengis.net/kml/2.2">';
        $kml[] = '<Document>';
        $kml[] = "<name>{$docName}</name>";
        $kml[] = "<description>{$docDesc}</description>";

        $kml[] = $this->styleBlock();

        // Folder: Tiang Eksisting
        $tiangPoints = $survey->points->where('type', SiteSurveyPoint::TYPE_TIANG);
        $kml[] = $this->pointsFolder('Tiang Eksisting', $tiangPoints, 'styleTiang');

        // Folder: Titik Catuan (dikelompokkan per tipe)
        $catuanPoints = $survey->points->where('type', SiteSurveyPoint::TYPE_CATUAN);
        $kml[] = '<Folder><name>Titik Catuan</name>';
        foreach (SiteSurveyPoint::CATUAN_TYPES as $catuanType) {
            $subset = $catuanPoints->where('catuan_type', $catuanType);
            $kml[] = $this->pointsFolder($catuanType, $subset, 'styleCatuan' . $catuanType);
        }
        $kml[] = '</Folder>';

        // Folder: Rute Kabel
        $kml[] = '<Folder><name>Rute Kabel</name>';
        foreach ($survey->routes as $route) {
            $kml[] = $this->routePlacemark($route);
        }
        $kml[] = '</Folder>';

        // Ending Site (dari kolom survey ATAU dari titik bertipe ending_site)
        $endingPoints = $survey->points->where('type', SiteSurveyPoint::TYPE_ENDING_SITE);
        if ($endingPoints->count() > 0) {
            $kml[] = $this->pointsFolder('Ending Site', $endingPoints, 'styleEndingSite');
        } elseif ($survey->hasEndingSite()) {
            $kml[] = $this->placemark(
                $survey->ending_site_name ?: 'Ending Site',
                'Titik akhir survey',
                (float) $survey->ending_site_lat,
                (float) $survey->ending_site_lng,
                'styleEndingSite'
            );
        }

        $kml[] = '</Document>';
        $kml[] = '</kml>';

        return implode("\n", $kml);
    }

    public function fileName(SiteSurvey $survey): string
    {
        $slug = \Illuminate\Support\Str::slug($survey->displayTitle() ?: ('survey-' . $survey->id));

        return 'survey-' . $slug . '-' . $survey->id . '.kml';
    }

    private function pointsFolder(string $name, $points, string $styleId): string
    {
        $out = '<Folder><name>' . $this->escape($name) . '</name>';

        foreach ($points as $point) {
            $desc = $point->notes ? $this->escape($point->notes) : '';
            $label = $point->name ?: $point->typeLabel();

            $out .= $this->placemark($label, $desc, (float) $point->latitude, (float) $point->longitude, $styleId);
        }

        $out .= '</Folder>';

        return $out;
    }

    private function placemark(string $name, string $description, float $lat, float $lng, string $styleId): string
    {
        return '<Placemark>' .
            '<name>' . $this->escape($name) . '</name>' .
            ($description !== '' ? '<description>' . $description . '</description>' : '') .
            "<styleUrl>#{$styleId}</styleUrl>" .
            '<Point><coordinates>' . $lng . ',' . $lat . ',0</coordinates></Point>' .
            '</Placemark>';
    }

    private function routePlacemark($route): string
    {
        $coords = collect($route->path ?? [])
            ->map(fn ($pair) => ((float) $pair[1]) . ',' . ((float) $pair[0]) . ',0')
            ->implode(' ');

        $desc = $route->distance_meters
            ? $this->escape('Panjang kurang lebih ' . number_format($route->distance_meters, 0, ',', '.') . ' meter')
            : '';

        return '<Placemark>' .
            '<name>' . $this->escape($route->name) . '</name>' .
            ($desc !== '' ? '<description>' . $desc . '</description>' : '') .
            '<styleUrl>#styleRute</styleUrl>' .
            '<LineString><tessellate>1</tessellate><coordinates>' . $coords . '</coordinates></LineString>' .
            '</Placemark>';
    }

    private function styleBlock(): string
    {
        return '
        <Style id="styleTiang">
            <IconStyle>
                <color>ff2563eb</color>
                <scale>1.1</scale>
                <Icon><href>http://maps.google.com/mapfiles/kml/shapes/electronics.png</href></Icon>
            </IconStyle>
        </Style>
        <Style id="styleCatuanODC">
            <IconStyle>
                <scale>1.1</scale>
                <Icon><href>http://maps.google.com/mapfiles/kml/paddle/red-circle.png</href></Icon>
            </IconStyle>
        </Style>
        <Style id="styleCatuanODP">
            <IconStyle>
                <scale>1.1</scale>
                <Icon><href>http://maps.google.com/mapfiles/kml/paddle/ylw-circle.png</href></Icon>
            </IconStyle>
        </Style>
        <Style id="styleCatuanJC">
            <IconStyle>
                <scale>1.1</scale>
                <Icon><href>http://maps.google.com/mapfiles/kml/paddle/purple-circle.png</href></Icon>
            </IconStyle>
        </Style>
        <Style id="styleEndingSite">
            <IconStyle>
                <scale>1.3</scale>
                <Icon><href>http://maps.google.com/mapfiles/kml/paddle/grn-stars.png</href></Icon>
            </IconStyle>
        </Style>
        <Style id="styleRute">
            <LineStyle>
                <color>ffeb6a25</color>
                <width>4</width>
            </LineStyle>
        </Style>';
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
