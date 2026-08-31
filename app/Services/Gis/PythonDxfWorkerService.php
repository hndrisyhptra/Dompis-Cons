<?php

namespace App\Services\Gis;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Wrapper tipis untuk memanggil worker Python (python-worker/gis_to_dxf.py)
 * yang benar-benar menulis file DXF (pakai ezdxf) dan transformasi
 * koordinat WGS84 -> UTM (pakai pyproj).
 *
 * Kenapa Python, bukan pure-PHP: sudah didiskusikan & di-approve user saat
 * audit - ezdxf/pyproj adalah library CAD/GIS yang jauh lebih matang &
 * teruji dibanding opsi PHP yang tersedia di Packagist saat ini.
 *
 * PENTING (harus dibaca sebelum deploy): script Python di python-worker/
 * BELUM bisa dieksekusi/dites di lingkungan manapun yang saya (Claude) bisa
 * akses saat ini - sandbox cloud saya maupun device_bash di komputer Anda
 * sama-sama tidak punya akses network ke PyPI untuk `pip install ezdxf
 * pyproj`. Jadi service ini sudah ditulis hati-hati mengikuti API resmi
 * ezdxf/pyproj yang sudah stabil bertahun-tahun, TAPI wajib di-smoke-test
 * manual oleh Anda (lihat python-worker/README.md) sebelum dipakai user
 * beneran. Kalau ada error, kirim output-nya ke saya untuk saya perbaiki.
 */
class PythonDxfWorkerService
{
    /**
     * @param  string  $datasetJsonPath  Path absolut ke file JSON dataset (points/polylines).
     * @param  string  $outputDxfPath  Path absolut tujuan file DXF yang akan ditulis worker.
     * @param  string  $template  'standard_fttx' | 'custom'
     * @return array{success: bool, utm_zone?: string, epsg?: int, points_written?: int, polylines_written?: int, error?: string}
     */
    public function generate(string $datasetJsonPath, string $outputDxfPath, string $template): array
    {
        $pythonBin = config('services.gis_cad.python_bin', 'python3');
        $scriptPath = config('services.gis_cad.worker_script');
        $timeout = (int) config('services.gis_cad.timeout', 300);

        if (!is_string($scriptPath) || !is_file($scriptPath)) {
            throw new RuntimeException("Worker script Python tidak ditemukan di: {$scriptPath}. Pastikan folder python-worker/ ikut ter-deploy.");
        }

        $result = Process::timeout($timeout)->run([
            $pythonBin,
            $scriptPath,
            '--input', $datasetJsonPath,
            '--output', $outputDxfPath,
            '--template', $template,
        ]);

        $stdout = trim($result->output());
        $stderr = trim($result->errorOutput());

        $decoded = $this->extractJsonResult($stdout);

        if (!$result->successful() || !is_array($decoded) || empty($decoded['success'])) {
            $errorMessage = $decoded['error']
                ?? ($stderr !== '' ? $stderr : 'Python worker gagal tanpa pesan error (exit code ' . $result->exitCode() . ').');

            Log::error('GisCad: Python worker gagal generate DXF', [
                'exit_code' => $result->exitCode(),
                'python_bin' => $pythonBin,
                'script' => $scriptPath,
                'stdout' => mb_substr($stdout, 0, 4000),
                'stderr' => mb_substr($stderr, 0, 4000),
            ]);

            throw new RuntimeException('Generate DXF gagal: ' . $errorMessage);
        }

        return $decoded;
    }

    /**
     * Worker Python selalu print TEPAT SATU baris JSON di stdout sebagai
     * baris terakhir (baris-baris sebelumnya boleh berupa log biasa).
     * Ambil baris terakhir yang valid JSON.
     */
    private function extractJsonResult(string $stdout): ?array
    {
        if ($stdout === '') {
            return null;
        }

        $lines = array_values(array_filter(explode("\n", $stdout), fn ($line) => trim($line) !== ''));

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $decoded = json_decode(trim($lines[$i]), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
