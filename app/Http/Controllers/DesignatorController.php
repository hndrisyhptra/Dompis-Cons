<?php

namespace App\Http\Controllers;

use App\Models\Designator;
use Illuminate\Http\Request;

class DesignatorController extends Controller
{
    public function index(Request $request)
    {
        $query = Designator::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('designator', 'like', '%' . $request->search . '%')
                ->orWhere('item_name', 'like', '%' . $request->search . '%')
                ->orWhere('unit', 'like', '%' . $request->search . '%')
                ->orWhere('type', 'like', '%' . $request->search . '%')
                ->orWhere('pair_code', 'like', '%' . $request->search . '%')
                ->orWhere('progress_category', 'like', '%' . $request->search . '%');
            });
        }

        $designators = $query
            ->orderBy('designator')
            ->paginate(10)
            ->withQueryString();

        $progressCategories = [
            'OTHER',
            'KABEL',
            'TIANG',
        ];

        return view('admin.designators.index', [
            'designators' => $designators,
            'progressCategories' => $progressCategories,
        ]);
    }

   public function store(Request $request)
    {
        $validated = $request->validate([
            'designator' => 'required|string|max:100',
            'item_name'  => 'required|string|max:255',
            'unit'       => 'required|string|max:50',
            'type'       => 'nullable|in:material,jasa',
            'pair_code'  => 'nullable|string|max:100',
        ]);

        $designatorCode = strtoupper(trim($validated['designator']));

        Designator::create([
            'designator' => $designatorCode,
            'item_name'  => $validated['item_name'],
            'unit'       => $validated['unit'],
            'type'       => $validated['type'] ?: $this->guessType($designatorCode),
            'pair_code'  => $validated['pair_code'] ?: $this->guessPairCode($designatorCode),
        ]);

        return back()->with('success', 'Designator berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $designator = Designator::findOrFail($id);

        $validated = $request->validate([
            'designator' => 'required|string|max:100',
            'item_name'  => 'required|string|max:255',
            'unit'       => 'required|string|max:50',
            'type'       => 'nullable|in:material,jasa',
            'pair_code'  => 'nullable|string|max:100',
        ]);

        $designatorCode = strtoupper(trim($validated['designator']));

        $designator->update([
            'designator' => $designatorCode,
            'item_name'  => $validated['item_name'],
            'unit'       => $validated['unit'],
            'type'       => $validated['type'] ?: $this->guessType($designatorCode),
            'pair_code'  => $validated['pair_code'] ?: $this->guessPairCode($designatorCode),
        ]);

        return back()->with('success', 'Designator berhasil diperbarui');
    }

    public function destroy($id)
    {
        $designator = Designator::findOrFail($id);
        $designator->delete();

        return back()->with('success', 'Designator berhasil dihapus');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        $filePath = $request->file('file')->getRealPath();
        $file = fopen($filePath, 'r');

        // Deteksi delimiter otomatis (, atau ;)
        $firstLine = fgets($file);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($file);

        $header = fgetcsv($file, 10000, $delimiter);
        if (!$header) {
            fclose($file);
            return back()->with('error', 'Format file CSV tidak valid atau kosong.');
        }
        $header = array_map('trim', $header);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        // Gunakan Database Transaction agar proses import aman
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file, 10000, $delimiter)) !== false) {
                // Lewati jika baris kosong
                if (count($row) === 1 && ($row[0] === null || trim($row[0]) === '')) {
                    continue;
                }

                if (count($row) !== count($header)) {
                    $skipped++;
                    continue;
                }

                $data = array_combine($header, $row);

                $designatorCode = strtoupper($this->cleanValue($data['designator'] ?? null) ?? '');

                if (empty($designatorCode)) {
                    $skipped++;
                    continue;
                }

                $type = strtolower($this->cleanValue($data['type'] ?? '') ?? '');
                if (!in_array($type, ['material', 'jasa'])) {
                    $type = $this->guessType($designatorCode);
                }

                $pairCode = $this->cleanValue($data['pair_code'] ?? null);
                if (!$pairCode) {
                    $pairCode = $this->guessPairCode($designatorCode);
                }

                $payload = [
                    'item_name' => $this->cleanValue($data['item_name'] ?? null),
                    'unit'      => $this->cleanValue($data['unit'] ?? null),
                    'type'      => $type,
                    'pair_code' => $pairCode,
                ];

                // Upsert logic
                $designator = Designator::where('designator', $designatorCode)->first();

                if ($designator) {
                    $designator->update($payload);
                    $updated++;
                } else {
                    Designator::create(array_merge($payload, [
                        'designator' => $designatorCode,
                    ]));
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            Log::error('Import Designator Gagal: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat mengimport data.');
        }

        fclose($file);

        return back()->with(
            'success',
            "Import selesai. {$imported} data baru, {$updated} diperbarui, {$skipped} dilewati."
        );
    }

    public function toggleFinishing($id)
    {
        $designator = Designator::findOrFail($id);

        $designator->update([
            'requires_finishing_evidence' => !$designator->requires_finishing_evidence,
        ]);

        return back()->with('success', 'Status Eviden Final berhasil diubah');
    }

    private function cleanValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function guessType($designator)
    {
        if (!$designator) {
            return null;
        }

        $upper = strtoupper($designator);
        if (str_starts_with($upper, 'M-')) {
            return 'material';
        }

        if (str_starts_with($upper, 'J-')) {
            return 'jasa';
        }

        return null;
    }

    private function guessPairCode($designator)
    {
        if (!$designator) {
            return null;
        }
        return preg_replace('/^[MJ]-/i', '', strtoupper($designator));
    }
    
}