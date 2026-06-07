<?php

namespace App\Http\Controllers;

use App\Models\Designator;
use App\Models\Package;
use App\Models\DesignatorPackagePrice;
use Illuminate\Http\Request;

class DesignatorPriceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $prices = DesignatorPackagePrice::with(['designator', 'package'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('designator', function ($q) use ($search) {
                    $q->where('designator', 'like', "%{$search}%")
                      ->orWhere('item_name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                })
                ->orWhereHas('package', function ($q) use ($search) {
                    $q->where('package_code', 'like', "%{$search}%")
                      ->orWhere('package_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $designators = Designator::orderBy('designator')->get();
        $packages = Package::orderBy('package_code')->get();

        return view('admin.designator-prices.index', compact(
            'prices',
            'designators',
            'packages',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'designator_id' => 'required|exists:designators,id_designator',
            'package_id' => 'required|exists:packages,id_package',
            'price' => 'required|numeric|min:0',
        ]);

        DesignatorPackagePrice::updateOrCreate(
            [
                'designator_id' => $request->designator_id,
                'package_id' => $request->package_id,
            ],
            [
                'price' => $request->price,
            ]
        );

        return back()->with('success', 'Harga designator berhasil disimpan');
    }

    public function update(Request $request, $id)
    {
        $price = DesignatorPackagePrice::findOrFail($id);

        $request->validate([
            'designator_id' => 'required|exists:designators,id_designator',
            'package_id' => 'required|exists:packages,id_package',
            'price' => 'required|numeric|min:0',
        ]);

        $price->update([
            'designator_id' => $request->designator_id,
            'package_id' => $request->package_id,
            'price' => $request->price,
        ]);

        return back()->with('success', 'Harga designator berhasil diperbarui');
    }

    public function destroy($id)
    {
        $price = DesignatorPackagePrice::findOrFail($id);
        $price->delete();

        return back()->with('success', 'Harga designator berhasil dihapus');
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
            $packageCode = strtoupper($this->cleanValue($data['package_code'] ?? null));
            $priceValue = $this->cleanNumber($data['price'] ?? null);

            if (!$designatorCode || !$packageCode || $priceValue === null) {
                $skipped++;
                continue;
            }

            $designator = Designator::where('designator', $designatorCode)->first();
            $package = Package::where('package_code', $packageCode)->first();

            if (!$designator || !$package) {
                $skipped++;
                continue;
            }

            $existing = DesignatorPackagePrice::where('designator_id', $designator->id_designator)
                ->where('package_id', $package->id_package)
                ->first();

            DesignatorPackagePrice::updateOrCreate(
                [
                    'designator_id' => $designator->id_designator,
                    'package_id' => $package->id_package,
                ],
                [
                    'price' => $priceValue,
                ]
            );

            if ($existing) {
                $updated++;
            } else {
                $imported++;
            }
        }

        fclose($file);

        return back()->with(
            'success',
            "Import harga selesai. {$imported} data baru, {$updated} diperbarui, {$skipped} dilewati."
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

    private function cleanNumber($value)
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return null;
        }

        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        return is_numeric($value) ? $value : null;
    }
}