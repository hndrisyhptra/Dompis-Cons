<?php

namespace App\Services\Gis;

use App\Models\GisCadExport;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

/**
 * Membaca file KML/KMZ hasil survey lapangan (upload manual, BUKAN
 * hasil generate SiteSurveyKmlService) dan mengubahnya jadi dataset
 * ternormalisasi (points[] + polylines[]) yang siap dipakai
 * GisCadExportService -> Python worker.
 *
 * Sengaja TIDAK pakai library composer pihak ketiga untuk parsing KML/KMZ
 * (lihat hasil audit): ekosistem library KML/KMZ PHP di Packagist saat ini
 * kecil & tidak jelas status maintenance-nya, sementara KML sendiri "hanya"
 * XML dengan skema stabil - jadi dipakai native ZipArchive + DOMDocument/
 * DOMXPath (xpath pakai local-name() supaya tidak rewel soal namespace,
 * karena setiap tool GPS/Google Earth kadang beda cara deklarasi xmlns).
 *
 * PENTING: karena file ini asalnya dari luar Dompis (Google Earth, app GPS
 * lapangan lain, dll), TIDAK ada struktur baku yang menjamin nama
 * Placemark/Folder konsisten. classifyPointType() di sini hanya best-effort
 * (menebak dari nama Placemark & nama Folder induknya). Titik yang gagal
 * ditebak akan diberi type 'unknown' + confidence 'low', BUKAN dibuang -
 * itu semua tetap dikirim ke layar review manual sebelum DXF final
 * di-generate (lihat instruksi user: wajib ada langkah konfirmasi).
 */
class KmlKmzReaderService
{
    /**
     * Batas pengaman ekstraksi KMZ (anti zip-bomb).
     */
    private const MAX_KMZ_ENTRIES = 500;
    private const MAX_KMZ_TOTAL_UNCOMPRESSED_BYTES = 100 * 1024 * 1024; // 100 MB

    /**
     * Urutan & pola pengenalan tipe titik dari teks nama Placemark/Folder.
     * Urutan penting: OTB/ODP/ODC/JC dicek dengan word-boundary supaya
     * tidak salah tangkap satu sama lain.
     *
     * @var array<string, string>
     */
    private const TYPE_PATTERNS = [
        'otb' => '/\bOTB\b/u',
        'odp' => '/\bODP\b/u',
        'odc' => '/\bODC\b/u',
        'jc' => '/\bJC\b|JOINT\s?CLOSURE/u',
        'ending_site' => '/ENDING\s?SITE|HOME\s?PASS|\bHP\b/u',
        'tiang' => '/\bTIANG\b|\bPOLE\b/u',
    ];

    public function readUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $realPath = $file->getRealPath();

        if ($realPath === false || !is_file($realPath)) {
            throw new RuntimeException('File upload tidak ditemukan di server.');
        }

        if ($extension === 'kmz') {
            $kmlContent = $this->extractKmlFromKmz($realPath);
        } elseif ($extension === 'kml') {
            $kmlContent = file_get_contents($realPath);
            if ($kmlContent === false) {
                throw new RuntimeException('Gagal membaca isi file KML.');
            }
        } else {
            throw new RuntimeException('Format file tidak didukung. Hanya .kml atau .kmz.');
        }

