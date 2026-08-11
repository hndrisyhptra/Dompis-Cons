@extends('layouts.admin')

@section('content')
@php
    $lop = $project->lop; // Diambil dari injeksi trik Controller
    $survey = $project->pt2Survey;
    $mode = $survey ? $survey->mode : 'A';
    
    // Tentukan kategori eviden wajib berdasarkan mode (Sama persis dengan TeknisiPt2Controller)
    $requiredEvidences = [];
    if ($mode === 'A') {
        $requiredEvidences = [
            'power_in' => 'Foto Eviden Power IN', 
            'power_out' => 'Foto Eviden Power OUT'
        ];
    } elseif ($mode === 'B') {
        $requiredEvidences = [
            'base_tray_feeder' => 'Foto Eviden Base Tray Feeder', 
            'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi',
            'power_in_feeder' => 'Foto Power IN Feeder', 
            'power_out_splitter' => 'Foto Power OUT Splitter Ex'
        ];
    } elseif ($mode === 'C') {
        $requiredEvidences = [
            'base_tray_feeder' => 'Foto Eviden Base Tray Feeder', 
            'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi'
        ];
    } else {
        $requiredEvidences = ['survey' => 'Foto Eviden Survey Lapangan'];
    }

    $persiapanEvidences = $project->evidences->where('stage', 'persiapan');
    
    // 1. Cek Apakah Data Survey Teks Sudah Approved
    $isSurveyApproved = $survey && $survey->pm_approval_status === 'approved';
    
    // 2. Cek Apakah Semua Eviden Foto Berdasarkan Mode Sudah Approved
    $filteredEvidences = $persiapanEvidences->whereIn('evidence_type', array_keys($requiredEvidences));
    $hasEvidences = $filteredEvidences->count() > 0;
    $areAllEvidencesApproved = $hasEvidences && ($filteredEvidences->where('status', 'approved')->count() === $filteredEvidences->count());
    
    // Step 1 Selesai jika Survey Teks Approved DAN Semua Foto Kategori Approved
    $step1Completed = $isSurveyApproved && $areAllEvidencesApproved;
@endphp

