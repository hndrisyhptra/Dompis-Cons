@extends('layouts.teknisi')

@section('content')
@php
    // Tarik data survey jika sudah ada
    $survey = $project->pt2Survey;
    $detailData = $survey && $survey->detail_data ? json_decode($survey->detail_data, true) : [];
@endphp

<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    {{-- Header --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-6 shadow-md rounded-b-[1.7rem]">
        <div class="flex items-center gap-4">
            <a href="{{ route('teknisi.pt2.inbox') }}" class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white backdrop-blur-sm active:scale-95 transition">
                ‹
            </a>
            <div>
                <p class="text-xs text-blue-200 font-medium uppercase tracking-widest">Step 1 dari 5</p>
                <h1 class="text-lg font-black tracking-tight leading-tight mt-0.5">Form Survey & Mode</h1>
            </div>
        </div>
    </div>

    <div class="px-5 mt-6">
        <form id="step1Form" action="{{ route('teknisi.pt2.storeStep1', $project->id_project) }}" method="POST">
            @csrf

            {{-- 1. PILIH STATUS SURVEY --}}
            <div class="mb-6">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Hasil Survey Lapangan <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="status_survey" value="eksekusi" class="peer sr-only" onchange="toggleForm()">
                        <div class="p-3 bg-white border-2 border-slate-200 rounded-2xl text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 transition">
                            <span class="flex justify-center items-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#00a303" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-arrow-up-right-icon lucide-square-arrow-up-right"><path d="M15 15V9H9"/><path d="m9 15 6-6"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                            </span>
                            <span class="text-xs font-bold text-green-700">Bisa Dieksekusi</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="status_survey" value="kendala" class="peer sr-only" onchange="toggleForm()">
                        <div class="p-3 bg-white border-2 border-slate-200 rounded-2xl text-center peer-checked:border-red-600 peer-checked:bg-red-50 transition">
                            <span class="flex justify-center items-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d5b201" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-alert-icon lucide-shield-alert"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>    
                            </span>
                            <span class="text-xs font-bold text-yellow-700">Terkendala</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- FORM KENDALA --}}
            <div id="formKendala" class="hidden mb-6 bg-red-50 p-4 rounded-2xl border border-red-100">
                <label class="block text-xs font-black text-red-800 uppercase tracking-widest mb-2">Deskripsi Kendala <span class="text-red-500">*</span></label>
                <textarea name="kendala_note" rows="3" placeholder="Jelaskan alasan kendala..." 
                          class="w-full rounded-xl border border-red-200 p-3 text-sm focus:ring-2 focus:ring-red-400 outline-none">{{ $survey->kendala_note ?? '' }}</textarea>
            </div>

            {{-- FORM EKSEKUSI --}}
            <div id="formEksekusi" class="hidden space-y-5">
                
                {{-- PILIH MODE --}}
                <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100">
                    <label class="block text-xs font-black text-blue-800 uppercase tracking-widest mb-3">Tentukan Mode <span class="text-red-500">*</span></label>
                    <div class="space-y-2">
                        @foreach(['A' => 'A. MODE EXPAND', 'B' => 'B. EXPAND ADD SPLITTER 1:8', 'C' => 'C. PT2 SIMPLE'] as $val => $label)
                        <label class="cursor-pointer flex">
                            <input type="radio" name="mode" value="{{ $val }}" class="peer sr-only" onchange="toggleModeData()"
                                {{ ($survey && $survey->mode === $val) ? 'checked' : '' }}>
                            <div class="w-full py-2.5 bg-white border border-blue-200 rounded-xl text-center font-bold text-sm text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white transition">
                                {{ $label }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- DYNAMIC FIELD CONTAINER --}}
                <div id="modeFieldsContainer" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hidden space-y-4">
                    
                    {{-- Khusus Mode A --}}
                    <div id="fieldSubModeA" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-2">Tindakan Khusus (Mode A) <span class="text-red-500">*</span></label>
                        <select name="sub_mode_a" class="w-full h-11 rounded-xl border border-slate-200 px-3 text-sm outline-none bg-slate-50">
                            <option value="">Pilih tindakan...</option>
                            <option value="Expand Splitter 1:16" {{ ($survey && $survey->sub_mode_a === 'Expand Splitter 1:16') ? 'selected' : '' }}>Expand Splitter 1:16</option>
                            <option value="Ganti ODP" {{ ($survey && $survey->sub_mode_a === 'Ganti ODP') ? 'selected' : '' }}>Ganti ODP</option>
                        </select>
                    </div>

                    {{-- Khusus Mode B --}}
                    <div id="fieldPossibleB" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-2">Possible Add 1:8 <span class="text-red-500">*</span></label>
                        <select name="possible_add" class="w-full h-11 rounded-xl border border-slate-200 px-3 text-sm outline-none bg-slate-50">
                            <option value="">Pilih opsi...</option>
                            <option value="OK" {{ (isset($detailData['possible_add']) && $detailData['possible_add'] === 'OK') ? 'selected' : '' }}>OK</option>
                            <option value="NOK" {{ (isset($detailData['possible_add']) && $detailData['possible_add'] === 'NOK') ? 'selected' : '' }}>NOK</option>
                        </select>
                    </div>

                    {{-- Khusus Mode C --}}
                    <div id="fieldOpsiC" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-2">Opsi PT2 Simple <span class="text-red-500">*</span></label>
                        <select name="opsi_simple" class="w-full h-11 rounded-xl border border-slate-200 px-3 text-sm outline-none bg-slate-50">
                            <option value="">Pilih opsi...</option>
                            <option value="OK" {{ (isset($detailData['opsi_simple']) && $detailData['opsi_simple'] === 'OK') ? 'selected' : '' }}>OK</option>
                            <option value="NOK" {{ (isset($detailData['opsi_simple']) && $detailData['opsi_simple'] === 'NOK') ? 'selected' : '' }}>NOK</option>
                        </select>
                    </div>

                    {{-- Input Bersama / Dinamis --}}
                    <div id="fieldOdpName" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-1">Nama ODP <span class="text-red-500">*</span></label>
                        <input type="text" name="odp_name" value="{{ $survey->odp_name ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm outline-none focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 mb-1">Distribusi <span class="text-red-500">*</span></label>
                        <input type="text" name="distribusi" value="{{ $survey->distribusi ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm outline-none focus:border-blue-500">
                    </div>

                    <div id="fieldKabel" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-1">Tipe Kabel <span class="text-red-500">*</span></label>
                        <input type="text" name="tipe_kabel" value="{{ $survey->tipe_kabel ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm outline-none focus:border-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1">Core <span id="labelCore">Ex</span> <span class="text-red-500">*</span></label>
                            <input type="text" name="core_ex" value="{{ $survey->core_ex ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm outline-none focus:border-blue-500">
                        </div>
                        <div id="fieldPowerOut" class="hidden">
                            <label class="block text-[11px] font-black text-slate-500 mb-1">Power Out (dBm) <span class="text-red-500">*</span></label>
                            <input type="text" name="power_out" value="{{ $survey->power_out ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm outline-none focus:border-blue-500">
                        </div>
                        <div id="fieldPowerIn" class="hidden">
                            <label class="block text-[11px] font-black text-slate-500 mb-1">Power IN Feeder <span class="text-red-500">*</span></label>
                            <input type="text" name="power_in_feeder" value="{{ $survey->power_in_feeder ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 mb-1">Kesimpulan Lapangan <span class="text-[10px] font-normal text-slate-400">(Opsional)</span></label>
                        <textarea name="kesimpulan" rows="2" class="w-full rounded-xl border border-slate-200 p-3 text-sm outline-none focus:border-blue-500">{{ $survey->kesimpulan ?? '' }}</textarea>
                    </div>

                </div>

                {{-- INPUT KEBUTUHAN MATERIAL (BOQ Review) --}}
                <div id="materialContainer" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <label class="block text-xs font-black text-slate-800">Review BOQ Material</label>
                            <p class="text-[10px] text-slate-400">Tambahkan material jika ada</p>
                        </div>
                        <button type="button" onclick="addMaterialRow()" class="px-3 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded-lg text-xs font-bold transition">+ Tambah</button>
                    </div>

                    <div class="overflow-hidden border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="p-2 font-bold text-slate-600">Nama Material</th>
                                    <th class="p-2 font-bold text-slate-600 w-16 text-center">Qty</th>
                                    <th class="p-2 font-bold text-slate-600 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="materialTableBody" class="divide-y divide-slate-100">
                                {{-- Diisi dari JS saat dimuat ulang --}}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- Tombol Submit --}}
            <button type="submit" id="btnSubmit" disabled 
                class="w-full h-12 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl mt-8 shadow-sm transition-all duration-300 flex items-center justify-center gap-2">
                <span>Simpan & Lanjut</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>

        </form>
    </div>
