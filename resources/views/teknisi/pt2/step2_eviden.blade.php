@extends('layouts.teknisi')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    {{-- Header STEPPER --}}
    @include('teknisi.partials.stepper', ['title' => 'Step 2 - Progress Instalasi'])

    {{-- PROJECT INFO CARD --}}
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
            </div>
        </div>
    </div>

    <div class="px-5 mt-6">
        @if(session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-xs font-bold">{{ session('success') }}</div>
        @endif

        <form id="evidenForm" action="{{ route('teknisi.pt2.storeStep2Eviden', $project->id_project) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-3">
                @foreach($requiredEvidences as $key => $label)
                    @php
                        $uploaded = isset($existingEvidences[$key]) ? $existingEvidences[$key] : collect();
                        $hasUploaded = $uploaded->count() > 0;
                    @endphp

                    <details class="group bg-white border border-slate-200 rounded-2xl shadow-sm [&_summary::-webkit-details-marker]:hidden transition-all duration-300 {{ $hasUploaded ? 'open' : '' }}">
                        
                        <summary id="summary-{{ $key }}" class="flex items-center justify-between p-4 cursor-pointer rounded-2xl transition {{ $hasUploaded ? 'bg-green-50/50' : '' }}">
                            <div class="flex items-center gap-3">
                                <div id="icon-{{ $key }}" class="w-8 h-8 rounded-full flex items-center justify-center text-sm {{ $hasUploaded ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-400' }}">
                                    @if($hasUploaded)
                                        {{-- SVG Checklist --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="18" height="18" x="3" y="3" rx="2"/>
                                            <path d="m9 12 2 2 4-4"/>
                                        </svg>
                                    @else
                                        {{-- SVG Camera --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                            <circle cx="12" cy="13" r="3"/>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-slate-800">{{ $label }} <span class="text-red-500">*</span></h3>
                                    <p id="count-{{ $key }}" class="text-[10px] font-medium mt-0.5 {{ $hasUploaded ? 'text-green-600' : 'text-slate-400' }}">
                                        {{ $hasUploaded ? $uploaded->count().' foto tersimpan' : 'Belum ada foto dipilih' }}
                                    </p>
                                </div>
                            </div>
                        </summary>

                        <div class="p-4 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl">
                            
                            {{-- Area Klik Tambah Foto Baru --}}
                            <label class="relative flex flex-col items-center justify-center w-full h-20 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-white hover:bg-slate-50 transition">
                                <span class="text-xs font-bold text-slate-500">+ Tambah Foto Baru</span>
                                {{-- PERHATIKAN PENAMBAHAN ID PADA INPUT --}}
                                <input type="file" id="input-{{ $key }}" name="evidences[{{ $key }}][]" multiple accept="image/*" class="hidden" onchange="handleFileSelect(this, '{{ $key }}')">
                            </label>

                            {{-- Grid Preview Foto Baru --}}
                            <div id="preview-{{ $key }}" class="grid grid-cols-3 gap-2 mt-3 empty:hidden"></div>

                            {{-- Grid Review Foto Lama (Database) --}}
                            @if($hasUploaded)
                                <div class="mt-4 pt-3 border-t border-slate-200">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Tersimpan di Database</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach($uploaded as $ev)
                                            <div class="relative rounded-xl overflow-hidden border border-slate-200 aspect-square shadow-sm">
                                                <img src="{{ asset('storage/' . $ev->file_path) }}" class="w-full h-full object-cover">
                                                <button type="button" onclick="event.preventDefault(); document.getElementById('form-delete-{{ $ev->id_evidence }}').submit();" class="absolute top-1 right-1 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs font-black shadow-md">✕</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        </div>
                    </details>
                @endforeach
            </div>

            {{-- TOMBOL SUBMIT FORM --}}
            <button type="submit" id="btnSubmit" disabled 
                class="w-full h-12 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl mt-8 shadow-sm transition-all flex items-center justify-center gap-2">
                <span>Upload Baru & Lanjut Step 3</span>
            </button>

            {{-- TOMBOL SKIP / LEWATI --}}
            <a href="{{ route('teknisi.pt2.step3Eviden', $project->id_project) }}" 
               class="block w-full h-11 bg-white border-2 border-slate-200 text-slate-600 text-center leading-[2.5rem] font-bold rounded-xl mt-3 active:scale-95 transition">
                Lewati (Lanjut Step 3) →
            </a>
        </form>

        {{-- Form Hapus Eviden Database --}}
        @foreach($requiredEvidences as $key => $label)
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

<script>
    const requiredKeys = @json(array_keys($requiredEvidences));
    
    let serverData = {
        @foreach($requiredEvidences as $key => $label)
            '{{ $key }}': {{ isset($existingEvidences[$key]) ? $existingEvidences[$key]->count() : 0 }},
        @endforeach
    };

    let uploadedData = {};
    let newFilesStore = {}; // Wadah virtual penyimpan foto baru
    
    requiredKeys.forEach(key => {
        uploadedData[key] = 0;
        newFilesStore[key] = [];
    });

    document.addEventListener("DOMContentLoaded", function() {
        checkFormValidity();
    });

    // 1. Fungsi saat user memilih file
    function handleFileSelect(inputElement, key) {
        let files = Array.from(inputElement.files);
        
        // Gabungkan file lama di wadah dengan file yang baru dipilih
        newFilesStore[key] = newFilesStore[key].concat(files);
        
        // Reset input agar bisa pilih foto yang sama lagi jika perlu
        inputElement.value = '';
        
        renderPreviews(key);
    }

    // 2. Fungsi untuk menghapus file dari preview baru
    function removeNewFile(key, index) {
        // Hapus file dari wadah virtual berdasarkan index
        newFilesStore[key].splice(index, 1);
        renderPreviews(key);
    }

    // 3. Fungsi Render dan Sinkronisasi Form
    function renderPreviews(key) {
        const previewContainer = document.getElementById('preview-' + key);
        previewContainer.innerHTML = ''; 
        
        // Buat DataTransfer untuk memanipulasi FileList asli HTML
        let dataTransfer = new DataTransfer();
        
        newFilesStore[key].forEach((file, index) => {
            dataTransfer.items.add(file);
            
            let reader = new FileReader();
            reader.onload = function(e) {
                let div = document.createElement('div');
                div.className = 'relative rounded-xl overflow-hidden border border-blue-200 border-2 aspect-square';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <div class="absolute bottom-0 inset-x-0 bg-blue-600 text-white text-[8px] text-center py-0.5">Baru</div>
                    <button type="button" onclick="removeNewFile('${key}', ${index})" class="absolute top-1 right-1 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs font-black shadow-md transition hover:bg-red-700">✕</button>
                `;
                previewContainer.appendChild(div);
            }
            reader.readAsDataURL(file);
        });

        // MASUKKAN KEMBALI KE DALAM INPUT HIDDEN AGAR BISA DISUBMIT KE SERVER
        document.getElementById('input-' + key).files = dataTransfer.files;
        
        // Update kalkulasi total foto
        uploadedData[key] = newFilesStore[key].length;
        
        updateUI(key);
        checkFormValidity();
    }

    // Fungsi UI Header Akordeon
    function updateUI(key) {
        let total = uploadedData[key] + serverData[key];
        
        if (total > 0) {
            document.getElementById('summary-' + key).classList.add('bg-green-50/50');
            document.getElementById('icon-' + key).classList.replace('bg-slate-100', 'bg-green-100');
            document.getElementById('icon-' + key).classList.replace('text-slate-400', 'text-green-600');
            document.getElementById('icon-' + key).innerText = '✅';
            document.getElementById('count-' + key).innerText = total + ' foto siap';
            document.getElementById('count-' + key).classList.replace('text-slate-400', 'text-green-600');
        } else {
            document.getElementById('summary-' + key).classList.remove('bg-green-50/50');
            document.getElementById('icon-' + key).classList.replace('bg-green-100', 'bg-slate-100');
            document.getElementById('icon-' + key).innerText = '📷';
            document.getElementById('count-' + key).innerText = 'Belum ada foto';
            document.getElementById('count-' + key).classList.replace('text-green-600', 'text-slate-400');
        }
    }

    // Fungsi Validasi Tombol Lanjut
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