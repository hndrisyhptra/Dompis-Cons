@extends('layouts.teknisi')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    @include('teknisi.partials.stepper', ['title' => 'Step 4 - BA Dismantle'])

    <div class="px-5 mt-6">
        @if(session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-xs font-bold">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs font-bold">{{ session('error') }}</div>
        @endif

        <div class="mb-5 bg-amber-50 border border-amber-100 p-4 rounded-2xl flex items-start gap-3">
            <span class="text-xl">🛠️</span>
            <p class="text-xs font-medium text-amber-800 leading-relaxed">
                Pilih material yang dibongkar (Dismantle). Jika ada, lengkapi QTY dan lampirkan foto evidennya.
            </p>
        </div>

        {{-- FORM UTAMA DITUTUP DISINI AGAR TIDAK NESTED --}}
        <form id="step4Form" action="{{ route('teknisi.pt2.storeStep4Eviden', $lop->id_pt2_lop) }}" method="POST" enctype="multipart/form-data" class="hidden">
            @csrf
        </form>

        <div class="space-y-4">
            
            {{-- 1. BAGIAN DISMANTLE ODP --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="font-black text-sm text-slate-800">1. Dismantle ODP</h3>
                </div>
                <div class="p-4 space-y-3">
                    @php
                        $odpOptions = ['none' => 'Tidak Ada', 'ODP Pole' => 'ODP Pole', 'ODP Wall' => 'ODP Wall', 'ODP Pedestal' => 'ODP Pedestal', 'ODP Closure' => 'ODP Closure'];
                        $currentOdp = $odpData ? $odpData->item_name : 'none';
                    @endphp
                    
                    @foreach($odpOptions as $val => $label)
                        <label class="flex items-center gap-3 cursor-pointer">
                            {{-- Input dikaikan ke form="step4Form" --}}
                            <input type="radio" form="step4Form" name="odp_item" value="{{ $val }}" class="w-4 h-4 text-blue-600 focus:ring-blue-500" 
                                {{ $currentOdp === $val ? 'checked' : '' }} onchange="toggleOdp()">
                            <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                        </label>
                    @endforeach

                    {{-- WADAH QTY & FOTO ODP (Muncul jika selain 'none') --}}
                    <div id="odp_box" class="hidden mt-4 p-4 bg-blue-50/50 border border-blue-100 rounded-xl">
                        <label class="block text-xs font-black text-slate-600 mb-1">QTY ODP <span class="text-red-500">*</span></label>
                        {{-- Input dikaikan ke form="step4Form" --}}
                        <input type="number" form="step4Form" name="odp_qty" value="{{ $odpData->qty ?? '1' }}" min="1" class="w-24 h-10 px-3 border border-slate-200 rounded-lg text-sm mb-4">
                        
                        @php
                            $key = 'odp';
                            $uploaded = isset($existingEvidences[$key]) ? $existingEvidences[$key] : collect();
                            $validUploaded = $uploaded->whereIn('status', ['pending', 'approved']);
                            $rejectedUploaded = $uploaded->where('status', 'rejected');
                        @endphp
                        
                        <div class="w-full">
                            <label class="block text-xs font-black text-slate-600 mb-2">Foto ODP Dibongkar <span class="text-red-500">*</span></label>
                            
                            {{-- Area Upload --}}
                            <label id="upload-box-{{ $key }}" class="relative flex flex-col items-center justify-center w-full h-16 border-2 border-dashed rounded-xl cursor-pointer transition 
                                {{ $rejectedUploaded->count() > 0 && $validUploaded->count() == 0 ? 'bg-red-50 border-red-300' : 'bg-white border-slate-300 hover:bg-slate-50' }}">
                                
                                <span id="label-btn-{{ $key }}" class="text-xs font-bold 
                                    {{ $rejectedUploaded->count() > 0 && $validUploaded->count() == 0 ? 'text-red-600' : 'text-slate-500' }}">
                                    {!! $rejectedUploaded->count() > 0 && $validUploaded->count() == 0 ? '⚠️ Ditolak! Upload Ulang' : '+ Tambah Foto' !!}
                                </span>
                                
                                {{-- Input dikaikan ke form="step4Form" --}}
                                <input type="file" form="step4Form" id="input-{{ $key }}" name="evidences[{{ $key }}][]" multiple accept="image/*" class="hidden" onchange="handleFileSelect(this, '{{ $key }}')">
                                <div id="loading-{{ $key }}" class="hidden absolute inset-0 bg-white/80 rounded-xl flex items-center justify-center text-[10px] font-bold text-blue-600">Compressing...</div>
                            </label>

                            <div id="preview-{{ $key }}" class="grid grid-cols-3 gap-2 mt-3 empty:hidden"></div>

                            {{-- Render Foto ODP dari Database --}}
                            @if($uploaded->count() > 0)
                                <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-slate-200">
                                    @foreach($uploaded as $ev)
                                        <div class="relative rounded-xl overflow-hidden border {{ $ev->status == 'rejected' ? 'border-red-500 border-2 shadow-red-200' : 'border-slate-200' }} aspect-square group">
                                            
                                            <div class="absolute top-1 left-1 bg-black/60 text-white text-[9px] font-black px-1.5 py-0.5 rounded flex items-center gap-1 z-20 backdrop-blur-sm pointer-events-none">
                                                ID-{{ $ev->id_pt2_evidence }}
                                            </div>

                                            <img src="{{ asset('storage/' . $ev->file_path) }}" class="w-full h-full object-cover {{ $ev->status == 'rejected' ? 'opacity-80 grayscale-[20%]' : '' }}">
                                            
                                            {{-- KHUSUS REJECTED ODP --}}
                                            @if($ev->status == 'rejected')
                                                @if(!empty($ev->review_note))
                                                    <div class="absolute bottom-0 left-0 right-0 bg-red-600/95 text-white text-[8px] p-1 text-center font-bold z-20 backdrop-blur-sm leading-tight border-t border-red-500 line-clamp-2 pointer-events-none" title="{{ $ev->review_note }}">
                                                        {{ $ev->review_note }}
                                                    </div>
                                                @endif
                                                <form method="POST" action="{{ route('teknisi.pt2.replaceEvidence', $ev->id_pt2_evidence) }}" enctype="multipart/form-data" 
                                                      class="absolute inset-0 z-30 flex items-center justify-center bg-black/60 opacity-0 hover:opacity-100 transition-opacity duration-200">
                                                    @csrf
                                                    <label class="cursor-pointer w-full h-full flex flex-col items-center justify-center text-white text-[9px] font-black group-hover:opacity-100">
                                                        <div class="bg-blue-600 px-2 py-1.5 rounded-xl shadow-lg flex flex-col items-center gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                                            Replace
                                                        </div>
                                                        <input type="file" name="file" class="hidden" accept="image/*" onchange="
                                                            if(this.files.length > 0) {
                                                                this.previousElementSibling.style.display = 'none';
                                                                let span = document.createElement('span');
                                                                span.className = 'animate-pulse text-white mt-1 text-center leading-tight';
                                                                span.innerText = 'Uploading...';
                                                                this.parentElement.appendChild(span);
                                                                this.form.submit();
                                                            }
                                                        ">
                                                    </label>
                                                </form>
                                            @endif

                                            {{-- BADGE STATUS PENDING ATAU APPROVED --}}
                                            @if($ev->status == 'approved')
                                                <div class="absolute bottom-0 inset-x-0 bg-emerald-600 text-white text-[7px] text-center py-0.5 font-black z-20 pointer-events-none">APPROVED</div>
                                            @elseif($ev->status == 'pending')
                                                <div class="absolute bottom-0 inset-x-0 bg-amber-500 text-white text-[7px] text-center py-0.5 font-black z-20 pointer-events-none">PENDING</div>
                                            @endif

                                            @if($ev->status != 'approved')
                                                <form method="POST" action="{{ route('teknisi.pt2.deleteEvidence', $ev->id_pt2_evidence) }}" class="absolute top-1 right-1 z-20">
                                                    @csrf @method('DELETE')
                                                    <button type="button" onclick="this.closest('form').submit();" class="w-5 h-5 bg-black/70 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-[9px] font-black shadow-md transition">✕</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. BAGIAN DISMANTLE SPLITTER --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="font-black text-sm text-slate-800">2. Dismantle Splitter (Bisa Multiple)</h3>
                </div>
                <div class="p-4 space-y-4">
                    @php
                        $splitterOptions = ['1_2' => 'Splitter 1:2', '1_4' => 'Splitter 1:4', '1_8' => 'Splitter 1:8', '1_16' => 'Splitter 1:16'];
                    @endphp

                    @foreach($splitterOptions as $sp => $label)
                        @php
                            $spData = $dismantles->where('item_name', 'Splitter ' . str_replace('_', ':', $sp))->first();
                            $isChecked = $spData ? true : false;
                            
                            $key = 'splitter_'.$sp;
                            $uploaded = isset($existingEvidences[$key]) ? $existingEvidences[$key] : collect();
                            $validUploaded = $uploaded->whereIn('status', ['pending', 'approved']);
                            $rejectedUploaded = $uploaded->where('status', 'rejected');
                        @endphp
                        
                        <div class="border border-slate-100 rounded-xl p-3">
                            <label class="flex items-center gap-3 cursor-pointer mb-2">
                                {{-- Input dikaikan ke form="step4Form" --}}
                                <input type="checkbox" form="step4Form" name="splitters[{{ $sp }}]" id="cb_{{ $sp }}" class="cb-splitter w-4 h-4 rounded text-blue-600 focus:ring-blue-500" 
                                    {{ $isChecked ? 'checked' : '' }} onchange="toggleSplitter('{{ $sp }}')">
                                <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                            </label>

                            {{-- WADAH QTY & FOTO SPLITTER --}}
                            <div id="box_{{ $sp }}" class="hidden pl-7 pt-2">
                                <label class="block text-[10px] font-black text-slate-500 mb-1">QTY <span class="text-red-500">*</span></label>
                                {{-- Input dikaikan ke form="step4Form" --}}
                                <input type="number" form="step4Form" name="qty_splitter_{{ $sp }}" id="qty_{{ $sp }}" value="{{ $spData->qty ?? '1' }}" min="1" class="w-20 h-9 px-2 border border-slate-200 rounded-lg text-sm mb-3">
                                
                                <div class="w-full">
                                    <label class="block text-[10px] font-black text-slate-600 mb-1">Foto {{ $label }} <span class="text-red-500">*</span></label>
                                    
                                    <label id="upload-box-{{ $key }}" class="relative flex flex-col items-center justify-center w-full h-12 border-2 border-dashed rounded-xl cursor-pointer transition
                                        {{ $rejectedUploaded->count() > 0 && $validUploaded->count() == 0 ? 'bg-red-50 border-red-300' : 'bg-white border-slate-300 hover:bg-slate-50' }}">
                                        
                                        <span id="label-btn-{{ $key }}" class="text-[10px] font-bold 
                                            {{ $rejectedUploaded->count() > 0 && $validUploaded->count() == 0 ? 'text-red-600' : 'text-slate-500' }}">
                                            {!! $rejectedUploaded->count() > 0 && $validUploaded->count() == 0 ? '⚠️ Ditolak! Upload Ulang' : '+ Tambah Foto' !!}
                                        </span>
                                        
                                        {{-- Input dikaikan ke form="step4Form" --}}
                                        <input type="file" form="step4Form" id="input-{{ $key }}" name="evidences[{{ $key }}][]" multiple accept="image/*" class="hidden" onchange="handleFileSelect(this, '{{ $key }}')">
                                        <div id="loading-{{ $key }}" class="hidden absolute inset-0 bg-white/80 rounded-xl flex items-center justify-center text-[10px] font-bold text-blue-600">Wait...</div>
                                    </label>

                                    <div id="preview-{{ $key }}" class="grid grid-cols-3 gap-2 mt-2 empty:hidden"></div>

                                    {{-- Render Foto Splitter dari Database --}}
                                    @if($uploaded->count() > 0)
                                        <div class="grid grid-cols-3 gap-2 mt-2 pt-2 border-t border-slate-100">
                                            @foreach($uploaded as $ev)
                                                <div class="relative rounded-lg overflow-hidden border {{ $ev->status == 'rejected' ? 'border-red-500 border-2 shadow-red-200' : 'border-slate-200' }} aspect-square group">
                                                    
                                                    <div class="absolute top-1 left-1 bg-black/60 text-white text-[8px] font-black px-1 py-0.5 rounded z-20 backdrop-blur-sm pointer-events-none">
                                                        ID-{{ $ev->id_pt2_evidence }}
                                                    </div>

                                                    <img src="{{ asset('storage/' . $ev->file_path) }}" class="w-full h-full object-cover {{ $ev->status == 'rejected' ? 'opacity-80 grayscale-[20%]' : '' }}">
                                                    
                                                    {{-- KHUSUS REJECTED SPLITTER --}}
                                                    @if($ev->status == 'rejected')
                                                        @if(!empty($ev->review_note))
                                                            <div class="absolute bottom-0 left-0 right-0 bg-red-600/95 text-white text-[8px] p-1 text-center font-bold z-20 backdrop-blur-sm leading-tight border-t border-red-500 line-clamp-2 pointer-events-none" title="{{ $ev->review_note }}">
                                                                {{ $ev->review_note }}
                                                            </div>
                                                        @endif
                                                        <form method="POST" action="{{ route('teknisi.pt2.replaceEvidence', $ev->id_pt2_evidence) }}" enctype="multipart/form-data" 
                                                              class="absolute inset-0 z-30 flex items-center justify-center bg-black/60 opacity-0 hover:opacity-100 transition-opacity duration-200">
                                                            @csrf
                                                            <label class="cursor-pointer w-full h-full flex flex-col items-center justify-center text-white text-[8px] font-black group-hover:opacity-100">
                                                                 <div class="bg-blue-600 px-2 py-1.5 rounded-xl shadow-lg flex flex-col items-center gap-1">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                                                    Replace
                                                                </div>
                                                                <input type="file" name="file" class="hidden" accept="image/*" onchange="
                                                                    if(this.files.length > 0) {
                                                                        this.previousElementSibling.style.display = 'none';
                                                                        let span = document.createElement('span');
                                                                        span.className = 'animate-pulse text-white mt-1 text-center';
                                                                        span.innerText = 'Up...';
                                                                        this.parentElement.appendChild(span);
                                                                        this.form.submit();
                                                                    }
                                                                ">
                                                            </label>
                                                        </form>
                                                    @endif

                                                   {{-- BADGE STATUS PENDING ATAU APPROVED --}}
                                                    @if($ev->status == 'approved')
                                                        <div class="absolute bottom-0 inset-x-0 bg-emerald-600 text-white text-[8px] text-center py-1 font-black z-20 pointer-events-none">APPROVED</div>
                                                    @elseif($ev->status == 'pending')
                                                        <div class="absolute bottom-0 inset-x-0 bg-amber-500 text-white text-[8px] text-center py-1 font-black z-20 pointer-events-none">PENDING</div>
                                                    @endif
                                                    
                                                    @if($ev->status != 'approved')
                                                        <form method="POST" action="{{ route('teknisi.pt2.deleteEvidence', $ev->id_pt2_evidence) }}" class="absolute top-1 right-1 z-20">
                                                            @csrf @method('DELETE')
                                                            <button type="button" onclick="this.closest('form').submit();" class="w-4 h-4 bg-black/70 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-[8px] font-black shadow-md transition">✕</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- TOMBOL SUBMIT (Diikat pakai form="step4Form") --}}
        <button type="submit" form="step4Form" id="btnSubmit" class="w-full h-12 bg-blue-600 text-white font-black rounded-xl mt-8 shadow-md transition-all flex items-center justify-center gap-2">
            <span id="btnText">Simpan & Lanjut Step 5</span>
        </button>

    </div>
</div>

{{-- BOTTOM NAV --}}
@include('teknisi.partials.bottom-nav', ['active' => 'home'])

{{-- JAVASCRIPT LOGIC --}}
<script>
    @php $allKeys = ['odp', 'splitter_1_2', 'splitter_1_4', 'splitter_1_8', 'splitter_1_16']; @endphp
    const allKeys = @json($allKeys);
    
    let serverData = {
        @foreach($allKeys as $key)
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
        toggleOdp();
        ['1_2', '1_4', '1_8', '1_16'].forEach(sp => toggleSplitter(sp));
        // Ubah selector menjadi selector form agar semua input terdeteksi
        document.body.addEventListener('input', function(e) {
            if (e.target.form && e.target.form.id === 'step4Form') {
                checkFormValidity();
            }
        });
    });

    function toggleOdp() {
        let selectedOdp = document.querySelector('input[name="odp_item"]:checked');
        let val = selectedOdp ? selectedOdp.value : 'none';
        let box = document.getElementById('odp_box');
        if (val !== 'none') { box.classList.remove('hidden'); } 
        else { box.classList.add('hidden'); }
        checkFormValidity();
    }

    function toggleSplitter(sp) {
        let isChecked = document.getElementById('cb_' + sp).checked;
        let box = document.getElementById('box_' + sp);
        if (isChecked) { box.classList.remove('hidden'); } 
        else { box.classList.add('hidden'); }
        checkFormValidity();
    }

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

    // FUNGSI INI SUDAH TIDAK PAKAI 'async' KARENA TIDAK ADA PROSES KOMPRESI
    function handleFileSelect(inputElement, key) {
        let files = Array.from(inputElement.files);
        if (files.length === 0) return;
        
        let loadingEl = document.getElementById('loading-' + key);
        if (loadingEl) loadingEl.classList.remove('hidden');
        
        for (let file of files) {
            // Pastikan yang dimasukkan hanya file gambar
            if (file.type.startsWith('image/')) {
                newFilesStore[key].push(file); // Masukkan foto ASLI (berserta metadata/exif)
            }
        }
        
        inputElement.value = '';
        if (loadingEl) loadingEl.classList.add('hidden');
        renderPreviews(key);
    }

    // FUNGSI RENDER DIUBAH MENGGUNAKAN URL.createObjectURL AGAR SUPER CEPAT
    function renderPreviews(key) {
        const previewContainer = document.getElementById('preview-' + key);
        previewContainer.innerHTML = ''; 
        let dataTransfer = new DataTransfer();
        
        newFilesStore[key].forEach((file, index) => {
            dataTransfer.items.add(file);
            
            // Generate link lokal instan untuk preview (Anti Ngelag)
            let imgUrl = URL.createObjectURL(file);
            
            let div = document.createElement('div');
            div.className = 'relative rounded-xl overflow-hidden border border-blue-200 border-2 aspect-square';
            div.innerHTML = `
                <img src="${imgUrl}" class="w-full h-full object-cover">
                <div class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[8px] text-center py-0.5 font-bold">BARU</div>
                <button type="button" onclick="removeNewFile('${key}', ${index})" class="absolute top-1 right-1 w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center text-[9px] font-black shadow-md">✕</button>
            `;
            previewContainer.appendChild(div);
        });

        document.getElementById('input-' + key).files = dataTransfer.files;
        uploadedData[key] = newFilesStore[key].length;
        
        // Update Teks Label & Style Kotak Upload
        let labelBtn = document.getElementById('label-btn-' + key);
        let uploadBox = document.getElementById('upload-box-' + key);
        let total = uploadedData[key] + serverData[key];
        
        if (total > 0) {
            uploadBox.className = "relative flex flex-col items-center justify-center w-full h-12 border-2 border-dashed rounded-xl cursor-pointer transition bg-green-50 border-green-300";
            labelBtn.className = "text-xs font-bold text-green-600";
            labelBtn.innerText = "✅ " + total + " Foto Siap";
        } else {
            uploadBox.className = "relative flex flex-col items-center justify-center w-full h-12 border-2 border-dashed rounded-xl cursor-pointer transition bg-white border-slate-300 hover:bg-slate-50";
            labelBtn.className = "text-xs font-bold text-slate-500";
            labelBtn.innerText = "+ Tambah Foto";
        }

        checkFormValidity();
    }

    function removeNewFile(key, index) {
        newFilesStore[key].splice(index, 1);
        renderPreviews(key);
    }

    function checkFormValidity() {
        let isValid = true;
        let selectedOdp = document.querySelector('input[name="odp_item"]:checked');
        let odpVal = selectedOdp ? selectedOdp.value : 'none';
        let isAnyDismantle = false;

        if (odpVal !== 'none') {
            isAnyDismantle = true;
            let qty = document.querySelector('input[name="odp_qty"]').value;
            let totalFoto = uploadedData['odp'] + serverData['odp']; 
            if (!qty || qty < 1 || totalFoto === 0) isValid = false;
        }

        ['1_2', '1_4', '1_8', '1_16'].forEach(sp => {
            if (document.getElementById('cb_' + sp).checked) {
                isAnyDismantle = true;
                let qty = document.getElementById('qty_' + sp).value;
                let totalFoto = uploadedData['splitter_' + sp] + serverData['splitter_' + sp];
                if (!qty || qty < 1 || totalFoto === 0) isValid = false;
            }
        });

        let btn = document.getElementById('btnSubmit');
        let btnText = document.getElementById('btnText');

        if (!isAnyDismantle) {
            btn.disabled = false;
            btn.className = 'w-full h-12 bg-slate-800 text-white font-black rounded-xl mt-8 shadow-md transition-all flex items-center justify-center gap-2';
            btnText.innerText = "Tidak Ada Dismantle, Lanjut Step 5 →";
        } else {
            if (isValid) {
                btn.disabled = false;
                btn.className = 'w-full h-12 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-xl mt-8 shadow-md transition-all flex items-center justify-center gap-2';
                btnText.innerText = "Simpan Data & Lanjut Step 5";
            } else {
                btn.disabled = true;
                btn.className = 'w-full h-12 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl mt-8 shadow-sm transition-all flex items-center justify-center gap-2';
                btnText.innerText = "Lengkapi QTY & Foto Eviden";
            }
        }
    }
</script>
@endsection