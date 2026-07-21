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
            'package_id'  => 'required|exists:packages,id_package', // <-- Tambahan validasi package_id
        ]);

        $customerId = $request->customer_id;
        $packageId  = $request->package_id; // <-- Ambil langsung dari request dropdown
        
        $filePath = $request->file('file')->getRealPath();
        $file = fopen($filePath, 'r');

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
        $rowNumber = 1;

        $validationErrors = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file, 10000, $delimiter)) !== false) {
                $rowNumber++;

                if (count($row) === 1 && ($row[0] === null || trim($row[0]) === '')) {
                    continue;
                }

                if (count($row) !== count($header)) {
                    $skipped++;
                    continue;
                }

                $data = array_combine($header, $row);

                $designatorCode = strtoupper($this->cleanValue($data['designator'] ?? null) ?? '');
                $priceValue     = $this->cleanNumber($data['price'] ?? null);
                $type           = strtolower($this->cleanValue($data['type'] ?? '') ?? '');

                $missingCols = [];
                if (empty($designatorCode)) $missingCols[] = 'designator';
                if ($priceValue === null) $missingCols[] = 'price (angka tidak valid)';

                if (!empty($missingCols)) {
                    $validationErrors[] = "Baris {$rowNumber} -> kolom " . implode(', ', $missingCols) . " kosong/salah";
                    continue;
                }

                // Query pencarian designator berdasarkan customer_id
                $designatorQuery = \App\Models\Designator::where('designator', $designatorCode)
                    ->where('customer_id', $customerId);

                if ($type && in_array($type, ['material', 'jasa'])) {
                    $designatorQuery->where('type', $type);
                }

                $designators = $designatorQuery->get();

                if ($designators->isEmpty()) {
                    $validationErrors[] = "Baris {$rowNumber} -> Designator '{$designatorCode}' tidak ditemukan di Master Customer";
                    continue;
                }

                // Proteksi Ambiguitas
                if ($designators->count() > 1 && empty($type)) {
                    $validationErrors[] = "Baris {$rowNumber} -> '{$designatorCode}' butuh kolom 'type' (material/jasa) karena ada 2 data di Master";
                    continue;
                }

                foreach ($designators as $designator) {
                    $existing = \App\Models\DesignatorPackagePrice::where('designator_id', $designator->id_designator)
                        ->where('package_id', $packageId) // <-- Langsung gunakan variabel dari dropdown
                        ->first();

                    \App\Models\DesignatorPackagePrice::updateOrCreate(
                        [
                            'designator_id' => $designator->id_designator,
                            'package_id'    => $packageId,
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

            if (count($validationErrors) > 0) {
                DB::rollBack();
                fclose($file);
                
                $errorMsg = "Import dibatalkan! Silakan perbaiki CSV Anda: ";
                $errorMsg .= implode(' | ', array_slice($validationErrors, 0, 4));
                
                if (count($validationErrors) > 4) {
                    $errorMsg .= " | ...dan " . (count($validationErrors) - 4) . " error lainnya.";
                }

                return back()->with('error', $errorMsg);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            Log::error('Import Harga Designator Gagal: ' . $e->getMessage());
            return back()->with('error', 'Sistem error: ' . $e->getMessage());
        }

        fclose($file);

        return back()->with(
            'success',
            "Import harga selesai. {$imported} data baru, {$updated} diperbarui, {$skipped} baris dilewati."
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