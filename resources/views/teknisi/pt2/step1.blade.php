@extends('layouts.teknisi')

@section('content')
@php
    $survey = $project->pt2Survey;
    $isEdit = $survey ? true : false;
    $detailData = $survey ? json_decode($survey->detail_data, true) : [];
@endphp

<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    {{-- Header STEPPER --}}
    @include('teknisi.partials.stepper', ['title' => 'Step 1 - Form Survey'])

    {{-- PROJECT INFO CARD - Badge Style --}}
    <div class="px-4 mt-3">
        <div class="bg-white rounded-xl border border-gray-200/80 p-3 shadow-xs">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">Nama LOP</p>
            <p class="text-xs font-bold text-gray-900 leading-snug break-words mb-2.5">
                {{ $project->project_name }}
            </p>
            
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-[11px] font-semibold text-gray-700">
                    <span class="text-gray-400 mr-1 font-normal">STO:</span>
                    <span class="font-mono">{{ $project->lop?->sto ?? '-' }}</span>
                </span>

                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-[11px] font-semibold text-gray-700">
                    <span class="text-gray-400 mr-1 font-normal">Branch:</span>
                    <span>{{ $project->lop?->branch ?? '-' }}</span>
                </span>
            </div>
        </div>
    </div>
    <div class="px-5 mt-6">
        @if($isEdit)
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-xs font-bold flex gap-2 items-center">
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save-check-icon lucide-save-check"><path d="M12.5 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10.2a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4v4.35"/><path d="m16 19 2 2 4-4"/><path d="M17 15.13V14a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                </span> Data Survey sudah tersimpan. Anda bisa mengupdate atau langsung lanjut.
            </div>
        @endif
    
        <form id="step1Form" action="{{ route('teknisi.pt2.storeStep1', $project->id_project) }}" method="POST">
            @csrf

            {{-- 1. PILIH STATUS SURVEY --}}
            <div class="mb-6">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Hasil Survey Lapangan <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="status_survey" value="eksekusi" class="peer sr-only" onchange="toggleForm()" {{ ($isEdit && $survey->has_kendala == 0) ? 'checked' : '' }}>
                        <div class="p-3 bg-white border-2 border-slate-200 rounded-2xl text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 transition">
                           <span class="flex justify-center items-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#00a303" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-arrow-up-right-icon lucide-square-arrow-up-right"><path d="M15 15V9H9"/><path d="m9 15 6-6"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                            </span>
                            <span class="text-xs font-bold text-green-700">Bisa Dieksekusi</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="status_survey" value="kendala" class="peer sr-only" onchange="toggleForm()" {{ ($isEdit && $survey->has_kendala == 1) ? 'checked' : '' }}>
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
                <textarea name="kendala_note" placeholder="Tuliskan catatan tambahan, kendala di lapangan, atau informasi penting lainnya di sini..." rows="3" class="w-full rounded-xl border border-red-200 p-3 text-sm outline-none">{{ $survey->kendala_note ?? '' }}</textarea>
            </div>

            {{-- FORM EKSEKUSI --}}
            <div id="formEksekusi" class="hidden space-y-5">
                
                {{-- PILIH MODE --}}
                <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100">
                    <label class="block text-xs font-black text-blue-800 uppercase tracking-widest mb-3">Tentukan Mode <span class="text-red-500">*</span></label>
                    <div class="space-y-2">
                        @foreach(['A' => 'A. MODE EXPAND', 'B' => 'B. EXPAND ADD SPLITTER 1:8', 'C' => 'C. PT2 SIMPLE'] as $val => $label)
                        <label class="cursor-pointer flex">
                            <input type="radio" name="mode" value="{{ $val }}" class="peer sr-only" onchange="toggleModeData()" {{ ($isEdit && $survey->mode === $val) ? 'checked' : '' }}>
                            <div class="w-full py-2.5 bg-white border border-blue-200 rounded-xl text-center font-bold text-sm text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white transition">
                                {{ $label }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- DYNAMIC FIELD CONTAINER --}}
                <div id="modeFieldsContainer" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hidden space-y-4">
                    
                    {{-- Mode A --}}
                    <div id="fieldSubModeA" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-2">Tipe Pekerjaan <span class="text-red-500">*</span></label>
                        <select name="sub_mode_a" class="w-full h-11 rounded-xl border border-slate-200 px-3 text-sm bg-slate-50">
                            <option value="">Pilih tindakan...</option>
                            <option value="Expand Splitter 1:16" {{ ($survey->sub_mode_a ?? '') == 'Expand Splitter 1:16' ? 'selected' : '' }}>Expand Splitter 1:16</option>
                            <option value="Ganti ODP" {{ ($survey->sub_mode_a ?? '') == 'Ganti ODP' ? 'selected' : '' }}>Ganti ODP</option>
                        </select>
                    </div>

                    {{-- Mode B --}}
                    <div id="fieldPossibleB" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-2">Possible Add 1:8 <span class="text-red-500">*</span></label>
                        <select name="possible_add" class="w-full h-11 rounded-xl border border-slate-200 px-3 text-sm bg-slate-50">
                            <option value="">Pilih opsi...</option>
                            <option value="OK" {{ ($detailData['possible_add'] ?? '') == 'OK' ? 'selected' : '' }}>OK</option>
                            <option value="NOK" {{ ($detailData['possible_add'] ?? '') == 'NOK' ? 'selected' : '' }}>NOK</option>
                        </select>
                    </div>

                    {{-- Mode C --}}
                    <div id="fieldOpsiC" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-2">Opsi PT2 Simple <span class="text-red-500">*</span></label>
                        <select name="opsi_simple" class="w-full h-11 rounded-xl border border-slate-200 px-3 text-sm bg-slate-50">
                            <option value="">Pilih opsi...</option>
                            <option value="OK" {{ ($detailData['opsi_simple'] ?? '') == 'OK' ? 'selected' : '' }}>OK</option>
                            <option value="NOK" {{ ($detailData['opsi_simple'] ?? '') == 'NOK' ? 'selected' : '' }}>NOK</option>
                        </select>
                    </div>

                    {{-- Inputs --}}
                    <div id="fieldOdpName" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-1">Nama ODP <span class="text-red-500">*</span></label>
                        <input type="text" name="odp_name" value="{{ $survey->odp_name ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 mb-1">Distribusi <span class="text-red-500">*</span></label>
                        <input type="text" name="distribusi" value="{{ $survey->distribusi ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm">
                    </div>

                    <div id="fieldKabel" class="hidden">
                        <label class="block text-xs font-black text-slate-500 mb-1">Tipe Kabel <span class="text-red-500">*</span></label>
                        <input type="text" name="tipe_kabel" value="{{ $survey->tipe_kabel ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 mb-1">Core <span id="labelCore">Ex</span> <span class="text-red-500">*</span></label>
                            <input type="text" name="core_ex" value="{{ $survey->core_ex ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm">
                        </div>
                        <div id="fieldPowerOut" class="hidden">
                            <label class="block text-[11px] font-black text-slate-500 mb-1">Power Out (dBm) <span class="text-red-500">*</span></label>
                            <input type="text" name="power_out" value="{{ $survey->power_out ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm">
                        </div>
                        <div id="fieldPowerIn" class="hidden">
                            <label class="block text-[11px] font-black text-slate-500 mb-1">Power IN <span class="text-red-500">*</span></label>
                            <input type="text" name="power_in_feeder" value="{{ $survey->power_in_feeder ?? '' }}" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 mb-1">Kesimpulan <span class="text-[10px] text-slate-400">(Opsional)</span></label>
                        <textarea name="kesimpulan" rows="2" class="w-full rounded-xl border border-slate-200 p-3 text-sm">{{ $survey->kesimpulan ?? '' }}</textarea>
                    </div>

                </div>

                {{-- BOQ Material --}}
                <div id="materialContainer" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hidden">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-xs font-black text-slate-800">Review BOQ Material</label>
                        <button type="button" onclick="addMaterialRow()" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-bold">+ Tambah</button>
                    </div>
                    <div class="overflow-hidden border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="p-2 font-bold text-slate-600">Material</th>
                                    <th class="p-2 font-bold text-slate-600 w-16 text-center">Qty</th>
                                    <th class="p-2 font-bold text-slate-600 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="materialTableBody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 2. TOMBOL AKSI DINAMIS --}}
            <div class="mt-8 space-y-3">
                @if($isEdit)
                    <div class="grid grid-cols-2 gap-3">
                        <button type="submit" id="btnSubmit" class="w-full h-11 bg-slate-800 text-white font-black rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <span>Update Data</span>
                        </button>
                        {{-- Tombol Lanjut ke Eviden tanpa Update --}}
                        <a href="{{ route('teknisi.pt2.step1Eviden', $project->id_project) }}" class="w-full h-11 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-xl shadow-md transition-all flex items-center justify-center gap-1">
                            <span>Lanjut Eviden</span> →
                        </a>
                    </div>
                @else
                    <button type="submit" id="btnSubmit" disabled class="w-full h-12 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                        <span>Simpan & Lanjut</span>
                    </button>
                @endif
            </div>

        </form>
    </div>
