<?php

namespace App\Http\Controllers;

use App\Models\Designator;
use App\Models\Package;
use App\Models\Customer;
use App\Models\DesignatorPackagePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DesignatorPriceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $customerId = $request->customer_id;

        $query = DesignatorPackagePrice::with(['designator.customer', 'package'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('designator', function ($subQ) use ($search) {
                    $subQ->where('designator', 'like', "%{$search}%")
                         ->orWhere('item_name', 'like', "%{$search}%")
                         ->orWhere('type', 'like', "%{$search}%");
                })
                ->orWhereHas('package', function ($subQ) use ($search) {
                    $subQ->where('package_code', 'like', "%{$search}%")
                         ->orWhere('package_name', 'like', "%{$search}%");
                });
            })
            ->when($customerId, function ($q) use ($customerId) {
                // Filter harga berdasarkan package yang dimiliki customer tersebut
                $q->whereHas('package', function ($subQ) use ($customerId) {
                    $subQ->where('customer_id', $customerId);
                });
            });

        $prices = $query->latest()->paginate(10)->withQueryString();

        // Data master untuk modal tambah/edit manual
        $customers = Customer::active()->get();
        // Tarik designator & package dengan menyertakan relasinya agar bisa difilter via JS di Blade
        $designators = Designator::orderBy('customer_id')->orderBy('designator')->get();
        $packages = Package::orderBy('customer_id')->orderBy('package_code')->get();

        return view('admin.designator-prices.index', compact(
            'prices',
            'designators',
            'packages',
            'customers',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'designator_id' => 'required|exists:designators,id_designator',
            'package_id'    => 'required|exists:packages,id_package',
            'price'         => 'required|numeric|min:0',
        ]);

        DesignatorPackagePrice::updateOrCreate(
            [
                'designator_id' => $request->designator_id,
                'package_id'    => $request->package_id,
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
            'package_id'    => 'required|exists:packages,id_package',
            'price'         => 'required|numeric|min:0',
        ]);

        $price->update([
            'designator_id' => $request->designator_id,
            'package_id'    => $request->package_id,
            'price'         => $request->price,
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
            'file'        => 'required|mimes:csv,txt',
            'customer_id' => 'required|exists:customers,id_customer',
        ]);

        $customerId = $request->customer_id;
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

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file, 10000, $delimiter)) !== false) {

                if (count($row) === 1 && ($row[0] === null || trim($row[0]) === '')) {
                    continue;
                }

                if (count($row) !== count($header)) {
                    $skipped++;
                    continue;
                }

                $data = array_combine($header, $row);

                $designatorCode = strtoupper($this->cleanValue($data['designator'] ?? null) ?? '');
                $packageCode    = strtoupper($this->cleanValue($data['package_code'] ?? null) ?? '');
                $priceValue     = $this->cleanNumber($data['price'] ?? null);
                
                // Cek apakah user mendefinisikan type (material/jasa) di CSV untuk membedakan harga split
                $type = strtolower($this->cleanValue($data['type'] ?? '') ?? '');

                if (!$designatorCode || !$packageCode || $priceValue === null) {
                    $skipped++;
                    continue;
                }

                // Ambil paket yang sesuai dengan kode dan customer yang dipilih
                $package = Package::where('package_code', $packageCode)
                    ->where('customer_id', $customerId)
                    ->first();

                if (!$package) {
                    $skipped++;
                    continue;
                }

                // Query pencarian designator
                $designatorQuery = Designator::where('designator', $designatorCode)
                    ->where('customer_id', $customerId);

                // Jika kolom type diisi di CSV (material/jasa), filter pencariannya
                if ($type && in_array($type, ['material', 'jasa'])) {
                    $designatorQuery->where('type', $type);
                }

                $designators = $designatorQuery->get();

                if ($designators->isEmpty()) {
                    $skipped++;
                    continue;
                }

                // Proteksi Ambiguitas: Jika 1 kode punya > 1 baris (contoh: Mitratel split jadi material & jasa),
                // dan user TIDAK menyertakan kolom type di CSV, maka SKIP (mencegah harga tertimpa ganda dengan nilai sama)
                if ($designators->count() > 1 && empty($type)) {
                    $skipped++;
                    continue;
                }

                foreach ($designators as $designator) {
                    $existing = DesignatorPackagePrice::where('designator_id', $designator->id_designator)
                        ->where('package_id', $package->id_package)
                        ->first();

                    DesignatorPackagePrice::updateOrCreate(
                        [
                            'designator_id' => $designator->id_designator,
                            'package_id'    => $package->id_package,
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
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            Log::error('Import Harga Designator Gagal: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat mengimport harga data.');
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

        // Deteksi format US (contoh: 9,254.00) -> Hapus koma pemisah ribuan
        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            if (strrpos($value, '.') > strrpos($value, ',')) {
                $value = str_replace(',', '', $value); 
            } else {
                // Deteksi format ID (contoh: 9.254,00)
                $value = str_replace('.', '', $value); 
                $value = str_replace(',', '.', $value);
            }
        } 
        // Jika hanya ada koma (contoh: 9254,50) -> jadikan titik untuk desimal MySQL
        elseif (strpos($value, ',') !== false) {
            $value = str_replace(',', '.', $value);
        }
        
        // Hapus pemisah ribuan jika titiknya lebih dari 1 (contoh: 9.254.000)
        if (substr_count($value, '.') > 1) {
            $value = str_replace('.', '', $value);
        }

        // Bersihkan karakter selain angka dan titik desimal final
        $value = preg_replace('/[^0-9.]/', '', $value);

        return is_numeric($value) ? $value : null;
    }
}