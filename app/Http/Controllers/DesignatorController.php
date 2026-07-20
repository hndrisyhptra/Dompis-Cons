<?php

namespace App\Http\Controllers;

use App\Models\Designator;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DesignatorController extends Controller
{
    public function index(Request $request)
    {
        // Eager load customer agar tidak N+1 query
        $query = Designator::query()->with('customer');

        // Filter berdasarkan Customer (TIF / Mitratel)
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

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
            ->orderBy('customer_id')
            ->orderBy('designator')
            ->paginate(10)
            ->withQueryString();

        $progressCategories = [
            'KABEL',
            'TIANG',
            'OTHER',
        ];

        // Tarik data active customer untuk dropdown filter/upload
        $customers = Customer::active()->get();

        return view('admin.designators.index', [
            'designators' => $designators,
            'progressCategories' => $progressCategories,
            'customers' => $customers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'       => 'required|exists:customers,id_customer',
            'designator'        => 'required|string|max:100',
            'item_name'         => 'required|string|max:500',
            'unit'              => 'required|string|max:50',
            'type'              => 'nullable|in:material,jasa',
            'pair_code'         => 'nullable|string|max:100',
            'progress_category' => 'nullable|string|max:50',
        ]);

        $designatorCode = strtoupper(trim($validated['designator']));

        Designator::create([
            'customer_id'       => $validated['customer_id'],
            'designator'        => $designatorCode,
            'item_name'         => $validated['item_name'],
            'unit'              => $validated['unit'],
            'type'              => $validated['type'] ?: $this->guessType($designatorCode),
            'pair_code'         => $validated['pair_code'] ?: $this->guessPairCode($designatorCode),
            'progress_category' => $validated['progress_category'] ?? 'OTHER',
        ]);

        return back()->with('success', 'Designator berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $designator = Designator::findOrFail($id);

        $validated = $request->validate([
            'customer_id'       => 'required|exists:customers,id_customer',
            'designator'        => 'required|string|max:100',
            'item_name'         => 'required|string|max:500',
            'unit'              => 'required|string|max:50',
            'type'              => 'nullable|in:material,jasa',
            'pair_code'         => 'nullable|string|max:100',
            'progress_category' => 'nullable|string|max:50',
        ]);

        $designatorCode = strtoupper(trim($validated['designator']));

        $designator->update([
            'customer_id'       => $validated['customer_id'],
            'designator'        => $designatorCode,
            'item_name'         => $validated['item_name'],
            'unit'              => $validated['unit'],
            'type'              => $validated['type'] ?: $this->guessType($designatorCode),
            'pair_code'         => $validated['pair_code'] ?: $this->guessPairCode($designatorCode),
            'progress_category' => $validated['progress_category'] ?? 'OTHER',
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
        // Pastikan form import mengirimkan customer_id
        $request->validate([
            'file' => 'required|mimes:csv,txt',
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
        $rowNumber = 1; // Header adalah baris 1
        
        // Array penampung baris-baris yang error
        $validationErrors = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file, 10000, $delimiter)) !== false) {
                $rowNumber++; // Naikkan nomor baris setiap kali loop

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

                // AMBIL DATA & CEK KOLOM KOSONG
                $itemName = $this->cleanValue($data['item_name'] ?? null);
                $unitValue = $this->cleanValue($data['unit'] ?? null);

                $missingCols = [];
                if ($itemName === null) $missingCols[] = 'item_name';
                if ($unitValue === null) $missingCols[] = 'unit';

                // Jika ada kolom mandatory yang kosong, catat ke dalam array error
                if (!empty($missingCols)) {
                    $validationErrors[] = "Baris {$rowNumber} (Desg: {$designatorCode}) -> kolom " . implode(' & ', $missingCols) . " kosong";
                    continue; // Lewati proses simpan untuk baris ini, lanjut cari error di baris lain
                }

                $csvType = strtolower($this->cleanValue($data['type'] ?? '') ?? '');
                
                // Logika Penentuan Type (Mendukung Virtual Split)
                $typesToProcess = [];
                if (in_array($csvType, ['material', 'jasa'])) {
                    $typesToProcess[] = $csvType; 
                } else {
                    $guessedType = $this->guessType($designatorCode);
                    if ($guessedType) {
                        $typesToProcess[] = $guessedType; 
                    } else {
                        // AUTO VIRTUAL SPLIT
                        $typesToProcess = ['material', 'jasa'];
                    }
                }

                $pairCode = $this->cleanValue($data['pair_code'] ?? null);
                if (!$pairCode) {
                    $pairCode = $this->guessPairCode($designatorCode);
                }
                
                $progressCategory = strtoupper($this->cleanValue($data['progress_category'] ?? 'OTHER'));

                // Looping tipe (Bisa 1 kali untuk TIF, bisa 2 kali untuk Mitratel yang auto-split)
                foreach ($typesToProcess as $type) {
                    $payload = [
                        'item_name'         => $itemName, // Data sudah pasti terisi (lolos validasi di atas)
                        'unit'              => $unitValue, // Data sudah pasti terisi
                        'type'              => $type,
                        'pair_code'         => $pairCode,
                        'progress_category' => $progressCategory,
                    ];

                    // Upsert logic dengan Composite Key (customer_id + designator + type)
                    $designator = Designator::where('customer_id', $customerId)
                                            ->where('designator', $designatorCode)
                                            ->where('type', $type)
                                            ->first();

                    if ($designator) {
                        $designator->update($payload);
                        $updated++;
                    } else {
                        Designator::create(array_merge($payload, [
                            'customer_id' => $customerId,
                            'designator'  => $designatorCode,
                        ]));
                        $imported++;
                    }
                }
            }

            // CEK APAKAH ADA ERROR SEBELUM DISIMPAN PERMANEN
            if (count($validationErrors) > 0) {
                // Batalkan semua query ke database
                DB::rollBack();
                fclose($file);
                
                // Susun pesan peringatan yang mudah dibaca Admin
                $errorMsg = "Import dibatalkan! Silakan lengkapi CSV Anda terlebih dahulu: ";
                $errorMsg .= implode(' | ', array_slice($validationErrors, 0, 5)); // Tampilkan max 5 error pertama
                
                if (count($validationErrors) > 5) {
                    $errorMsg .= " | ...dan " . (count($validationErrors) - 5) . " baris lainnya.";
                }

                return back()->with('error', $errorMsg);
            }

            // Jika bersih tanpa error, simpan permanen!
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            Log::error('Import Designator Gagal: ' . $e->getMessage());
            // Menampilkan error MySQL asli ke layar (sangat membantu untuk Debugging)
            return back()->with('error', 'Sistem error: ' . $e->getMessage());

            // DEBUG TAMPIL PESAN ERROR ASLINYA KE LAYAR
            //return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
        fclose($file);

        return back()->with(
            'success',
            "Import selesai. {$imported} baris baru (termasuk virtual split), {$updated} diperbarui, {$skipped} dilewati."
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

        return null; // Return null jika tidak ada prefix (untuk memicu Virtual Split)
    }

    private function guessPairCode($designator)
    {
        if (!$designator) {
            return null;
        }
        
        // Coba hilangkan M-/J- jika ada, jika tidak ada (Mitratel), pair_code sama dengan designator
        $guessed = preg_replace('/^[MJ]-/i', '', strtoupper($designator));
        return $guessed;
    }
}