</div>

<script>
    const designatorList = @json($designators);
    const existingBoq = @json($project->boqItems);

    document.getElementById('step1Form').addEventListener('input', checkFormValidity);
    document.getElementById('step1Form').addEventListener('change', checkFormValidity);

    // KETIKA HALAMAN PERTAMA KALI DIBUKA
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelector('input[name="status_survey"]:checked')) {
            toggleForm();
        }
        if (document.querySelector('input[name="mode"]:checked')) {
            toggleModeData();
        }
        
        // Memuat ulang material yang sudah disimpan (jika ada)
        if(existingBoq && existingBoq.length > 0) {
            existingBoq.forEach(item => {
                addMaterialRow(item.designator_id, item.quantity_plan);
            });
        }
        
        checkFormValidity();
    });

    function toggleForm() {
        let status = document.querySelector('input[name="status_survey"]:checked');
        document.getElementById('formKendala').classList.add('hidden');
        document.getElementById('formEksekusi').classList.add('hidden');
        
        if (status && status.value === 'kendala') {
            document.getElementById('formKendala').classList.remove('hidden');
        } else if (status && status.value === 'eksekusi') {
            document.getElementById('formEksekusi').classList.remove('hidden');
        }
        checkFormValidity();
    }

    function toggleModeData() {
        let modeInput = document.querySelector('input[name="mode"]:checked');
        if (!modeInput) return;
        let mode = modeInput.value;
        
        document.getElementById('modeFieldsContainer').classList.remove('hidden');
        document.getElementById('materialContainer').classList.remove('hidden');

        let hideElements = ['fieldSubModeA', 'fieldPossibleB', 'fieldOpsiC', 'fieldOdpName', 'fieldKabel', 'fieldPowerOut', 'fieldPowerIn'];
        hideElements.forEach(id => {
            document.getElementById(id).classList.add('hidden');
        });

        if (mode === 'A') {
            document.getElementById('fieldSubModeA').classList.remove('hidden');
            document.getElementById('fieldOdpName').classList.remove('hidden');
            document.getElementById('fieldPowerOut').classList.remove('hidden');
            document.getElementById('labelCore').innerText = 'Ex';
        } else if (mode === 'B') {
            document.getElementById('fieldPossibleB').classList.remove('hidden');
            document.getElementById('fieldPowerIn').classList.remove('hidden');
            document.getElementById('labelCore').innerText = ''; 
        } else if (mode === 'C') {
            document.getElementById('fieldOpsiC').classList.remove('hidden');
            document.getElementById('fieldKabel').classList.remove('hidden');
            document.getElementById('fieldPowerIn').classList.remove('hidden');
            document.getElementById('labelCore').innerText = ''; 
        }
        checkFormValidity();
    }

    function checkFormValidity() {
        let isValid = false;
        let status = document.querySelector('input[name="status_survey"]:checked');
        let btnSubmit = document.getElementById('btnSubmit');

        if (status && status.value === 'kendala') {
            let note = document.querySelector('textarea[name="kendala_note"]').value.trim();
            isValid = note.length > 0;
        } 
        else if (status && status.value === 'eksekusi') {
            let mode = document.querySelector('input[name="mode"]:checked');
            if (mode) {
                let m = mode.value;
                let dist = document.querySelector('input[name="distribusi"]').value.trim();
                let core = document.querySelector('input[name="core_ex"]').value.trim();
                
                let matsValid = true;
                document.querySelectorAll('select[name="materials[]"]').forEach(el => { if(!el.value) matsValid = false; });
                document.querySelectorAll('input[name="qty[]"]').forEach(el => { if(!el.value) matsValid = false; });

                if (m === 'A') {
                    let subMode = document.querySelector('select[name="sub_mode_a"]').value;
                    let odp = document.querySelector('input[name="odp_name"]').value.trim();
                    let pout = document.querySelector('input[name="power_out"]').value.trim();
                    if(subMode && odp && dist && core && pout && matsValid) isValid = true;
                } else if (m === 'B') {
                    let posAdd = document.querySelector('select[name="possible_add"]').value;
                    let pin = document.querySelector('input[name="power_in_feeder"]').value.trim();
                    if(posAdd && dist && core && pin && matsValid) isValid = true;
                } else if (m === 'C') {
                    let opsi = document.querySelector('select[name="opsi_simple"]').value;
                    let kabel = document.querySelector('input[name="tipe_kabel"]').value.trim();
                    let pin = document.querySelector('input[name="power_in_feeder"]').value.trim();
                    if(opsi && dist && kabel && core && pin && matsValid) isValid = true;
                }
            }
        }

        if (isValid) {
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btnSubmit.classList.add('bg-blue-600', 'hover:bg-blue-700', 'text-white', 'shadow-lg');
            // Jika data sudah pernah diisi, kita ubah teks tombolnya
            @if($survey) btnSubmit.querySelector('span').innerText = "Update & Lanjut Step 2"; @endif
        } else {
            btnSubmit.disabled = true;
            btnSubmit.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btnSubmit.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'text-white', 'shadow-lg');
        }
    }

    function addMaterialRow(selectedId = null, qtyValue = '') {
        let tbody = document.getElementById('materialTableBody');
        let tr = document.createElement('tr');
        
        let options = '<option value="">Pilih Material...</option>';
        designatorList.forEach(item => {
            let selected = (selectedId == item.id_designator) ? 'selected' : '';
            options += `<option value="${item.id_designator}" ${selected}>${item.designator} - ${item.item_name}</option>`;
        });

        tr.innerHTML = `
            <td class="p-1.5 border-r border-slate-100">
                <select name="materials[]" class="w-full text-[11px] rounded bg-white border border-slate-200 outline-none p-2 focus:border-blue-500">
                    ${options}
                </select>
            </td>
            <td class="p-1.5 border-r border-slate-100">
                <input type="number" name="qty[]" min="1" value="${qtyValue}" placeholder="0" class="w-full text-center text-xs rounded bg-white border border-slate-200 p-2 outline-none focus:border-blue-500">
            </td>
            <td class="p-1.5 text-center">
                <button type="button" onclick="removeRow(this)" class="w-6 h-6 bg-red-50 text-red-600 rounded text-xs font-bold hover:bg-red-100 transition">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
        checkFormValidity();
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        checkFormValidity();
    }
</script>
@endsection