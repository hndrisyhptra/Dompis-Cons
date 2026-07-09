@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 px-4 py-2 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- CARD HEADER CONTROLS --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="min-w-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 uppercase tracking-wider">
                    Eksport Dokumen Fisik
                </span>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mt-2">
                    Preview Bulk Download Folder
                </h1>
                <p class="text-xs text-slate-400 font-bold mt-1">
                    Proyek: <b class="text-slate-700 dark:text-slate-300">{{ $project->project_name }}</b>
                </p>
            </div>
            
            <a href="{{ route('admin.evidences.review.finishing', $project->id_project) }}"
               class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-black shadow-xs inline-flex items-center justify-center transition hover:bg-slate-50 shrink-0">
                ← Kembali ke Finishing
            </a>
        </div>
    </div>

    {{-- INFORMASI KLASTERISASI FILE ZIP --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-50 dark:border-slate-800 bg-slate-50/30 flex items-center justify-between">
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Struktur Arsip File (.ZIP)</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Sistem otomatis membagi file ke dalam sub-folder berdasarkan tahap konstruksi.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-50 text-blue-700 border border-blue-100">
                {{ $project->evidences->count() }} Berkas Terpilih
            </span>
        </div>

        <div class="p-6 space-y-4">
            @php
                $stages = ['persiapan', 'instalasi', 'pengukuran', 'finishing'];
            @endphp

            <div class="space-y-2 font-mono text-xs text-slate-600 dark:text-slate-400">
                <p class="font-bold text-slate-800 dark:text-white">📁 Eviden_Approved_{{ Str::slug($project->project_name) }}.zip</p>
                
                @foreach($stages as $stage)
                    @php $stageEvidences = $project->evidences->where('stage', $stage); @endphp
                    <div class="pl-5 border-l border-dashed border-slate-200 dark:border-slate-700 py-1">
                        <p class="font-black text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            📂 {{ ucfirst($stage) }}/ 
                            <span class="font-normal text-[10px] text-slate-400">({{ $stageEvidences->count() }} berkas disetujui)</span>
                        </p>
                        
                        @foreach($stageEvidences as $ev)
                            <p class="pl-6 text-[11px] text-slate-400 truncate">
                                📄 [{{ strtoupper($ev->evidence_type) }}] · {{ basename($ev->file_path) }}
                            </p>
                        @endforeach
                        
                        @if($stageEvidences->isEmpty())
                            <p class="pl-6 text-[11px] text-slate-400 italic">Kosong (Tidak ada berkas yang di-approve)</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ACTION RUN GENERATE STREAM ZIP --}}
    <div class="flex justify-end pt-2">
        @if($project->evidences->count() > 0)
            <a href="{{ route('admin.projects.download_zip', $project->id_project) }}"
               onclick="triggerDownloadAnimation()"
               class="h-11 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                Unduh Semua Berkas (.ZIP)
            </a>
        @else
            <button disabled class="h-11 px-6 rounded-xl border border-slate-200 text-slate-400 text-xs font-black bg-slate-50 cursor-not-allowed">
                Tidak Ada Berkas Dapat Diunduh
            </button>
        @endif
    </div>

</div>

{{-- SCRIPT NOTIFICATION FEEDBACK ENGINE --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function triggerDownloadAnimation() {
    Swal.fire({
        title: 'Mengompilasi Berkas...',
        text: 'Sedang membungkus semua berkas fisik ke dalam satu folder. Proses download akan otomatis dimulai.',
        icon: 'info',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
        customClass: { popup: 'rounded-3xl shadow-xl' }
    });
}
</script>
@endsection