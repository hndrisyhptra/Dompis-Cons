<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $packages = Package::query()
            ->when($search, function ($query) use ($search) {
                $query->where('package_code', 'like', "%{$search}%")
                    ->orWhere('package_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('package_code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.packages.index', compact('packages', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'package_code' => 'required|string|max:50|unique:packages,package_code',
            'package_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        Package::create([
            'package_code' => strtoupper(trim($request->package_code)),
            'package_name' => $request->package_name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Package berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $package = Package::findOrFail($id);

        $request->validate([
            'package_code' => 'required|string|max:50|unique:packages,package_code,' . $package->id_package . ',id_package',
            'package_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $package->update([
            'package_code' => strtoupper(trim($request->package_code)),
            'package_name' => $request->package_name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Package berhasil diperbarui');
    }

    public function destroy($id)
    {
        $package = Package::findOrFail($id);
        $package->delete();

        return back()->with('success', 'Package berhasil dihapus');
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

            $packageCode = strtoupper($this->cleanValue($data['package_code'] ?? null));

            if (!$packageCode) {
                $skipped++;
                continue;
            }

            $payload = [
                'package_name' => $this->cleanValue($data['package_name'] ?? null),
                'description' => $this->cleanValue($data['description'] ?? null),
            ];

            $package = Package::where('package_code', $packageCode)->first();

            if ($package) {
                $package->update($payload);
                $updated++;
            } else {
                Package::create(array_merge($payload, [
                    'package_code' => $packageCode,
                ]));

                $imported++;
            }
        }

        fclose($file);

        return back()->with(
            'success',
            "Import package selesai. {$imported} data baru, {$updated} diperbarui, {$skipped} dilewati."
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
}