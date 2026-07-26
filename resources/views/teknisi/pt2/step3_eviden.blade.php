@extends('layouts.teknisi')

@section('content')
@php
    $allEvidences = array_merge($requiredEvidences ?? [], $optionalEvidences ?? []);
@endphp

<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    @include('teknisi.partials.stepper', ['title' => 'Step 3 - Eviden Redaman'])

    <div class="px-4 mt-3">
        <div class="bg-white rounded-xl border border-gray-200/80 p-3 shadow-xs">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">Nama LOP</p>
            <p class="text-xs font-bold text-gray-900 leading-snug break-words mb-2.5">{{ $project->project_name ?? '-' }}</p>
            <div class="flex items-center gap-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-[11px] font-semibold text-gray-700">
                    <span class="text-gray-400 mr-1 font-normal">STO:</span>
                    <span class="font-mono">{{ $project->lop->sto ?? '-' }}</span>
                </span>
            </div>
        </div>
    </div>

    <div class="px-5 mt-6">
        @if(session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-xs font-bold">{{ session('success') }}</div>
        @endif

        <div class="mb-5 bg-blue-50 border border-blue-100 p-4 rounded-2xl flex items-start gap-3">
            <span class="text-xl">💡</span>
            <p class="text-xs font-medium text-blue-800 leading-relaxed">
                Mode <strong>{{ $mode }}</strong>. Pilih <strong>{{ str_replace('_', ' ', $targetPortCount) }} Foto</strong> sekaligus. Foto akan otomatis dikompres sebelum dikirim agar cepat & hemat kuota.
            </p>
        </div>

        <form id="evidenForm" action="{{ route('teknisi.pt2.storeStep3Eviden', $project->id_project) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-3">
                @foreach($allEvidences as $key => $label)
                    @php
                        $uploaded = isset($existingEvidences[$key]) ? $existingEvidences[$key] : collect();
                        $hasUploaded = $uploaded->count() > 0;
                        $isRequired = array_key_exists($key, $requiredEvidences);
                    @endphp

                    <details id="details-{{ $key }}" class="group bg-white border border-slate-200 rounded-2xl shadow-sm [&_summary::-webkit-details-marker]:hidden transition-all duration-300 {{ $hasUploaded ? 'open' : '' }}">
                        
                        <summary id="summary-{{ $key }}" class="flex items-center justify-between p-4 cursor-pointer rounded-2xl transition">
                            <div class="flex items-center gap-3">
                                <div id="icon-{{ $key }}" class="w-8 h-8 rounded-full flex items-center justify-center text-sm bg-slate-100 text-slate-400">
                                    📷
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
                            <label class="relative flex flex-col items-center justify-center w-full h-20 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-white hover:bg-slate-50 transition">
                                <span class="text-xs font-bold text-slate-500">+ Pilih Multiple Foto</span>
                                <input type="file" id="input-{{ $key }}" name="evidences[{{ $key }}][]" multiple accept="image/*" class="hidden" onchange="handleFileSelect(this, '{{ $key }}')">
                            </label>

                            {{-- INDIKATOR LOADING SAAT PROSES KOMPRES --}}
                            <div id="loading-{{ $key }}" class="hidden mt-2 text-[10px] font-bold text-blue-600 animate-pulse text-center">
                                Sedang mengompres foto... mohon tunggu...
                            </div>

                            <div id="preview-{{ $key }}" class="grid grid-cols-4 gap-2 mt-3 empty:hidden"></div>

                            @if($hasUploaded)
                                <div class="mt-4 pt-3 border-t border-slate-200">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Tersimpan di Database</p>
                                    <div class="grid grid-cols-4 gap-2">
                                        @foreach($uploaded as $ev)
                                            <div class="relative rounded-xl overflow-hidden border border-slate-200 aspect-square shadow-sm">
                                                <img src="{{ asset('storage/' . $ev->file_path) }}" class="w-full h-full object-cover">
                                                <button type="button" onclick="event.preventDefault(); document.getElementById('form-delete-{{ $ev->id_evidence }}').submit();" class="absolute top-1 right-1 w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center text-[9px] font-black shadow-md">✕</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>

            <button type="submit" id="btnSubmit" disabled class="w-full h-12 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl mt-8 shadow-sm transition-all flex items-center justify-center gap-2">
                <span>Upload Baru & Lanjut Step 4</span>
            </button>

            <a href="{{ route('teknisi.pt2.step4Eviden', $project->id_project) }}"  class="block w-full h-11 bg-white border-2 border-slate-200 text-slate-600 text-center leading-[2.5rem] font-bold rounded-xl mt-3 active:scale-95 transition">
                Lewati (Lanjut Step 4) →
            </a>
        </form>

        {{-- Form Hapus Eviden Database --}}
        @foreach($allEvidences as $key => $label)
            @if(isset($existingEvidences[$key]))
                @foreach($existingEvidences[$key] as $ev)
                    <form id="form-delete-{{ $ev->id_evidence }}" action="{{ route('teknisi.pt2.deleteEvidence', $ev->id_evidence) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        @endforeach

    </div>
</div>

{{-- BOTTOM NAV --}}
@include('teknisi.partials.bottom-nav', ['active' => 'home'])

<script>
    const targetPortCount = '{{ $targetPortCount }}'; 
    const allKeys = @json(array_keys($allEvidences));
    
    let serverData = {
        @foreach($allEvidences as $key => $label)
            '{{ $key }}': {{ isset($existingEvidences[$key]) ? $existingEvidences[$key]->count() : 0 }},
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

    // MESIN KOMPRES GAMBAR
    function compressImage(file, maxWidth = 1280, quality = 0.75) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let w = img.width, h = img.height;
                    if (w > maxWidth) { h = Math.round((h * maxWidth) / w); w = maxWidth; }
                    canvas.width = w; canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    canvas.toBlob((blob) => {
                        let newFileName = file.name.replace(/\.[^/.]+$/, "") + '.jpg';
                        resolve(new File([blob], newFileName, { type: 'image/jpeg', lastModified: Date.now() }));
                    }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    // Fungsi ASYNC agar menunggu file dikompres semua dulu
    async function handleFileSelect(inputElement, key) {
        let files = Array.from(inputElement.files);
        if (files.length === 0) return;

        // Munculkan tulisan loading
        document.getElementById('loading-' + key).classList.remove('hidden');

        // Proses kompres semua foto (Looping)
        for (let file of files) {
            if (file.type.startsWith('image/')) {
                const compressedFile = await compressImage(file, 1280, 0.7); 
                newFilesStore[key].push(compressedFile);
            }
        }
        
        inputElement.value = '';
        
        // Sembunyikan loading
        document.getElementById('loading-' + key).classList.add('hidden');
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
                    <div class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[7px] text-center py-0.5">Baru</div>
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
        
        let state = 'empty'; 
        let msg = '';
        
        if (isPort) {
            if (total === 0) { state = 'empty'; msg = 'Belum ada foto dipilih'; }
            else {
                if (targetPortCount == '16') {
                    if (total == 16) { state = 'complete'; msg = '✅ 16 / 16 foto siap'; }
                    else if (total > 16) { state = 'over'; msg = '❌ Kelebihan ' + (total-16) + ' foto!'; }
                    else { state = 'partial'; msg = '⚠️ ' + total + ' / 16 foto (Kurang '+(16-total)+')'; }
                } 
                else if (targetPortCount == '8') {
                    if (total == 8) { state = 'complete'; msg = '✅ 8 / 8 foto siap'; }
                    else if (total > 8) { state = 'over'; msg = '❌ Kelebihan ' + (total-8) + ' foto!'; }
                    else { state = 'partial'; msg = '⚠️ ' + total + ' / 8 foto (Kurang '+(8-total)+')'; }
                }
                else { // 8_atau_16
                    if (total == 8 || total == 16) { state = 'complete'; msg = '✅ ' + total + ' foto siap'; }
                    else if (total > 16) { state = 'over'; msg = '❌ Kelebihan foto!'; }
                    else { state = 'partial'; msg = '⚠️ ' + total + ' foto (Harus pas 8 atau 16)'; }
                }
            }
        } else {
            if (total === 0) { state = 'empty'; msg = 'Belum ada foto (Opsional)'; }
            else { state = 'complete'; msg = '✅ ' + total + ' foto siap'; }
        }

        let summary = document.getElementById('summary-' + key);
        let icon = document.getElementById('icon-' + key);
        let countText = document.getElementById('count-' + key);
        
        summary.className = 'flex items-center justify-between p-4 cursor-pointer rounded-2xl transition ' + 
            (state === 'complete' ? 'bg-green-50/50' : (state === 'partial' ? 'bg-amber-50/50' : (state === 'over' ? 'bg-red-50/50' : '')));
        
        icon.className = 'w-8 h-8 rounded-full flex items-center justify-center text-sm ' + 
            (state === 'complete' ? 'bg-green-100 text-green-600' : (state === 'partial' ? 'bg-amber-100 text-amber-600' : (state === 'over' ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400')));
        icon.innerText = (state === 'complete' ? '✅' : (state === 'partial' ? '⚠️' : (state === 'over' ? '❌' : '📷')));
        
        countText.className = 'text-[10px] font-medium mt-0.5 ' + 
            (state === 'complete' ? 'text-green-600' : (state === 'partial' ? 'text-amber-600' : (state === 'over' ? 'text-red-600' : 'text-slate-400')));
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