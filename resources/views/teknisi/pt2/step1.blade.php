@extends('layouts.teknisi') <!-- Sesuaikan dengan layout Anda -->

@section('content')
<div class="container mx-auto px-4 py-6" x-data="surveyForm()">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Step 1: Form Survey PT2</h2>
        <p class="text-gray-600 mb-6">PID: {{ $project->pid }}</p>

        <form action="{{ route('teknisi.pt2.step1.store', $project->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- STATUS SURVEY -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Hasil Survey</label>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="status_survey" value="eksekusi" x-model="statusSurvey" class="form-radio text-blue-600">
                        <span class="ml-2">Bisa Eksekusi</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="status_survey" value="kendala" x-model="statusSurvey" class="form-radio text-red-600">
                        <span class="ml-2">Kendala (Butuh Approval PM)</span>
                    </label>
                </div>
            </div>

            <!-- JIKA KENDALA -->
            <div x-show="statusSurvey === 'kendala'" class="mb-6 bg-red-50 p-4 rounded-md border border-red-200" style="display: none;">
                <label class="block text-sm font-medium text-red-700 mb-1">Catatan Kendala</label>
                <textarea name="kendala_note" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"></textarea>
                <p class="text-xs text-red-500 mt-1">*Jika disimpan, pekerjaan ini akan menunggu Approval dari PM.</p>
            </div>

            <!-- JIKA BISA EKSEKUSI -->
            <div x-show="statusSurvey === 'eksekusi'" style="display: none;">
                
                <!-- PILIH MODE -->
                <div class="mb-6 border-b pb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Mode Eksekusi</label>
                    <select name="mode" x-model="mode" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Pilih Mode --</option>
                        <option value="A">Mode A: Expand (Splitter 1:16) / Ganti ODP</option>
                        <option value="B">Mode B: Expand Add Splitter 1:8</option>
                        <option value="C">Mode C: PT2 Simple</option>
                    </select>
                </div>

                <!-- FORM MODE A -->
                <template x-if="mode === 'A'">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
                        <h3 class="font-bold text-blue-800 mb-3">Detail Mode A</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Tipe Pekerjaan Mode A</label>
                            <select name="sub_mode_a" class="mt-1 w-full border-gray-300 rounded-md">
                                <option value="expand_16">EXPAND (SPLITTER 1:16)</option>
                                <option value="ganti_odp">GANTI ODP (EXPAND +)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div><label class="text-sm">Nama ODP</label><input type="text" name="nama_odp" class="w-full rounded border-gray-300"></div>
                            <div><label class="text-sm">Distribusi</label><input type="text" name="distribusi" class="w-full rounded border-gray-300"></div>
                            <div><label class="text-sm">Core Ex</label><input type="text" name="core_ex" class="w-full rounded border-gray-300"></div>
                            <div><label class="text-sm">Power (dBm)</label><input type="number" step="0.01" name="power_dbm" class="w-full rounded border-gray-300"></div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="text-sm">Kesimpulan (Opsional)</label>
                            <textarea name="kesimpulan" rows="2" class="w-full rounded border-gray-300"></textarea>
                        </div>

                        <!-- Eviden Upload -->
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div><label class="text-sm font-medium">Foto Eviden Power IN</label><input type="file" name="foto_power_in" class="w-full"></div>
                            <div><label class="text-sm font-medium">Foto Eviden Power OUT</label><input type="file" name="foto_power_out" class="w-full"></div>
                        </div>
                    </div>
                </template>

                <!-- FORM MODE B -->
                <template x-if="mode === 'B'">
                    <div class="bg-green-50 p-4 rounded-lg border border-green-100 mb-6">
                        <h3 class="font-bold text-green-800 mb-3">Detail Mode B</h3>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div><label class="text-sm">POSSIBLE ADD 1:8 (OK/NOK)</label>
                                <select name="possible_add" class="w-full rounded border-gray-300">
                                    <option value="OK">OK</option>
                                    <option value="NOK">NOK</option>
                                </select>
                            </div>
                            <div><label class="text-sm">Distribusi</label><input type="text" name="distribusi" class="w-full rounded border-gray-300"></div>
                            <div><label class="text-sm">Core</label><input type="text" name="core" class="w-full rounded border-gray-300"></div>
                            <div><label class="text-sm">Power IN Feeder (dBm)</label><input type="number" step="0.01" name="power_in_feeder" class="w-full rounded border-gray-300"></div>
                        </div>

                        <div class="mb-4">
                            <label class="text-sm">Kesimpulan (Opsional)</label>
                            <textarea name="kesimpulan" rows="2" class="w-full rounded border-gray-300"></textarea>
                        </div>

                        <!-- Eviden Upload -->
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div><label class="text-sm font-medium">Foto Base Tray Feeder</label><input type="file" name="foto_basetray_feeder" class="w-full"></div>
                            <div><label class="text-sm font-medium">Foto Base Tray Distribusi</label><input type="file" name="foto_basetray_dist" class="w-full"></div>
                            <div><label class="text-sm font-medium">Foto Power IN Feeder</label><input type="file" name="foto_power_in_feeder" class="w-full"></div>
                            <div><label class="text-sm font-medium">Foto Power OUT Splitter Ex</label><input type="file" name="foto_power_out_splitter" class="w-full"></div>
                        </div>
                    </div>
                </template>

                <!-- FORM MODE C -->
                <template x-if="mode === 'C'">
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100 mb-6">
                        <h3 class="font-bold text-yellow-800 mb-3">Detail Mode C</h3>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div><label class="text-sm">Opsi PT2 SIMPLE (OK/NOK)</label>
                                <select name="opsi_simple" class="w-full rounded border-gray-300">
                                    <option value="OK">OK</option>
                                    <option value="NOK">NOK</option>
                                </select>
                            </div>
                            <div><label class="text-sm">Distribusi</label><input type="text" name="distribusi" class="w-full rounded border-gray-300"></div>
                            <div><label class="text-sm">Tipe Kabel</label><input type="text" name="tipe_kabel" class="w-full rounded border-gray-300"></div>
                            <div><label class="text-sm">Core</label><input type="text" name="core" class="w-full rounded border-gray-300"></div>
                            <div class="col-span-2"><label class="text-sm">Power IN Feeder (dBm)</label><input type="number" step="0.01" name="power_in_feeder" class="w-full rounded border-gray-300"></div>
                        </div>

                        <div class="mb-4">
                            <label class="text-sm">Kesimpulan (Opsional)</label>
                            <textarea name="kesimpulan" rows="2" class="w-full rounded border-gray-300"></textarea>
                        </div>

                        <!-- Eviden Upload -->
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div><label class="text-sm font-medium">Foto Base Tray Feeder</label><input type="file" name="foto_basetray_feeder" class="w-full"></div>
                            <div><label class="text-sm font-medium">Foto Base Tray Distribusi</label><input type="file" name="foto_basetray_dist" class="w-full"></div>
                        </div>
                    </div>
                </template>

                <!-- REVIEW MATERIAL BOQ (NON-PRICE) -->
                <div x-show="mode !== ''" class="mt-6 border-t pt-4">
                    <h3 class="text-lg font-bold mb-2">Pilih Item Designator (Review BOQ)</h3>
                    <p class="text-sm text-gray-500 mb-4">*Hanya untuk review, harga jasa otomatis terikat pada sistem (Paten).</p>
                    
                    <!-- Ini bisa dipadukan dengan modal/Select2 Designator yang biasa digunakan dompis_cons -->
                    <button type="button" class="bg-gray-200 text-gray-800 px-4 py-2 rounded shadow text-sm hover:bg-gray-300">
                        + Tambah Material
                    </button>
                    <!-- List Selected Material Akan Muncul Disini -->
                </div>

            </div>

            <!-- Tombol Submit -->
            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 shadow">Simpan & Lanjut</button>
            </div>
        </form>
    </div>
</div>

<script>
    function surveyForm() {
        return {
            statusSurvey: '', // eksekusi | kendala
            mode: ''          // A | B | C
        }
    }
</script>
@endsection