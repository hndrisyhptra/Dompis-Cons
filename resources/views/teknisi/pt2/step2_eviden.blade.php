@extends('layouts.teknisi')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    {{-- Header STEPPER --}}
    @include('teknisi.partials.stepper', ['title' => 'Step 2 - Progress Instalasi'])

    {{-- PROJECT INFO CARD - Badge Style --}}
    <div class="px-4 mt-3">
        <div class="bg-white rounded-xl border border-gray-200/80 p-3 shadow-xs">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">Nama LOP</p>
            <p class="text-xs font-bold text-gray-900 leading-snug break-words mb-2.5">
                {{ $lop->lop_name }}
            </p>
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-[11px] font-semibold text-gray-700">
                    <span class="text-gray-400 mr-1 font-normal">IHLD:</span>
                    <span class="font-mono">{{ $lop->id_ihld ?? '-' }}</span>
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-[11px] font-semibold text-gray-700">
                    <span class="text-gray-400 mr-1 font-normal">STO:</span>
                    <span class="font-mono">{{ $lop->sto ?? '-' }}</span>
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-[11px] font-semibold text-gray-700">
                    <span class="text-gray-400 mr-1 font-normal">Branch:</span>
                    <span>{{ $lop->branch ?? '-' }}</span>
                </span>
            </div>
        </div>
    </div>

    <div class="px-5 mt-6">
        @if(session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-xs font-bold">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs font-bold">{{ session('error') }}</div>
        @endif

        {{-- FORM UTAMA DITUTUP DISINI AGAR TIDAK BENTROK DENGAN FORM REPLACE/DELETE (TIDAK NESTED) --}}
        <form id="evidenForm" action="{{ route('teknisi.pt2.storeStep2Eviden', $lop->id_pt2_lop) }}" method="POST" enctype="multipart/form-data" class="hidden">
            @csrf
        </form>

        <div class="space-y-3">
            @foreach($requiredEvidences as $key => $label)
                @php
                    $uploaded = isset($existingEvidences[$key]) ? $existingEvidences[$key] : collect();
                    
                    // Pisahkan foto yang sah (pending/approved) dan yang ditolak (rejected)
                    $validUploaded = $uploaded->whereIn('status', ['pending', 'approved']);
                    $rejectedUploaded = $uploaded->where('status', 'rejected');
                    
                    $hasValid = $validUploaded->count() > 0;
                    $hasRejected = $rejectedUploaded->count() > 0;
                @endphp

                <details class="group bg-white border border-slate-200 rounded-2xl shadow-sm [&_summary::-webkit-details-marker]:hidden transition-all duration-300 {{ ($hasValid || $hasRejected) ? 'open' : '' }}">
                    
                    {{-- SUMMARY HEADER (Akan berubah warna sesuai status) --}}
                    <summary id="summary-{{ $key }}" data-rejected="{{ $hasRejected && !$hasValid ? 'true' : 'false' }}" 
                             class="flex items-center justify-between p-4 cursor-pointer rounded-2xl transition 
                             {{ $hasValid ? 'bg-green-50/50' : ($hasRejected ? 'bg-red-50/50 border border-red-200' : '') }}">
                        
                        <div class="flex items-center gap-3">
                            <div id="icon-{{ $key }}" class="w-8 h-8 rounded-full flex items-center justify-center text-sm 
                                {{ $hasValid ? 'bg-green-100 text-green-600' : ($hasRejected ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400') }}">
                                
                                @if($hasValid)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 12 2 2 4-4"/></svg>
                                @elseif($hasRejected)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                @endif
                            </div>
                            
                            <div>
                                <h3 class="font-bold text-sm text-slate-800">{{ $label }} <span class="text-red-500">*</span></h3>
                                <p id="count-{{ $key }}" class="text-[10px] font-medium mt-0.5 
                                    {{ $hasValid ? 'text-green-600' : ($hasRejected ? 'text-red-600 font-bold' : 'text-slate-400') }}">
                                    @if($hasValid)
                                        {{ $validUploaded->count() }} foto siap
                                    @elseif($hasRejected)
                                        Ada foto ditolak! Wajib upload ulang.
                                    @else
                                        Belum ada foto
                                    @endif
                                </p>
                            </div>
                        </div>
                    </summary>

                    <div class="p-4 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl">
                        
                        {{-- Area Klik Tambah Foto Baru (Pakai form="evidenForm") --}}
                        <label class="relative flex flex-col items-center justify-center w-full h-20 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-white hover:bg-slate-50 transition">
                            <span class="text-xs font-bold text-slate-500">+ Tambah Foto Baru</span>
                            <input type="file" form="evidenForm" id="input-{{ $key }}" name="evidences[{{ $key }}][]" multiple accept="image/*" class="hidden" onchange="handleFileSelect(this, '{{ $key }}')">
                        </label>

                        {{-- Grid Preview Foto Baru --}}
                        <div id="preview-{{ $key }}" class="grid grid-cols-3 gap-2 mt-3 empty:hidden"></div>

                        {{-- Grid Review Foto Lama (Style Persis Waspang) --}}
                        @if($uploaded->count() > 0)
                            <div class="mt-4 pt-3 border-t border-slate-200">
                                <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Tersimpan di Database</p>
                                
                                @if($hasRejected)
                                    <div class="mb-3 rounded-xl border border-red-100 bg-red-50/80 p-3 text-xs text-red-700 leading-relaxed flex items-start gap-2 shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 mt-0.5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        <div>
                                            <p class="font-bold mb-0.5">Terdapat Eviden Ditolak</p>
                                            <p>Ketuk pada foto untuk mengunggah ulang (replace).</p>
                                        </div>
                                    </div>
                                @endif

                                <div class="grid grid-cols-3 gap-2 mb-3">
                                    @foreach($uploaded as $ev)
                                        <div class="relative aspect-square rounded-xl overflow-hidden bg-gray-100 group flex items-center justify-center transition-all 
                                            {{ $ev->status == 'rejected' ? 'border-2 border-red-500 ring-2 ring-red-200' : 'border border-gray-200' }}">

                                            {{-- INDIKATOR ID FOTO --}}
                                            <div class="absolute top-1 left-1 bg-black/60 text-white text-[9px] font-black px-1.5 py-0.5 rounded flex items-center gap-1 z-20 backdrop-blur-sm pointer-events-none">
                                                @if($ev->status == 'rejected')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                @elseif($ev->status == 'approved')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                                @endif
                                                ID-{{ $ev->id_pt2_evidence }}
                                            </div>

                                            {{-- FOTO NORMAL --}}
                                            <img src="{{ asset('storage/' . $ev->file_path) }}" 
                                                 class="w-full h-full object-cover {{ $ev->status == 'rejected' ? 'opacity-80 grayscale-[20%]' : '' }}">

                                            {{-- JIKA STATUS REJECTED, TAMPILKAN NOTE & FORM REPLACE --}}
                                            @if($ev->status == 'rejected')
                                                @if(!empty($ev->review_note))
                                                    <div class="absolute bottom-0 left-0 right-0 bg-red-600/95 text-white text-[9px] p-1.5 text-center font-bold z-20 backdrop-blur-sm leading-tight border-t border-red-500 line-clamp-2 pointer-events-none" title="{{ $ev->review_note }}">
                                                        {{ $ev->review_note }}
                                                    </div>
                                                @endif

                                                {{-- OVERLAY GANTI FOTO --}}
                                                <form method="POST" action="{{ route('teknisi.pt2.replaceEvidence', $ev->id_pt2_evidence) }}" enctype="multipart/form-data" 
                                                      class="absolute inset-0 z-30 flex items-center justify-center bg-black/60 opacity-0 hover:opacity-100 transition-opacity duration-200">
                                                    @csrf
                                                    <label class="cursor-pointer w-full h-full flex flex-col items-center justify-center text-white text-[10px] font-black group">
                                                        <div class="bg-blue-600 px-3 py-2 rounded-xl shadow-lg flex flex-col items-center gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                                            Upload Ulang
                                                        </div>
                                                        <input type="file" name="file" class="hidden" accept="image/*"
                                                               onchange="
                                                                   if(this.files.length > 0) {
                                                                       this.previousElementSibling.style.display = 'none';
                                                                       let span = document.createElement('span');
                                                                       span.className = 'animate-pulse text-white mt-2';
                                                                       span.innerText = 'Mengupload...';
                                                                       this.parentElement.appendChild(span);
                                                                       this.form.submit();
                                                                   }
                                                               ">
                                                    </label>
                                                </form>
                                            @endif

                                            {{-- BADGE STATUS PENDING ATAU APPROVED (Di Bawah) --}}
                                            @if($ev->status == 'approved')
                                                <div class="absolute bottom-0 inset-x-0 bg-emerald-600 text-white text-[8px] text-center py-1 font-black z-20 pointer-events-none">APPROVED</div>
                                            @elseif($ev->status == 'pending')
                                                <div class="absolute bottom-0 inset-x-0 bg-amber-500 text-white text-[8px] text-center py-1 font-black z-20 pointer-events-none">PENDING</div>
                                            @endif

                                            {{-- TOMBOL DELETE --}}
                                            @if($ev->status != 'approved')
                                                <form method="POST" action="{{ route('teknisi.pt2.deleteEvidence', $ev->id_pt2_evidence) }}" class="absolute top-1 right-1 z-20">
                                                    @csrf @method('DELETE')
                                                    <button type="button" onclick="this.closest('form').submit()" class="w-6 h-6 rounded-full bg-black/70 hover:bg-red-600 text-white text-xs flex items-center justify-center font-bold backdrop-blur-sm transition">
                                                        ×
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    </div>
                </details>
            @endforeach
        </div>

        {{-- TOMBOL SUBMIT FORM UTAMA (Diikat pakai form="evidenForm") --}}
        <button type="submit" form="evidenForm" id="btnSubmit" disabled 
            class="w-full h-12 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl mt-8 shadow-sm transition-all flex items-center justify-center gap-2">
            <span>Upload Baru & Lanjut Step 3</span>
        </button>

        {{-- TOMBOL SKIP / LEWATI --}}
        <a href="{{ route('teknisi.pt2.step3Eviden', $lop->id_pt2_lop) }}" 
           class="block w-full h-11 bg-white border-2 border-slate-200 text-slate-600 text-center leading-[2.5rem] font-bold rounded-xl mt-3 active:scale-95 transition">
            Lewati (Lanjut Step 3) →
        </a>

    </div>
</div>

{{-- BOTTOM NAV --}}
@include('teknisi.partials.bottom-nav', ['active' => 'home'])

<script>
    const requiredKeys = @json(array_keys($requiredEvidences));
    
    let serverData = {
        @foreach($requiredEvidences as $key => $label)
            @php $validCount = isset($existingEvidences[$key]) ? $existingEvidences[$key]->whereIn('status', ['pending', 'approved'])->count() : 0; @endphp
            '{{ $key }}': {{ $validCount }},
        @endforeach
    };

    let uploadedData = {};
    let newFilesStore = {}; 
    
    const checkIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 12 2 2 4-4"/></svg>`;
    const warnIcon  = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>`;
    const camIcon   = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>`;

    requiredKeys.forEach(key => {
        uploadedData[key] = 0;
        newFilesStore[key] = [];
    });

    document.addEventListener("DOMContentLoaded", function() {
        checkFormValidity();
    });

    async function handleFileSelect(inputElement, key) {
        let files = Array.from(inputElement.files);
        if (files.length === 0) return;

        // Hilangkan loading kompresi jika ada
        let loadingEl = document.getElementById('loading-' + key);
        if(loadingEl) loadingEl.classList.remove('hidden');

        for (let file of files) {
            // LANGSUNG MASUKKAN FILE ASLI KE STORE TANPA COMPRESS
            newFilesStore[key].push(file); 
        }
        
        inputElement.value = '';
        if(loadingEl) loadingEl.classList.add('hidden');
        renderPreviews(key);
    }

    function removeNewFile(key, index) {
        newFilesStore[key].splice(index, 1);
        renderPreviews(key);
    }

    function renderPreviews(key) {
        const previewContainer = document.getElementById('preview-' + key);
        previewContainer.innerHTML = ''; 
        
        let dataTransfer = new DataTransfer();
        
        newFilesStore[key].forEach((file, index) => {
            dataTransfer.items.add(file);
            
            let reader = new FileReader();
            reader.onload = function(e) {
                let div = document.createElement('div');
                div.className = 'relative rounded-xl overflow-hidden border border-blue-200 border-2 aspect-square';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <div class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[8px] text-center py-1 font-bold">BARU</div>
                    <button type="button" onclick="removeNewFile('${key}', ${index})" class="absolute top-1 right-1 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs font-black shadow-md transition hover:bg-red-700">✕</button>
                `;
                previewContainer.appendChild(div);
            }
            reader.readAsDataURL(file);
        });

        document.getElementById('input-' + key).files = dataTransfer.files;
        uploadedData[key] = newFilesStore[key].length;
        
        updateUI(key);
        checkFormValidity();
    }

    function updateUI(key) {
        let total = uploadedData[key] + serverData[key];
        let sumEl = document.getElementById('summary-' + key);
        let iconEl = document.getElementById('icon-' + key);
        let countEl = document.getElementById('count-' + key);
        let hasRejected = sumEl.getAttribute('data-rejected') === 'true';

        sumEl.className = "flex items-center justify-between p-4 cursor-pointer rounded-2xl transition";
        iconEl.className = "w-8 h-8 rounded-full flex items-center justify-center text-sm";
        countEl.className = "text-[10px] mt-0.5";

        if (total > 0) {
            sumEl.classList.add('bg-green-50/50');
            iconEl.classList.add('bg-green-100', 'text-green-600');
            iconEl.innerHTML = checkIcon;
            countEl.classList.add('text-green-600', 'font-medium');
            countEl.innerText = total + ' foto siap';
        } else {
            if (hasRejected) {
                sumEl.classList.add('bg-red-50/50', 'border', 'border-red-200');
                iconEl.classList.add('bg-red-100', 'text-red-600');
                iconEl.innerHTML = warnIcon;
                countEl.classList.add('text-red-600', 'font-bold');
                countEl.innerText = 'Ada foto ditolak! Wajib upload ulang.';
            } else {
                iconEl.classList.add('bg-slate-100', 'text-slate-400');
                iconEl.innerHTML = camIcon;
                countEl.classList.add('text-slate-400', 'font-medium');
                countEl.innerText = 'Belum ada foto';
            }
        }
    }

    function checkFormValidity() {
        let isValid = requiredKeys.every(key => (uploadedData[key] + serverData[key]) > 0);
        let hasNewUploads = requiredKeys.some(key => uploadedData[key] > 0);
        let btn = document.getElementById('btnSubmit');

        if (isValid && hasNewUploads) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.add('bg-blue-600', 'text-white');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.remove('bg-blue-600', 'text-white');
        }
    }
</script>
@endsection