        return $this->parseKmlString($kmlContent, $file->getClientOriginalName());
    }

    /**
     * Ambil isi doc.kml dari dalam KMZ TANPA extractTo() ke disk
     * (baca langsung dari stream zip) supaya tidak ada risiko path
     * traversal dari nama entry yang aneh-aneh.
     */
    private function extractKmlFromKmz(string $kmzPath): string
    {
        $zip = new ZipArchive();
        $opened = $zip->open($kmzPath);

        if ($opened !== true) {
            throw new RuntimeException('File KMZ tidak bisa dibuka (kemungkinan rusak atau bukan format zip yang valid).');
        }

        try {
            if ($zip->numFiles > self::MAX_KMZ_ENTRIES) {
                throw new RuntimeException('File KMZ berisi terlalu banyak entry (' . $zip->numFiles . '). Maksimal ' . self::MAX_KMZ_ENTRIES . '.');
            }

            $totalUncompressed = 0;
            $kmlEntryName = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    continue;
                }

                $totalUncompressed += (int) $stat['size'];

                if ($totalUncompressed > self::MAX_KMZ_TOTAL_UNCOMPRESSED_BYTES) {
                    throw new RuntimeException('Isi file KMZ terlalu besar setelah diekstrak (melebihi batas aman).');
                }

                $name = $stat['name'];
                if (preg_match('/\.kml$/i', $name)) {
                    // Prioritaskan doc.kml (konvensi umum KMZ) kalau ada lebih dari 1 .kml
                    if ($kmlEntryName === null || strtolower(basename($name)) === 'doc.kml') {
                        $kmlEntryName = $name;
                    }
                }
            }

            if ($kmlEntryName === null) {
                throw new RuntimeException('File KMZ tidak berisi file .kml di dalamnya.');
            }

            $content = $zip->getFromName($kmlEntryName);

            if ($content === false) {
                throw new RuntimeException('Gagal membaca isi doc.kml dari dalam KMZ.');
            }

            return $content;
        } finally {
            $zip->close();
        }
    }

    /**
     * Parse konten KML mentah menjadi dataset ternormalisasi.
     */
    public function parseKmlString(string $kmlContent, ?string $sourceLabel = null): array
    {
        $previousErrorSetting = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new DOMDocument();
        $loaded = $dom->loadXML($kmlContent, LIBXML_NONET | LIBXML_NOENT | LIBXML_NOBLANKS);

        if (!$loaded) {
            $errors = collect(libxml_get_errors())->map(fn ($e) => trim($e->message))->implode('; ');
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorSetting);

            throw new RuntimeException('File KML tidak valid / gagal di-parse: ' . ($errors ?: 'unknown XML error'));
        }

        libxml_use_internal_errors($previousErrorSetting);

        $xpath = new DOMXPath($dom);

        $points = [];
        $polylines = [];
        $skipped = [];
        $refCounter = 0;

        /** @var DOMElement $placemark */
        foreach ($xpath->query("//*[local-name()='Placemark']") as $placemark) {
            $name = trim($this->firstChildText($xpath, "./*[local-name()='name']", $placemark));
            $folderName = $this->closestFolderName($xpath, $placemark);

            $pointNode = $xpath->query("./*[local-name()='Point']", $placemark)->item(0);
            $lineNode = $xpath->query("./*[local-name()='LineString']", $placemark)->item(0);
            $polygonNode = $xpath->query("./*[local-name()='Polygon']", $placemark)->item(0);

            if ($pointNode) {
                $coordText = trim($this->firstChildText($xpath, "./*[local-name()='coordinates']", $pointNode));
                $coord = $this->parseSingleCoordinate($coordText);

                if ($coord === null) {
                    $skipped[] = ['name' => $name, 'folder' => $folderName, 'reason' => 'invalid_point_coordinates'];
                    continue;
                }

                [$lng, $lat] = $coord;
                [$type, $confidence] = $this->classifyPointType($name, $folderName);
                $refCounter++;

                $points[] = [
                    'ref' => 'p' . $refCounter,
                    'type' => $type,
                    'name' => $name !== '' ? $name : null,
                    'lat' => $lat,
                    'lng' => $lng,
                    'source_name' => $name,
                    'source_folder' => $folderName,
                    'confidence' => $confidence,
                ];
            } elseif ($lineNode) {
                $coordText = trim($this->firstChildText($xpath, "./*[local-name()='coordinates']", $lineNode));
                $coords = $this->parseCoordinateList($coordText);

                if (count($coords) < 2) {
                    $skipped[] = ['name' => $name, 'folder' => $folderName, 'reason' => 'invalid_line_coordinates'];
                    continue;
                }

                $refCounter++;

                $polylines[] = [
                    'ref' => 'r' . $refCounter,
                    'type' => 'cable',
                    'name' => $name !== '' ? $name : ('Rute Kabel ' . $refCounter),
                    // format [[lat, lng], ...] - konsisten dengan kolom `path` di site_survey_routes
                    'coordinates' => $coords,
                ];
            } elseif ($polygonNode) {
                // Polygon (mis. outline bangunan) belum ada di alur "Sistem membaca" saat ini.
                // Dicatat sebagai skipped (bukan dibuang diam-diam) supaya kelihatan di ringkasan hasil.
                $skipped[] = ['name' => $name, 'folder' => $folderName, 'reason' => 'unsupported_geometry:Polygon'];
            } else {
                $skipped[] = ['name' => $name, 'folder' => $folderName, 'reason' => 'no_supported_geometry'];
            }
        }

        if (empty($points) && empty($polylines)) {
            throw new RuntimeException('File KML/KMZ tidak berisi titik (Point) atau jalur kabel (LineString) yang bisa dibaca.');
        }

        return [
            'meta' => [
                'source_type' => GisCadExport::SOURCE_KML_UPLOAD,
                'original_file_name' => $sourceLabel,
                'parsed_at' => now()->toIso8601String(),
            ],
            'points' => $points,
            'polylines' => $polylines,
            'skipped' => $skipped,
        ];
    }

    /**
     * Tebak tipe titik dari nama Placemark & nama Folder induknya.
     * Folder dicek lebih dulu karena biasanya lebih konsisten penamaannya
     * dibanding nama Placemark satuan.
     *
     * @return array{0: string, 1: string} [type, confidence]
     */
    private function classifyPointType(string $name, string $folder): array
    {
        $haystackFolder = mb_strtoupper($folder);
        $haystackName = mb_strtoupper($name);

        foreach (self::TYPE_PATTERNS as $type => $regex) {
            if ($haystackFolder !== '' && preg_match($regex, $haystackFolder)) {
                return [$type, 'high'];
            }
        }

        foreach (self::TYPE_PATTERNS as $type => $regex) {
            if ($haystackName !== '' && preg_match($regex, $haystackName)) {
                return [$type, 'high'];
            }
        }

        return ['unknown', 'low'];
    }

    private function firstChildText(DOMXPath $xpath, string $query, DOMElement $context): string
    {
        $node = $xpath->query($query, $context)->item(0);

        return $node?->textContent ?? '';
    }

    /**
     * Cari nama <Folder> terdekat di atas sebuah Placemark (bisa nested).
     */
    private function closestFolderName(DOMXPath $xpath, DOMElement $placemark): string
    {
        $folder = $xpath->query("./ancestor::*[local-name()='Folder'][1]", $placemark)->item(0);

        if (!$folder instanceof DOMElement) {
            return '';
        }

        return trim($this->firstChildText($xpath, "./*[local-name()='name']", $folder));
    }

    /**
     * KML coordinates format: "lng,lat[,alt]" (satu titik).
     *
     * @return array{0: float, 1: float}|null [lng, lat]
     */
    private function parseSingleCoordinate(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $text);
        $first = $parts[0] ?? '';
        $segments = explode(',', $first);

        if (count($segments) < 2) {
            return null;
        }

        $lng = (float) $segments[0];
        $lat = (float) $segments[1];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return [$lng, $lat];
    }

    /**
     * KML coordinates format untuk LineString: "lng,lat[,alt] lng,lat[,alt] ...".
     *
     * @return array<int, array{0: float, 1: float}> list of [lat, lng]
     */
    private function parseCoordinateList(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $result = [];

        foreach (preg_split('/\s+/', $text) as $token) {
            $segments = explode(',', $token);
            if (count($segments) < 2) {
                continue;
            }

            $lng = (float) $segments[0];
            $lat = (float) $segments[1];

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }

            $result[] = [$lat, $lng];
        }

        return $result;
    }
}
