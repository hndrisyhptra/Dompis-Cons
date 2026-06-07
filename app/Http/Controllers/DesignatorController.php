<?php

namespace App\Http\Controllers;

use App\Models\Designator;
use Illuminate\Http\Request;

class DesignatorController extends Controller
{
    public function index(Request $request)
    {
        $query = Designator::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('designator', 'like', '%' . $request->search . '%')
                    ->orWhere('item_name', 'like', '%' . $request->search . '%')
                    ->orWhere('unit', 'like', '%' . $request->search . '%')
                    ->orWhere('type', 'like', '%' . $request->search . '%')
                    ->orWhere('pair_code', 'like', '%' . $request->search . '%');
            });
        }

        $designators = $query
            ->orderBy('designator')
            ->paginate(10)
            ->withQueryString();

        return view('admin.designators.index', compact('designators'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'designator' => 'required|string|max:100',
            'item_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'type' => 'nullable|in:material,jasa',
            'pair_code' => 'nullable|string|max:100',
        ]);

        $designatorCode = strtoupper(trim($request->designator));

        Designator::create([
            'designator' => $designatorCode,
            'item_name' => $request->item_name,
            'unit' => $request->unit,
            'type' => $request->type ?: $this->guessType($designatorCode),
            'pair_code' => $request->pair_code ?: $this->guessPairCode($designatorCode),
        ]);

        return back()->with('success', 'Designator berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $designator = Designator::findOrFail($id);

        $request->validate([
            'designator' => 'required|string|max:100',
            'item_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'type' => 'nullable|in:material,jasa',
            'pair_code' => 'nullable|string|max:100',
        ]);

        $designatorCode = strtoupper(trim($request->designator));

        $designator->update([
            'designator' => $designatorCode,
            'item_name' => $request->item_name,
            'unit' => $request->unit,
            'type' => $request->type ?: $this->guessType($designatorCode),
            'pair_code' => $request->pair_code ?: $this->guessPairCode($designatorCode),
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

        $file = fopen($request->file('file')->getRealPath(), 'r');

        $header = fgetcsv($file);
        $header = array_map('trim', $header);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($file, 10000, ",")) !== false) {

            if (count($row) === 1 && ($row[0] === null || trim($row[0]) === '')) {
                continue;
            }

            if (count($row) !== count($header)) {
                $skipped++;
                continue;
            }

            $data = array_combine($header, $row);

            $designatorCode = strtoupper($this->cleanValue($data['designator'] ?? null));

            if (!$designatorCode) {
                $skipped++;
                continue;
            }

            $type = strtolower($this->cleanValue($data['type'] ?? ''));

            if (!in_array($type, ['material', 'jasa'])) {
                $type = $this->guessType($designatorCode);
            }

            $pairCode = $this->cleanValue($data['pair_code'] ?? null);

            if (!$pairCode) {
                $pairCode = $this->guessPairCode($designatorCode);
            }

            $payload = [
                'item_name' => $this->cleanValue($data['item_name'] ?? null),
                'unit' => $this->cleanValue($data['unit'] ?? null),
                'type' => $type,
                'pair_code' => $pairCode,
            ];

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

        fclose($file);

        return back()->with(
            'success',
            "Import selesai. {$imported} data baru, {$updated} diperbarui, {$skipped} dilewati."
        );
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
        if (str_starts_with(strtoupper($designator), 'M-')) {
            return 'material';
        }

        if (str_starts_with(strtoupper($designator), 'J-')) {
            return 'jasa';
        }

        return null;
    }

    private function guessPairCode($designator)
    {
        return preg_replace('/^[MJ]-/i', '', strtoupper($designator));
    }

    
}