<div class="max-w-4xl mx-auto space-y-4 px-4 py-6">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            Approval LOP PT 2
        </h1>
        <p class="text-sm text-gray-500">
            Pilih project untuk mulai review step by step
        </p>
    </div>

    {{-- LOP Info Card & Stepper --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-bold text-gray-900 dark:text-white truncate">
                    {{ $lop->lop_name }}
                </h2>
                <p class="text-sm text-gray-500 font-medium">
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
        @include('admin.pt2.partials.stepper', ['currentStep' => 1, 'lop' => $lop])
    </div>

    {{-- Step Title & Status Badge --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="h-1 bg-indigo-500"></div>
        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Step 1 — Survey & Persiapan (Mode {{ $mode }})
                </h2>
                <p class="text-sm text-gray-500">
                    Tinjau data survey teks dan eviden foto persiapan awal.
                </p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold
                {{ $step1Completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $step1Completed ? 'Approved' : 'Pending Review' }}
            </span>
        </div>
    </div>

    {{-- Data Survey Card + Aksi Approve / Reject / Reset Teks Survey --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Data Survey Lapangan (PT 2)</h3>
                <p class="text-xs text-gray-400">Tinjau parameter teknis hasil survey lapangan oleh teknisi.</p>
            </div>

            {{-- TOMBOL AKSI LENGKAP: APPROVE / REJECT / ATUR ULANG (RESET) --}}
            @if($survey)
                <div class="flex items-center gap-2">
                    @if($survey->pm_approval_status === 'pending')
                        <form action="{{ route('admin.pt2.survey.approve', $lop->id_pt2_lop) }}" method="POST">
                            @csrf
                            <button type="submit" class="h-9 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition shadow-sm inline-flex items-center gap-1.5">
                                ✓ Approve Survey
                            </button>
                        </form>

                        <button type="button" onclick="rejectSurveyModal()" class="h-9 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black transition shadow-sm inline-flex items-center gap-1.5">
                            ✕ Reject
                        </button>

                    @elseif($survey->pm_approval_status === 'approved')
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                            ✓ Survey Approved
                        </span>
                        <form action="{{ route('admin.pt2.survey.reset', $lop->id_pt2_lop) }}" method="POST">
                            @csrf
                            <button type="submit" class="h-9 px-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition border border-gray-300">
                                Atur Ulang
                            </button>
                        </form>

                    @elseif($survey->pm_approval_status === 'rejected')
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold">
                            ✕ Survey Ditolak
                        </span>
                        <form action="{{ route('admin.pt2.survey.reset', $lop->id_pt2_lop) }}" method="POST">
                            @csrf
                            <button type="submit" class="h-9 px-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition border border-gray-300">
                                Atur Ulang
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
        
        @if($survey)
            
            {{-- BLOK KENDALA --}}
            @if($survey->has_kendala == 1)
                <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 shadow-inner">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                        <p class="text-xs font-black text-red-700 uppercase flex items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation"></i> Terkendala Saat Survey
                        </p>
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider
                            {{ $survey->pm_approval_status == 'approved' ? 'bg-green-100 text-green-700' : 
                              ($survey->pm_approval_status == 'rejected' ? 'bg-red-200 text-red-800' : 'bg-amber-100 text-amber-700') }}">
                            Status: {{ $survey->pm_approval_status ?? 'PENDING' }}
                        </span>
                    </div>
                    <p class="text-sm font-medium text-red-600">{{ $survey->kendala_note ?? 'Tidak ada catatan kendala.' }}</p>
                </div>
            @endif

            {{-- GRID DATA TEKNIS SURVEY --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Mode & Sub Mode A</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                        {{ $survey->mode ?? '-' }} <span class="text-gray-300 mx-1">/</span> {{ $survey->sub_mode_a ?? '-' }}
                    </p>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Nama ODP</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $survey->odp_name ?? '-' }}</p>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Tipe Kabel</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $survey->tipe_kabel ?? '-' }}</p>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Distribusi / Core EX</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                        {{ $survey->distribusi ?? '-' }} <span class="text-gray-300 mx-1">/</span> {{ $survey->core_ex ?? '-' }}
                    </p>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Power Out / In Feeder</p>
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mt-0.5 font-mono">
                        {{ $survey->power_out ?? '-' }} <span class="text-gray-300 mx-1">/</span> {{ $survey->power_in_feeder ?? '-' }}
                    </p>
                </div>

                <div class="p-3 bg-indigo-50/50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800">
                    <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-wide">Estimasi Jasa (Fixed)</p>
                    <p class="text-sm font-black text-indigo-700 dark:text-indigo-400 mt-0.5">
                        Rp {{ number_format($survey->fixed_jasa_price ?? 0, 0, ',', '.') }}
                    </p>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 md:col-span-2 lg:col-span-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Kesimpulan Survey</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1 leading-relaxed">
                        {{ $survey->kesimpulan ?? 'Belum ada kesimpulan yang ditulis.' }}
                    </p>
                </div>

            </div>
            
            <p class="text-xs text-gray-400 mt-3 text-right">Diinput pada: {{ \Carbon\Carbon::parse($survey->created_at)->format('d M Y H:i') }}</p>

        @else
            <div class="p-4 rounded-xl bg-amber-50 text-amber-600 text-sm font-bold border border-amber-100 flex items-center justify-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i> Data Survey belum disubmit oleh Teknisi.
            </div>
        @endif
    </div>

    {{-- EVIDEN FOTO PERSIAPAN --}}
    @foreach($requiredEvidences as $key => $label)
        @include('admin.evidences.partials.review-item', [
            'isPt2' => true,
            'number' => $loop->iteration,
            'title' => $label,
            'description' => 'Bukti foto eviden ' . strtolower($label) . ' lapangan.',
            'items' => $persiapanEvidences->where('evidence_type', $key),
            'type' => $key,
        ])
    @endforeach

    {{-- Footer Actions --}}
    <div class="flex items-center justify-between pt-2">
        <p class="text-sm text-gray-500 font-medium">Step 1 dari 5</p>
        <a href="{{ route('admin.pt2.instalasi', $lop->id_pt2_lop) }}" class="h-10 px-5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-bold inline-flex items-center justify-center transition hover:opacity-90">
            Step Instalasi →
        </a>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function rejectSurveyModal() {
        Swal.fire({
            title: 'Tolak Data Survey?',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan / Kendala',
            inputPlaceholder: 'Tuliskan alasan mengapa data survey ini ditolak...',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            confirmButtonText: 'Ya, Tolak Survey',
            cancelButtonText: 'Batal',
            customClass: { popup: 'rounded-3xl' }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('admin.pt2.survey.reject', $lop->id_pt2_lop) }}";
                
                let token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = "{{ csrf_token() }}";
                form.appendChild(token);

                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'kendala_note';
                input.value = result.value;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endsection