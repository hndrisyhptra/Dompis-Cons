@extends('layouts.admin')

@section('content')
{{-- Menggunakan Alpine.js terpusat untuk handling Modal Zoom tanpa library berat --}}
<div x-data="{ openZoom: false, zoomSrc: '' }" class="max-w-5xl mx-auto space-y-6 px-4 py-2 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- CARD HEADER CONTROLS --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="min-w-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black bg-blue-50 dark:bg-blue-950/40 text-blue-600 border border-blue-100 dark:border-blue-900/40 uppercase tracking-wider">
                    Export Eviden
                </span>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mt-2">
                    Bulk Download Eviden
                </h1>
                <p class="text-xs text-slate-400 font-bold mt-1">
                    LOP: <b class="text-slate-700 dark:text-slate-300">{{ $project->project_name }}</b>
                </p>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.evidences.review.finishing', $project->id_project) }}"
                   class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-black shadow-xs inline-flex items-center justify-center transition hover:bg-slate-50">
                    ← Kembali
                </a>
                {{-- DOWNLOAD MASSAL ALL IN ONE STAGE --}}
                <a href="{{ route('admin.projects.download_zip', $project->id_project) }}"
                   onclick="triggerDownloadAnimation('Semua')"
                   class="h-10 px-4 rounded-xl bg-green-600 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-black shadow-md inline-flex items-center justify-center gap-1.5 transition hover:bg-green-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
                   Download Semua Eviden
                </a>
            </div>
        </div>
    </div>

    {{-- LOOPING PER-STEP MANAGEMENT --}}
    <div class="space-y-6">
        @php
            $stages = [
                ['key' => 'persiapan', 'title' => 'Step 1 - Persiapan', 'color' => 'bg-red-500'],
                ['key' => 'instalasi', 'title' => 'Step 2 - Instalasi', 'color' => 'bg-blue-600'],
                ['key' => 'pengukuran', 'title' => 'Step 3 - Pengukuran', 'color' => 'bg-amber-500'],
                ['key' => 'finishing', 'title' => 'Step 4 - Finishing', 'color' => 'bg-emerald-600']
            ];
        @endphp

        @foreach($stages as $stage)
            @php 
                $stageEvidences = $project->evidences->where('stage', $stage['key']); 
            @endphp
            
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
                {{-- HEADER PER STEP DENGAN TOMBOL DOWNLOAD SPESIFIK --}}
                <div class="p-4 border-b border-slate-50 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-800/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full {{ $stage['color'] }}"></span>
                        <h3 class="text-sm font-black text-slate-800 dark:text-white tracking-tight">{{ $stage['title'] }}</h3>
                        <span class="text-[11px] text-slate-400 font-bold">({{ $stageEvidences->count() }} Foto Approved)</span>
                    </div>

                    @if($stageEvidences->count() > 0)
                        {{-- Button download zip dengan parameter query khusus step agar dinamis di controller --}}
                        <a href="{{ route('admin.projects.download_zip', $project->id_project) }}?only_stage={{ $stage['key'] }}"
                        onclick="triggerDownloadAnimation('{{ $stage['key'] }}')"
                        class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-[11px] font-black shadow-2xs inline-flex items-center justify-center gap-1.5 transition hover:bg-slate-50 text-slate-700 dark:text-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
                        Download Step {{ ucfirst($stage['key']) }}
                        </a>
                    @endif
                </div>

                {{-- GRID THUMBNAIL FOTO KECIL-KECIL --}}
                <div class="p-5">
                    @if($stageEvidences->count() > 0)
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3">
                            @foreach($stageEvidences as $ev)
                                @php
                                    // LOGIKA PERUBAHAN LABEL SESUAI ATURAN DESIGNATOR BARU
                                    $designatorName = $ev->boqItem?->designator ?? null;
                                    
                                    if ($stage['key'] === 'instalasi') {
                                        $labelBadge = $designatorName ? ' ' . $designatorName : 'Material';
                                    } elseif ($stage['key'] === 'finishing') {
                                        $labelBadge = $designatorName ? 'Final Eviden: ' . $designatorName : 'Final';
                                    } else {
                                        $labelBadge = strtoupper(str_replace('_', ' ', $ev->evidence_type));
                                    }
                                @endphp

                                <div class="space-y-1 group relative">
                                    {{-- Mini Thumbnail Frame --}}
                                    <div @click="openZoom = true; zoomSrc = '{{ asset('storage/' . $ev->file_path) }}'"
                                         class="aspect-square rounded-xl overflow-hidden bg-slate-50 border border-slate-200/60 dark:border-slate-700/80 cursor-pointer shadow-2xs hover:scale-105 active:scale-95 transition-all relative">
                                        <img src="{{ asset('storage/' . $ev->file_path) }}" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-icon lucide-eye"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                        </div>
                                    </div>
                                    {{-- Keterangan Label Sub-Text Mini --}}
                                    <p class="text-[9px] font-black text-slate-400 text-center truncate px-0.5" title="{{ $labelBadge }}">
                                        {{ $labelBadge }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic text-center py-4">Belum ada berkas yang disetujui (Approved) pada tahapan ini.</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- INTERACTIVE MODAL OVERLAY ZOOM VIEW (ALPINE HANDLED) --}}
    <div x-show="openZoom" 
         x-transition.opacity
         @keydown.escape.window="openZoom = false"
         class="fixed inset-0 z-[9999] bg-black/80 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div class="relative max-w-3xl w-full max-h-[85vh] flex flex-col items-center" @click.away="openZoom = false">
            {{-- Tombol Close X Pojok Atas --}}
            <button type="button" @click="openZoom = false" class="absolute -top-12 right-0 w-9 h-9 rounded-full bg-white/10 text-white font-black text-lg flex items-center justify-center hover:bg-white/20 transition">
                ×
            </button>
            {{-- Foto Zoom Main Frame --}}
            <img :src="zoomSrc" class="max-w-full max-h-[80vh] object-contain rounded-2xl shadow-2xl border border-white/10">
        </div>
    </div>

</div>

{{-- SCRIPT SWEETALERT FEEDBACK --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function triggerDownloadAnimation(stepName) {
    Swal.fire({
        title: 'Mengompres Data...',
        text: 'Sedang membungkus berkas ' + stepName + ' ke format ZIP. Unduhan akan segera berjalan.',
        icon: 'info',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false,
        customClass: { popup: 'rounded-3xl shadow-xl text-xs' }
    });
}
</script>
@endsection