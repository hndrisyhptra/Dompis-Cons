@extends('layouts.admin')

@section('content')
@php
    $summary = $lop->progressSummary();
    $progress = $summary['progress'];
    $stageLabel = $summary['stageLabel'];
    $teknisi = $lop->assignment->teknisi ?? null;
    
    // Grouping evidences berdasarkan stage
    $evidencesByStage = $lop->evidences->groupBy('stage');
@endphp

<div class="max-w-7xl mx-auto space-y-6 px-4 py-4 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- BREADCRUMB & HEADER ACTION --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inbox.pt2') }}" class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800">
                        PROGRAM PT 2
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">ID LOP: #{{ $lop->id_pt2_lop }}</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                    Tracking & Review LOP
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase {{ $summary['badge'] }}">
                {{ $stageLabel }} ({{ $progress }}%)
            </span>
            @if($lop->is_golive || $lop->sdi_approval_status === 'approved')
                <span class="px-3.5 py-1.5 rounded-xl bg-emerald-500 text-white text-xs font-black flex items-center gap-1 shadow-sm">
                    ✅ GO-LIVE
                </span>
            @endif
        </div>
    </div>

    {{-- ALERT / NOTIFIKASI --}}
    @if(session('success'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 text-xs font-bold flex items-center justify-between">
            <span class="flex items-center gap-2">
                <span>🎉</span> {{ session('success') }}
            </span>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">✕</button>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 text-xs font-bold flex items-center justify-between">
            <span class="flex items-center gap-2">
                <span>⚠️</span> {{ session('error') }}
            </span>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">✕</button>
        </div>
    @endif

    {{-- MAIN CONTENT GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT COLUMN: TIMELINE & EVIDENCES (2 COLUMNS ON DESKTOP) --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- PROGRESS BAR CARD --}}
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-extrabold uppercase text-slate-400 tracking-wider">Progress Konstruksi LOP</span>
                    <span class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ $progress }}%</span>
                </div>
                <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden p-0.5">
                    <div class="h-full {{ $summary['color'] }} rounded-full transition-all duration-500 shadow-sm" style="width: {{ $progress }}%"></div>
                </div>

                {{-- TAHAPAN STEPPER --}}
                <div class="grid grid-cols-6 gap-2 mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
                    @php
                        $steps = [
                            ['name' => 'Survey', 'p' => 20],
                            ['name' => 'Progress', 'p' => 40],
                            ['name' => 'Finish', 'p' => 60],
                            ['name' => 'Dismantle', 'p' => 80],
                            ['name' => 'Complete', 'p' => 100],
                            ['name' => 'Go Live', 'p' => 100],
                        ];
                    @endphp
                    @foreach($steps as $idx => $st)
                        @php $isDone = $progress >= $st['p']; @endphp
                        <div class="flex flex-col items-center">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black mb-1.5 transition-all
                                {{ $isDone ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                {{ $isDone ? '✓' : ($idx + 1) }}
                            </div>
                            <span class="text-[10px] font-extrabold {{ $isDone ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400' }}">
                                {{ $st['name'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- DETAIL PER TAHAPAN (CARDS) --}}
            <div class="space-y-4">

                {{-- STEP 1: SURVEY --}}
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-yellow-100 dark:bg-yellow-950/50 text-yellow-700 dark:text-yellow-400 flex items-center justify-center font-black text-xs">
                                1
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-900 dark:text-white">Tahap 1: Survey Lokasi</h3>
                                <p class="text-[11px] text-slate-400 font-bold">Hasil survey awal teknisi di lapangan</p>
                            </div>
                        </div>
                        @if($lop->surveys->count() > 0)
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 text-[10px] font-black uppercase">
                                ✓ Data Tersedia
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-black uppercase">
                                Belum Ada Data
                            </span>
                        @endif
                    </div>

                    @forelse($lop->surveys as $srv)
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-800 text-xs space-y-2">
                            <div class="flex justify-between font-bold text-slate-600 dark:text-slate-300">
                                <span>Panjang Rute: {{ $srv->length_route ?? '-' }} M</span>
                                <span>Tanggal: {{ $srv->created_at ? $srv->created_at->format('d M Y H:i') : '-' }}</span>
                            </div>
                            @if($srv->notes)
                                <p class="text-slate-500 bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                                    💬 {{ $srv->notes }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic text-center py-4">Teknisi belum mengunggah data survey.</p>
                    @endforelse
                </div>

                {{-- STEP 2 S/D 5: EVIDENCES (PROGRESS, FINISH, DISMANTLE, MANCORE) --}}
                @php
                    $stagesConfig = [
                        'instalasi' => ['num' => 2, 'title' => 'Tahap 2: Progress / Instalasi', 'color' => 'amber'],
                        'redaman'   => ['num' => 3, 'title' => 'Tahap 3: Finish / Redaman', 'color' => 'blue'],
                        'dismantle' => ['num' => 4, 'title' => 'Tahap 4: Dismantle', 'color' => 'purple'],
                        'mancore'   => ['num' => 5, 'title' => 'Tahap 5: Complete / Mancore', 'color' => 'emerald'],
                    ];
                @endphp

                @foreach($stagesConfig as $stKey => $stMeta)
                    @php
                        $evList = $evidencesByStage->get($stKey) ?? collect();
                    @endphp

                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 flex items-center justify-center font-black text-xs">
                                    {{ $stMeta['num'] }}
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $stMeta['title'] }}</h3>
                                    <p class="text-[11px] text-slate-400 font-bold">Bukti foto & eviden pekerjaan teknisi</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-black uppercase">
                                {{ $evList->count() }} Foto Eviden
                            </span>
                        </div>

                        @if($evList->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($evList as $ev)
                                    <div class="bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col justify-between">
                                        {{-- MEDIA PREVIEW --}}
                                        <div class="relative bg-slate-900 h-44 flex items-center justify-center overflow-hidden group">
                                            @if($ev->file_path)
                                                <img src="{{ Storage::url($ev->file_path) }}" alt="Eviden" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                                <a href="{{ Storage::url($ev->file_path) }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white font-bold text-xs transition-opacity">
                                                    🔍 Lihat Foto Penuh
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-500 font-bold">Foto Tidak Tersedia</span>
                                            @endif

                                            {{-- BADGE STATUS EVIDEN --}}
                                            <div class="absolute top-2 right-2">
                                                @if($ev->status === 'approved')
                                                    <span class="px-2 py-0.5 rounded bg-emerald-500 text-white text-[9px] font-black uppercase shadow">
                                                        ✓ Approved
                                                    </span>
                                                @elseif($ev->status === 'rejected')
                                                    <span class="px-2 py-0.5 rounded bg-rose-500 text-white text-[9px] font-black uppercase shadow">
                                                        ✕ Rejected
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded bg-amber-500 text-white text-[9px] font-black uppercase shadow animate-pulse">
                                                        ⏳ Pending Review
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- META & ACTION BUTTONS --}}
                                        <div class="p-3 space-y-2">
                                            <div class="text-[11px] text-slate-500 font-bold flex justify-between">
                                                <span>Tipe: {{ ucfirst($ev->evidence_type ?? $stKey) }}</span>
                                                <span>{{ $ev->created_at ? $ev->created_at->format('d M H:i') : '-' }}</span>
                                            </div>

                                            @if($ev->review_note)
                                                <p class="text-[10px] text-rose-600 bg-rose-50 dark:bg-rose-950/40 p-2 rounded-lg border border-rose-100 dark:border-rose-900/40 font-semibold">
                                                    Catatan: {{ $ev->review_note }}
                                                </p>
                                            @endif

                                            {{-- TOMBOL APPROVE / REJECT JIKA PENDING --}}
                                            @if($ev->status === 'pending')
                                                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex gap-2">
                                                    {{-- FORM APPROVE --}}
                                                    <form method="POST" action="{{ route('admin.evidences.approve', $ev->id_evidence ?? $ev->id) }}" class="flex-1 m-0">
                                                        @csrf
                                                        <button type="submit" onclick="return confirm('Setujui foto eviden ini?')" class="w-full py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black transition">
                                                            ✓ Approve
                                                        </button>
                                                    </form>

                                                    {{-- FORM REJECT WITH NOTE --}}
                                                    <button type="button" 
                                                            onclick="let note = prompt('Masukkan alasan penolakan foto:'); if(note) { document.getElementById('reject-note-{{ $ev->id_evidence ?? $ev->id }}').value = note; document.getElementById('form-reject-{{ $ev->id_evidence ?? $ev->id }}').submit(); }" 
                                                            class="flex-1 py-1.5 rounded-xl bg-rose-100 text-rose-700 hover:bg-rose-200 text-[11px] font-black transition">
                                                        ✕ Reject
                                                    </button>

                                                    <form id="form-reject-{{ $ev->id_evidence ?? $ev->id }}" method="POST" action="{{ route('admin.evidences.reject', $ev->id_evidence ?? $ev->id) }}" class="hidden">
                                                        @csrf
                                                        <input type="hidden" name="review_note" id="reject-note-{{ $ev->id_evidence ?? $ev->id }}">
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-400 italic text-center py-4">Belum ada foto eviden diunggah pada {{ strtolower($stMeta['title']) }}.</p>
                        @endif
                    </div>
                @endforeach

            </div>
        </div>

        {{-- RIGHT COLUMN: LOP & TEKNISI SUMMARY METADATA --}}
        <div class="space-y-6">

            {{-- CARD DETAIL LOP --}}
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <h2 class="text-base font-black text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Informasi LOP PT 2
                </h2>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[10px]">Nama LOP</span>
                        <p class="font-black text-slate-800 dark:text-slate-100 leading-snug mt-0.5">{{ $lop->lop_name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[10px]">PID SAP</span>
                            <p class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $project->pid_sap ?? '-' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[10px]">PID Wadah</span>
                            <p class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $project->pid ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[10px]">Branch</span>
                            <p class="font-bold text-slate-800 dark:text-slate-200">{{ $lop->branch ?? '-' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[10px]">STO</span>
                            <p class="font-bold text-slate-800 dark:text-slate-200">{{ $lop->sto ?? '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[10px]">ID IHLD</span>
                        <p class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $lop->id_ihld ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- CARD INFORMASI TEKNISI --}}
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <h2 class="text-base font-black text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Teknisi Penanggung Jawab
                </h2>

                @if($teknisi)
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 flex items-center justify-center font-black text-sm">
                            {{ strtoupper(substr($teknisi->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ $teknisi->name }}</p>
                            <p class="text-xs text-emerald-600 font-bold">Teknisi PT 2 Terpilih</p>
                        </div>
                    </div>
                    <div class="text-[11px] text-slate-400 font-bold border-t border-slate-100 dark:border-slate-800 pt-3 flex justify-between">
                        <span>Ditugaskan Pada:</span>
                        <span>{{ $lop->assignment->created_at ? $lop->assignment->created_at->format('d M Y H:i') : '-' }}</span>
                    </div>
                @else
                    <div class="text-center py-4">
                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-bold">Belum Ada Teknisi</span>
                        <p class="text-[11px] text-slate-400 mt-2">Silakan assign teknisi pada dashboard admin PT 2.</p>
                    </div>
                @endif
            </div>

            {{-- CARD KML / PETA (OPSIONAL) --}}
            @if($lop->kml_file_path)
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-3">
                    <h2 class="text-base font-black text-slate-900 dark:text-white">
                        File KML Rute
                    </h2>
                    <a href="{{ Storage::url($lop->kml_file_path) }}" target="_blank" class="w-full py-2.5 px-4 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center justify-between">
                        <span>🗺️ Download File KML</span>
                        <span>⬇️</span>
                    </a>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection