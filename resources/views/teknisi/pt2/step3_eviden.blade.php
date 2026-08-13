@extends('layouts.teknisi')

@section('content')
@php
    $allEvidences = array_merge($requiredEvidences ?? [], $optionalEvidences ?? []);
@endphp

<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    @include('teknisi.partials.stepper', ['title' => 'Step 3 - Eviden Redaman'])

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

        <div class="mb-5 bg-blue-50 border border-blue-100 p-4 rounded-2xl flex items-start gap-3">
            <span class="text-xl">💡</span>
            <p class="text-xs font-medium text-blue-800 leading-relaxed">
                Mode <strong>{{ $mode }}</strong>. Pilih <strong>{{ str_replace('_', ' ', $targetPortCount) }} Foto</strong> sekaligus. Metadata Foto akan otomatis tersimpan.
            </p>
        </div>

        {{-- FORM UTAMA DITUTUP DISINI AGAR TIDAK BENTROK DENGAN FORM REPLACE/DELETE --}}
        <form id="evidenForm" action="{{ route('teknisi.pt2.storeStep3Eviden', $lop->id_pt2_lop) }}" method="POST" enctype="multipart/form-data" class="hidden">
            @csrf
        </form>

        <div class="space-y-3">
            @foreach($allEvidences as $key => $label)
                @php
                    $uploaded = isset($existingEvidences[$key]) ? $existingEvidences[$key] : collect();
                    
                    // Pisahkan status foto
                    $validUploaded = $uploaded->whereIn('status', ['pending', 'approved']);
                    $rejectedUploaded = $uploaded->where('status', 'rejected');
                    
                    $hasValid = $validUploaded->count() > 0;
                    $hasRejected = $rejectedUploaded->count() > 0;
                    $isRequired = array_key_exists($key, $requiredEvidences);
                @endphp

                <details id="details-{{ $key }}" class="group bg-white border border-slate-200 rounded-2xl shadow-sm [&_summary::-webkit-details-marker]:hidden transition-all duration-300 {{ ($hasValid || $hasRejected) ? 'open' : '' }}">
                    
                    <summary id="summary-{{ $key }}" data-rejected="{{ $hasRejected ? 'true' : 'false' }}" class="flex items-center justify-between p-4 cursor-pointer rounded-2xl transition">
                        <div class="flex items-center gap-3">
                            <div id="icon-{{ $key }}" class="w-8 h-8 rounded-full flex items-center justify-center text-sm bg-slate-100 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera-icon lucide-camera"><path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-slate-800">
                                    {{ $label }} 
                                    @if($isRequired) <span class="text-red-500">*</span> @else <span class="text-[9px] text-slate-400 font-normal">(Opsional)</span> @endif
                                </h3>
                                <p id="count-{{ $key }}" class="text-[10px] font-medium mt-0.5 text-slate-400">Menghitung...</p>
                            </div>
                        </div>
                    </summary>

                    <div class="p-4 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl">
                        
                        {{-- Area Klik Tambah Foto Baru (Dihubungkan dengan form="evidenForm") --}}
                        <label class="relative flex flex-col items-center justify-center w-full h-20 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-white hover:bg-slate-50 transition">
                            <span class="text-xs font-bold text-slate-500">+ Pilih Multiple Foto</span>
                            <input type="file" form="evidenForm" id="input-{{ $key }}" name="evidences[{{ $key }}][]" multiple accept="image/*" class="hidden" onchange="handleFileSelect(this, '{{ $key }}')">
                        </label>

                        {{-- INDIKATOR LOADING SAAT PROSES KOMPRES --}}
                        <div id="loading-{{ $key }}" class="hidden mt-2 text-[10px] font-bold text-blue-600 animate-pulse text-center">
                            Sedang upload foto... mohon tunggu...
                        </div>

                        <div id="preview-{{ $key }}" class="grid grid-cols-4 gap-2 mt-3 empty:hidden"></div>

                        {{-- GRID FOTO YANG TERSIMPAN DI DATABASE --}}
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

                                {{-- Step 3 menggunakan grid-cols-4 agar lebih muat banyak foto port --}}
                              <div class="grid grid-cols-3 gap-x-3 gap-y-4 mb-3">
                                    @foreach($uploaded as $ev)
                                        {{-- WRAPPER ITEM --}}
                                        <div class="relative pt-2 pr-2">

                                            {{-- TOMBOL DELETE --}}
                                            @if($ev->status != 'approved')
                                                <form method="POST" action="{{ route('teknisi.pt2.deleteEvidence', $ev->id_pt2_evidence) }}" class="absolute top-0 right-0 z-50" onsubmit="return confirm('Hapus foto eviden ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Hapus foto" class="w-7 h-7 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center text-sm font-black shadow-lg border-2 border-white transition active:scale-90">×</button>
                                                </form>
                                            @endif

                                            {{-- FOTO CONTAINER --}}
                                            <div class="relative aspect-square rounded-xl overflow-hidden bg-gray-100 group flex items-center justify-center transition-all {{ $ev->status == 'rejected' ? 'border-2 border-red-500 ring-2 ring-red-200' : 'border border-gray-200' }}">

                                                {{-- ID FOTO & STATUS ICON --}}
                                                <div class="absolute top-1 left-1 bg-black/60 text-white text-[9px] font-black px-1.5 py-0.5 rounded flex items-center gap-1 z-20 backdrop-blur-sm pointer-events-none">
                                                    @if($ev->status == 'rejected')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                    @elseif($ev->status == 'approved')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                                    @endif
                                                    ID-{{ $ev->id_pt2_evidence }}
                                                </div>

                                                {{-- IMAGE --}}
                                                <img src="{{ asset('storage/' . $ev->file_path) }}" alt="Evidence {{ $ev->id_pt2_evidence }}" class="w-full h-full object-cover {{ $ev->status == 'rejected' ? 'opacity-80 grayscale-[20%]' : '' }}">

                                                {{-- REJECTED STATE --}}
                                                @if($ev->status == 'rejected')
                                                    @if(!empty($ev->review_note))
                                                        <div class="absolute bottom-0 left-0 right-0 bg-red-600/95 text-white text-[9px] p-1.5 text-center font-bold z-20 backdrop-blur-sm leading-tight border-t border-red-500 line-clamp-2 pointer-events-none" title="{{ $ev->review_note }}">
                                                            {{ $ev->review_note }}
                                                        </div>
                                                    @endif

                                                    <form method="POST" action="{{ route('teknisi.pt2.replaceEvidence', $ev->id_pt2_evidence) }}" enctype="multipart/form-data" class="absolute inset-0 z-30 flex items-center justify-center bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                        @csrf
                                                        <label class="cursor-pointer w-full h-full flex flex-col items-center justify-center text-white text-[10px] font-black">
                                                            <div class="bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-xl shadow-lg flex flex-col items-center gap-1 transition">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                                                <span>Upload Ulang</span>
                                                            </div>
                                                            <input type="file" name="file" accept="image/*" class="hidden" onchange="this.form.submit()">
                                                        </label>
                                                    </form>
                                                @endif

                                                {{-- STATUS FOOTER (APPROVED / PENDING) --}}
                                                @if($ev->status == 'approved')
                                                    <div class="absolute bottom-0 inset-x-0 bg-emerald-600 text-white text-[8px] text-center py-1 font-black z-20 pointer-events-none">APPROVED</div>
                                                @elseif($ev->status == 'pending')
                                                    <div class="absolute bottom-0 inset-x-0 bg-amber-500 text-white text-[8px] text-center py-1 font-black z-20 pointer-events-none">PENDING</div>
                                                @endif

                                            </div>
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
        <button type="submit" form="evidenForm" id="btnSubmit" disabled class="w-full h-12 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl mt-8 shadow-sm transition-all flex items-center justify-center gap-2">
            <span>Upload Baru & Lanjut Step 4</span>
        </button>

        <a href="{{ route('teknisi.pt2.step4Eviden', $lop->id_pt2_lop) }}"  class="block w-full h-11 bg-white border-2 border-slate-200 text-slate-600 text-center leading-[2.5rem] font-bold rounded-xl mt-3 active:scale-95 transition">
            Lewati (Lanjut Step 4) →
        </a>

    </div>
