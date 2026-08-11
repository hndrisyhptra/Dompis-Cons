@extends('layouts.admin')

@section('content')
@php
    $lop = $project->lop; // Data LOP yang sudah di-inject
    $mancore = $project->pt2Mancore;
    $step5Completed = $mancore ? true : false;
    
    // LOGIKA STATUS MENGGUNAKAN LOP (Kunci Masalah Ada di Sini)
    $isWaitingSdi = $lop->sdi_approval_status === 'pending';
    $isGoLive = $lop->is_golive == 1 || $lop->sdi_approval_status === 'approved';
@endphp

<div class="max-w-4xl mx-auto space-y-4 px-4 py-6">

    {{-- Header & Stepper --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Approval LOP PT 2</h1>
        <p class="text-sm text-gray-500">Pilih project untuk mulai review step by step</p>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-bold text-gray-900 dark:text-white truncate">{{ $lop->lop_name }}</h2>
                <p class="text-sm text-gray-500 font-medium mt-0.5">
                    PID: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $project->pid ?? '-' }}</span> · 
                    IHLD: <span class="font-mono text-cyan-600 dark:text-cyan-400">{{ $lop->id_ihld ?? '-' }}</span> · 
                    STO {{ $lop->sto ?? '-' }}
                </p>
            </div>
            <a href="{{ route('admin.pt2.approval') }}" class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 inline-flex items-center text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                ← Kembali
            </a>
        </div>
        
        {{-- PANGGIL STEPPER --}}
        @include('admin.pt2.partials.stepper', ['currentStep' => 5, 'lop' => $lop])
    </div>

    {{-- Step Title --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="h-1 bg-indigo-500"></div>
        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Step 5 — Mancore</h2>
                <p class="text-sm text-gray-500">Data update mancore untuk diserahkan ke tim SDI.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $step5Completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $step5Completed ? 'Data Lengkap' : 'Pending' }}
            </span>
        </div>
    </div>

    {{-- Form Data Mancore --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
        @if($mancore)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-500 uppercase">ODP Label</p>
                    <p class="text-lg font-black text-gray-900 dark:text-white mt-1">{{ $mancore->odp_label ?? '-' }}</p>
                </div>
                
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-500 uppercase">ODC Label</p>
                    <p class="text-lg font-black text-gray-900 dark:text-white mt-1">{{ $mancore->odc_label ?? '-' }}</p>
                </div>

                <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                    <p class="text-[10px] font-bold text-indigo-500 uppercase">Distribusi Core</p>
                    <p class="text-base font-black text-indigo-700 dark:text-indigo-400 mt-1">{{ $mancore->distribusi_core ?? '-' }}</p>
                </div>

                <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                    <p class="text-[10px] font-bold text-indigo-500 uppercase">Feeder Core</p>
                    <p class="text-base font-black text-indigo-700 dark:text-indigo-400 mt-1">{{ $mancore->feeder_core ?? '-' }}</p>
                </div>
            </div>
            
            <p class="text-xs text-gray-400 mt-4 text-right">Diupdate pada: {{ \Carbon\Carbon::parse($mancore->updated_at)->format('d M Y H:i') }}</p>
        @else
            <div class="p-8 text-center text-sm font-bold text-red-500 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-900/50">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> Teknisi belum menginput Data Mancore.
            </div>
        @endif
    </div>

    {{-- Footer Actions --}}
    <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-gray-200 dark:border-gray-800">
        {{-- TOMBOL KEMBALI KE DISMANTLE LOP INI --}}
        <a href="{{ route('admin.pt2.dismantle', $lop->id_pt2_lop) }}" class="text-sm font-bold text-gray-500 hover:text-gray-900 dark:hover:text-white transition">← Step Dismantle</a>
        
        <div class="flex flex-wrap items-center gap-2">
            
            @if($isGoLive)
                <span class="h-10 px-4 rounded-xl bg-emerald-100 text-emerald-700 text-sm font-bold inline-flex items-center justify-center border border-emerald-200">
                    ✅ Sudah Go-Live
                </span>
                
                {{-- TOMBOL GENERATE BERKAS: AKTIF --}}
                <a href="#" class="h-10 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold inline-flex items-center justify-center transition shadow-md gap-2">
                    📄 Generate Berkas
                </a>

            @elseif($isWaitingSdi)
                <span class="h-10 px-4 rounded-xl bg-amber-100 text-amber-700 text-sm font-bold inline-flex items-center justify-center border border-amber-200">
                    ⏳ Menunggu SDI
                </span>

                {{-- TOMBOL GENERATE BERKAS: DISABLE --}}
                <button type="button" disabled class="h-10 px-5 rounded-xl bg-gray-200 text-gray-400 cursor-not-allowed text-sm font-bold inline-flex items-center justify-center border border-gray-300 gap-2">
                    📄 Generate Berkas (Terkunci)
                </button>

            @else
                {{-- TOMBOL KIRIM KE SDI (PERHATIKAN ID YANG DIKIRIM ADALAH ID PROJECT INDUK) --}}
                <form id="formSendSdi" method="POST" action="{{ route('admin.pt2.sendToSdi', ['id' => $project->lop->id_pt2_lop]) }}">
    @csrf
    <button type="button" 
            onclick="confirmSendSdi()" 
            class="h-10 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold inline-flex items-center justify-center transition shadow-md gap-2" 
            {{ !$step5Completed ? 'disabled' : '' }}>
        Kirim ke SDI 🚀
    </button>
</form>

                {{-- TOMBOL GENERATE BERKAS: DISABLE --}}
                <button type="button" disabled class="h-10 px-5 rounded-xl bg-gray-200 dark:bg-gray-800 text-gray-400 cursor-not-allowed text-sm font-bold inline-flex items-center justify-center border border-gray-300 dark:border-gray-700 gap-2">
                    📄 Generate Berkas (Terkunci)
                </button>
            @endif

        </div>
    </div>

</div>

{{-- SCRIPT --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmSendSdi() {
        Swal.fire({
            title: 'Kirim LOP ke SDI?',
            html: "Pastikan <b>SEMUA EVIDEN</b> di dalam LOP ini sudah divalidasi.<br>LOP ini akan terkirim ke dashboard SDI.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Kirim Sekarang!',
            cancelButtonText: 'Batal',
            customClass: { popup: 'rounded-3xl' }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mengirim data PID ke sistem SDI.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading() }
                });
                document.getElementById('formSendSdi').submit();
            }
        });
    }

    @if(session('success'))
        Swal.fire({
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            icon: 'success',
            confirmButtonColor: '#10b981',
            customClass: { popup: 'rounded-3xl' }
        });
    @endif
</script>
@endsection