</div>

{{-- BOTTOM NAV --}}
    @include('teknisi.partials.bottom-nav', ['active' => 'home'])

<script>
    const designatorList = @json($designators);
    const existingBoq = @json($project->boqItems ?? []);
    let isEditMode = {{ $isEdit ? 'true' : 'false' }};

    document.getElementById('step1Form').addEventListener('input', checkFormValidity);
    document.getElementById('step1Form').addEventListener('change', checkFormValidity);

    document.addEventListener("DOMContentLoaded", function() {
        toggleForm();
        if(document.querySelector('input[name="mode"]:checked')) {
            toggleModeData();
        }
        
        // Render existing material
        existingBoq.forEach(item => {
            addMaterialRow(item.designator_id, item.quantity_plan);
        });

        checkFormValidity();
    });

    function toggleForm() {
        let status = document.querySelector('input[name="status_survey"]:checked');
        document.getElementById('formKendala').classList.add('hidden');
        document.getElementById('formEksekusi').classList.add('hidden');
        if (status && status.value === 'kendala') document.getElementById('formKendala').classList.remove('hidden');
        else if (status && status.value === 'eksekusi') document.getElementById('formEksekusi').classList.remove('hidden');
        checkFormValidity();
    }

    function toggleModeData() {
        let mode = document.querySelector('input[name="mode"]:checked');
        if (!mode) return;
        let m = mode.value;
        
        document.getElementById('modeFieldsContainer').classList.remove('hidden');
        document.getElementById('materialContainer').classList.remove('hidden');
        
        ['fieldSubModeA', 'fieldPossibleB', 'fieldOpsiC', 'fieldOdpName', 'fieldKabel', 'fieldPowerOut', 'fieldPowerIn'].forEach(id => {
            document.getElementById(id).classList.add('hidden');
        });

        if (m === 'A') {
            document.getElementById('fieldSubModeA').classList.remove('hidden');
            document.getElementById('fieldOdpName').classList.remove('hidden');
            document.getElementById('fieldPowerOut').classList.remove('hidden');
            document.getElementById('labelCore').innerText = 'Ex';
        } else if (m === 'B') {
            document.getElementById('fieldPossibleB').classList.remove('hidden');
            document.getElementById('fieldPowerIn').classList.remove('hidden');
            document.getElementById('labelCore').innerText = ''; 
        } else if (m === 'C') {
            document.getElementById('fieldOpsiC').classList.remove('hidden');
            document.getElementById('fieldKabel').classList.remove('hidden');
            document.getElementById('fieldPowerIn').classList.remove('hidden');
            document.getElementById('labelCore').innerText = ''; 
        }
        checkFormValidity();
    }

    function addMaterialRow(selectedId = '', qty = '') {
        let tbody = document.getElementById('materialTableBody');
        let tr = document.createElement('tr');
        
        let options = '<option value="">Pilih...</option>';
        designatorList.forEach(item => {
            let sel = (selectedId == item.id_designator) ? 'selected' : '';
            options += `<option value="${item.id_designator}" ${sel}>${item.designator} - ${item.item_name}</option>`;
        });

        tr.innerHTML = `
            <td class="p-1.5 border-r border-slate-100">
                <select name="materials[]" class="w-full text-[11px] rounded bg-white border border-slate-200 outline-none p-2">${options}</select>
            </td>
            <td class="p-1.5 border-r border-slate-100">
                <input type="number" name="qty[]" value="${qty}" min="1" class="w-full text-center text-xs rounded bg-white border border-slate-200 p-2 outline-none">
            </td>
            <td class="p-1.5 text-center">
                <button type="button" onclick="this.closest('tr').remove(); checkFormValidity();" class="w-6 h-6 bg-red-50 text-red-600 rounded text-xs font-bold">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
        checkFormValidity();
    }

    function checkFormValidity() {
        let isValid = false;
        let status = document.querySelector('input[name="status_survey"]:checked');
        
        if (status && status.value === 'kendala') {
            isValid = document.querySelector('textarea[name="kendala_note"]').value.trim().length > 0;
        } else if (status && status.value === 'eksekusi') {
            let mode = document.querySelector('input[name="mode"]:checked');
            if (mode) {
                let m = mode.value;
                let dist = document.querySelector('input[name="distribusi"]').value.trim();
                let core = document.querySelector('input[name="core_ex"]').value.trim();
                
                let matsValid = true;
                document.querySelectorAll('select[name="materials[]"]').forEach(el => { if(!el.value) matsValid = false; });
                document.querySelectorAll('input[name="qty[]"]').forEach(el => { if(!el.value) matsValid = false; });

                if (m === 'A' && document.querySelector('select[name="sub_mode_a"]').value && document.querySelector('input[name="odp_name"]').value.trim() && dist && core && document.querySelector('input[name="power_out"]').value.trim() && matsValid) isValid = true;
                if (m === 'B' && document.querySelector('select[name="possible_add"]').value && dist && core && document.querySelector('input[name="power_in_feeder"]').value.trim() && matsValid) isValid = true;
                if (m === 'C' && document.querySelector('select[name="opsi_simple"]').value && document.querySelector('input[name="tipe_kabel"]').value.trim() && dist && core && document.querySelector('input[name="power_in_feeder"]').value.trim() && matsValid) isValid = true;
            }
        }

        let btn = document.getElementById('btnSubmit');
        // Jika mode edit, tombol update selalu biru. Jika belum isi, validasi berjalan.
        if (isValid || isEditMode) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            if(!isEditMode) btn.classList.add('bg-blue-600', 'text-white'); // Mode baru
        } else {
            btn.disabled = true;
            btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            if(!isEditMode) btn.classList.remove('bg-blue-600', 'text-white');
        }
    }
</script>
@endsection