</div>

{{-- BOTTOM NAV --}}
@include('teknisi.partials.bottom-nav', ['active' => 'home'])

<script>
    const targetPortCount = '{{ $targetPortCount }}'; 
    const allKeys = @json(array_keys($allEvidences));
    
    let serverData = {
        @foreach($allEvidences as $key => $label)
            @php
                $validCount = isset($existingEvidences[$key]) ? $existingEvidences[$key]->whereIn('status', ['pending', 'approved'])->count() : 0;
            @endphp
            '{{ $key }}': {{ $validCount }},
        @endforeach
    };

    let uploadedData = {};
    let newFilesStore = {}; 
    
    allKeys.forEach(key => {
        uploadedData[key] = 0;
        newFilesStore[key] = [];
    });

    document.addEventListener("DOMContentLoaded", function() {
        allKeys.forEach(key => updateUI(key));
        checkFormValidity();
    });

    // function compressImage(file, maxWidth = 1280, quality = 0.75) {
    //     return new Promise((resolve) => {
    //         const reader = new FileReader();
    //         reader.onload = (e) => {
    //             const img = new Image();
    //             img.onload = () => {
    //                 const canvas = document.createElement('canvas');
    //                 let w = img.width, h = img.height;
    //                 if (w > maxWidth) { h = Math.round((h * maxWidth) / w); w = maxWidth; }
    //                 canvas.width = w; canvas.height = h;
    //                 const ctx = canvas.getContext('2d');
    //                 ctx.drawImage(img, 0, 0, w, h);
    //                 canvas.toBlob((blob) => {
    //                     let newFileName = file.name.replace(/\.[^/.]+$/, "") + '.jpg';
    //                     resolve(new File([blob], newFileName, { type: 'image/jpeg', lastModified: Date.now() }));
    //                 }, 'image/jpeg', quality);
    //             };
    //             img.src = e.target.result;
    //         };
    //         reader.readAsDataURL(file);
    //     });
    // }

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
                    <div class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[7px] text-center py-0.5 font-bold">BARU</div>
                    <button type="button" onclick="removeNewFile('${key}', ${index})" class="absolute top-1 right-1 w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center text-[9px] font-black shadow-md hover:bg-red-700">✕</button>
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
        let isPort = (key === 'redaman_port');
        
        let summary = document.getElementById('summary-' + key);
        let icon = document.getElementById('icon-' + key);
        let countText = document.getElementById('count-' + key);
        let hasRejected = summary.getAttribute('data-rejected') === 'true';

        let state = 'empty'; 
        let msg = '';
        
        if (isPort) {
            if (total === 0) { 
                state = 'empty'; 
                msg = hasRejected ? 'Ada foto ditolak! Wajib upload ulang.' : 'Belum ada foto dipilih'; 
            }
            else {
                if (targetPortCount == '16') {
                    if (total == 16) { state = 'complete'; msg = '✅ 16 / 16 foto siap'; }
                    else if (total > 16) { state = 'over'; msg = '❌ Kelebihan ' + (total-16) + ' foto!'; }
                    else { state = 'partial'; msg = hasRejected ? '🚨 ' + total + ' / 16 (Ada yg ditolak)' : '⚠️ ' + total + ' / 16 foto (Kurang '+(16-total)+')'; }
                } 
                else if (targetPortCount == '8') {
                    if (total == 8) { state = 'complete'; msg = '✅ 8 / 8 foto siap'; }
                    else if (total > 8) { state = 'over'; msg = '❌ Kelebihan ' + (total-8) + ' foto!'; }
                    else { state = 'partial'; msg = hasRejected ? '🚨 ' + total + ' / 8 (Ada yg ditolak)' : '⚠️ ' + total + ' / 8 foto (Kurang '+(8-total)+')'; }
                }
                else { // 8_atau_16
                    if (total == 8 || total == 16) { state = 'complete'; msg = '✅ ' + total + ' foto siap'; }
                    else if (total > 16) { state = 'over'; msg = '❌ Kelebihan foto!'; }
                    else { state = 'partial'; msg = hasRejected ? '🚨 ' + total + ' foto (Ada yg ditolak)' : '⚠️ ' + total + ' foto (Harus pas 8 atau 16)'; }
                }
            }
        } else {
            if (total === 0) { 
                state = 'empty'; 
                msg = hasRejected ? 'Foto sebelumnya ditolak.' : 'Belum ada foto (Opsional)'; 
            }
            else { state = 'complete'; msg = '✅ ' + total + ' foto siap'; }
        }

        // --- STYLING LOGIC ---
        if ((state === 'empty' || state === 'partial') && hasRejected) {
             summary.className = 'flex items-center justify-between p-4 cursor-pointer rounded-2xl transition bg-red-50/50 border border-red-200';
             icon.className = 'w-8 h-8 rounded-full flex items-center justify-center text-sm bg-red-100 text-red-600';
             icon.innerText = '🚨';
             countText.className = 'text-[10px] font-bold mt-0.5 text-red-600';
        } else {
             summary.className = 'flex items-center justify-between p-4 cursor-pointer rounded-2xl transition ' + 
                 (state === 'complete' ? 'bg-green-50/50' : (state === 'partial' ? 'bg-amber-50/50' : (state === 'over' ? 'bg-red-50/50' : '')));
             
             icon.className = 'w-8 h-8 rounded-full flex items-center justify-center text-sm ' + 
                 (state === 'complete' ? 'bg-green-100 text-green-600' : (state === 'partial' ? 'bg-amber-100 text-amber-600' : (state === 'over' ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400')));
             icon.innerHTML = (state === 'complete' ? '✅' : (state === 'partial' ? '⚠️' : (state === 'over' ? '❌' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera-icon lucide-camera"><path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/></svg>')));
             
             countText.className = 'text-[10px] font-medium mt-0.5 ' + 
                 (state === 'complete' ? 'text-green-600' : (state === 'partial' ? 'text-amber-600' : (state === 'over' ? 'text-red-600' : 'text-slate-400')));
        }
        
        countText.innerText = msg;
    }

    function checkFormValidity() {
        let isPortValid = false;
        let portTotal = uploadedData['redaman_port'] + serverData['redaman_port'];
        
        if (targetPortCount == '16' && portTotal == 16) isPortValid = true;
        else if (targetPortCount == '8' && portTotal == 8) isPortValid = true;
        else if (targetPortCount == '8_atau_16' && (portTotal == 8 || portTotal == 16)) isPortValid = true;

        let hasNewUploads = allKeys.some(key => uploadedData[key] > 0);
        let btn = document.getElementById('btnSubmit');

        if (isPortValid && hasNewUploads) {
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