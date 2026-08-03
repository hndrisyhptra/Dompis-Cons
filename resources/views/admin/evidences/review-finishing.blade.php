@extends('layouts.admin')

@section('content')

@php
    $evidences = $project->evidences ?? collect();

    $materialBoqItems = ($project->boqItems ?? collect())->filter(function ($boq) {
        $designator = $boq->designatorData ?? $boq->designatorDataByCode;

        return optional($designator)->type === 'material'
            && optional($designator)->requires_finishing_evidence == 1;
    });
    
    $finalTotal = $materialBoqItems->count();
    $finalApproved = 0;

    foreach ($materialBoqItems as $boq) {
        $items = $evidences
            ->where('stage', 'finishing')
            ->where('evidence_type', 'final_boq')
            ->where('boq_item_id', $boq->id_boq);

        if ($items->count() > 0 && $items->where('status', 'approved')->count() == $items->count()) {
            $finalApproved++;
        }
    }

    $finishingApproved = $finalTotal == 0 || $finalApproved == $finalTotal;

    $statusClass = $finishingApproved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200';

    $stepSummary = [
        [
            'label' => 'Persiapan',
            'stage' => 'persiapan',
            'route' => route('admin.evidences.review.project', $project->id_project),
        ],
        [
            'label' => 'Instalasi',
            'stage' => 'instalasi',
            'route' => route('admin.evidences.review.instalasi', $project->id_project),
        ],
        [
            'label' => 'Pengukuran',
            'stage' => 'pengukuran',
            'route' => route('admin.evidences.review.pengukuran', $project->id_project),
        ],
    ];
@endphp

<div class="max-w-4xl mx-auto space-y-4">

    {{-- HEADER & STEPPER --}}
    @include('admin.evidences.partials.stepper')

    {{-- STEP TITLE CARD --}}
    <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="h-1.5 bg-green-600 w-full"></div>
        <div class="p-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Step 4 — Finishing
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Review hanya item material yang diwajibkan memiliki Eviden Final.
                </p>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold shrink-0
                {{ $finalApproved >= $finalTotal && $finalTotal > 0
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                {{ $finalApproved }}/{{ $finalTotal }} Approved
            </span>
        </div>
    </div>

    {{-- REVIEW SUMMARY CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach($stepSummary as $step)
            @php
                $stepItems = $project->evidences->where('stage', $step['stage']);
                $approved = $stepItems->where('status', 'approved')->count();
                $pending = $stepItems->where('status', 'pending')->count();
                $rejected = $stepItems->where('status', 'rejected')->count();
                $total = $stepItems->count();
                $isReviewed = $total > 0 && $pending == 0 && $rejected == 0;
            @endphp

            <a href="{{ $step['route'] }}" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 hover:shadow-md transition-all">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs text-gray-500">{{ $step['label'] }}</p>
                        <p class="mt-1 text-sm font-bold {{ $isReviewed ? 'text-green-700' : 'text-yellow-700' }}">
                            {{ $isReviewed ? '✓ Reviewed' : 'Needs Review' }}
                        </p>
                    </div>
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold {{ $isReviewed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $isReviewed ? '✓' : '!' }}
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-4 text-[10px] font-bold">
                    <span class="px-2 py-1 rounded-lg bg-green-100 text-green-700">Approved {{ $approved }}</span>
                    <span class="px-2 py-1 rounded-lg bg-yellow-100 text-yellow-700">Pending {{ $pending }}</span>
                    <span class="px-2 py-1 rounded-lg bg-red-100 text-red-700">Reject {{ $rejected }}</span>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <span class="text-[11px] text-gray-500">Total Eviden</span>
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ $total }}</span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- FINISHING FINAL BOQ EVIDEN (MENGGUNAKAN POLA REVIEW-ITEM) --}}
    <div class="space-y-4">
        @forelse($materialBoqItems as $boq)
            @php
                // Foto instalasi existing
                $instalasiItems = $evidences
                    ->where('stage', 'instalasi')
                    ->where('evidence_type', 'progress_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->sortByDesc('created_at');

                // Foto eviden final yang akan direview Admin
                $items = $evidences
                    ->where('stage', 'finishing')
                    ->where('evidence_type', 'final_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->sortByDesc('created_at');

                $total = $items->count();
                $approvedCount = $items->where('status', 'approved')->count();
                $rejectedCount = $items->where('status', 'rejected')->count();
                $pendingCount = $items->where('status', 'pending')->count();

                $groupStatus = 'pending';
                if ($rejectedCount > 0) {
                    $groupStatus = 'rejected';
                } elseif ($approvedCount === $total && $total > 0) {
                    $groupStatus = 'approved';
                }

                $statusClass = match ($groupStatus) {
                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900',
                    'rejected' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:border-red-900',
                    default => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900',
                };

                $iconText = match ($groupStatus) {
                    'approved' => '✓',
                    'rejected' => '×',
                    default => $loop->iteration,
                };

                $designatorTitle = $boq->designator ?? 'DESIGNATOR';
                $itemName = $boq->item_name;
                $planVal = number_format($boq->quantity_plan, 0, ',', '.') . ' ' . $boq->unit;
                $actualVal = number_format($boq->quantity_actual ?? 0, 0, ',', '.') . ' ' . $boq->unit;
                if ($boq->actual_reason) {
                    $actualVal .= " (Alasan 0: {$boq->actual_reason})";
                }
            @endphp

            <div x-data="{ open: false }" class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm transition-all">
                
                {{-- HEADER ACCORDION --}}
                <button type="button" @click="open = !open" class="w-full p-5 flex items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-10 h-10 rounded-2xl border {{ $statusClass }} flex items-center justify-center text-sm font-black shrink-0 shadow-sm">
                            {{ $iconText }}
                        </div>
                        <div class="text-left min-w-0">
                            <h3 class="text-base font-black text-gray-900 dark:text-white truncate font-mono tracking-tight">
                                {{ $designatorTitle }}
                            </h3>
                            <p class="text-xs font-medium text-gray-500 truncate mt-0.5">
                                Final {{ $total }} Foto · <span class="text-emerald-600 font-bold">{{ $approvedCount }} Approved</span> · <span class="text-amber-600 font-bold">{{ $pendingCount }} Pending</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <span class="px-3 py-1.5 rounded-xl border text-xs font-black uppercase tracking-wider {{ $statusClass }}">
                            {{ ucfirst($groupStatus) }}
                        </span>
                        <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500 transition-transform duration-300" :class="open ? 'rotate-180' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>
                </button>

                {{-- COLLAPSIBLE CONTENT --}}
                <div x-show="open" x-collapse x-cloak class="border-t border-gray-100 dark:border-gray-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="p-5 space-y-5">

                        {{-- KOTAK INFORMASI DETAIL ITEM BOQ (PLAN VS AKTUAL & ITEM NAME) --}}
                        <div class="bg-white dark:bg-gray-950 p-4 rounded-2xl border border-blue-100 dark:border-blue-900/50 shadow-xs space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded text-[10px] font-black uppercase tracking-wider">Detail</span>
                            </div>
                            
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nama Item Material:</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5 leading-snug">
                                    {{ $itemName }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                                <div class="bg-slate-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-gray-800">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Target Plan</p>
                                    <p class="text-sm font-black text-blue-600 dark:text-blue-400 mt-0.5 font-mono">{{ $planVal }}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-gray-800">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Aktual Waspang</p>
                                    <p class="text-sm font-black text-emerald-600 dark:text-emerald-400 mt-0.5 font-mono">{{ $actualVal }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- EVIDEN INSTALASI EXISTING (PREVIEW REFERENSI) --}}
                        <div>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-clock-rotate-left text-blue-500"></i> Refrensi Eviden Instalasi
                            </p>

                            @if($instalasiItems->count() > 0)
                                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                                    @foreach($instalasiItems as $evidence)
                                        <a href="{{ asset('storage/' . $evidence->file_path) }}" target="_blank" class="aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 block group">
                                            <img src="{{ asset('storage/' . $evidence->file_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-400 italic">Belum ada eviden instalasi untuk item ini.</p>
                            @endif
                        </div>

                        <hr class="border-gray-200 dark:border-gray-800">

                        {{-- HEADER AKSI & BULK APPROVE EVIDEN FINAL --}}
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Eviden Final</p>
                                <p class="text-xs text-gray-500 mt-0.5">Periksa dan setujui eviden final di bawah ini.</p>
                            </div>

                            @if($pendingCount > 0)
                                <form method="POST" action="{{ route('admin.evidences.bulk-approve') }}">
                                    @csrf
                                    @foreach($items->where('status', 'pending') as $pendingItem)
                                        <input type="hidden" name="evidence_ids[]" value="{{ $pendingItem->id_evidence }}">
                                    @endforeach
                                    
                                    <button type="submit" onclick="return confirm('Approve semua {{ $pendingCount }} eviden final pending di item ini?')"
                                            class="h-10 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2 shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        Bulk Approval ({{ $pendingCount }})
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- GRID FOTO EVIDEN FINAL (MENGGUNAKAN LOGIKA APPROVE/REJECT PER FOTO) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            @forelse($items as $evidence)
                                <div x-data="{ showReject: false }" class="bg-white dark:bg-gray-950 rounded-[1.5rem] border border-gray-200 dark:border-gray-800 p-2.5 shadow-sm flex flex-col transition-all hover:border-blue-300">
                                    
                                    <div class="relative aspect-video rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 group">
                                        {{-- ID BADGE --}}
                                        <div class="absolute top-2 left-2 z-10 bg-black/70 backdrop-blur-sm text-white text-[10px] font-black px-2 py-1 rounded-lg flex items-center gap-1.5 shadow-sm">
                                            <i class="fa-solid fa-tag text-blue-300"></i>
                                            ID-{{ $evidence->id_evidence }}
                                        </div>

                                        {{-- STATUS BADGE --}}
                                        <div class="absolute top-2 right-2 z-10 text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded-lg backdrop-blur-sm shadow-sm
                                            {{ $evidence->status == 'approved' ? 'bg-emerald-500/90 text-white' : ($evidence->status == 'rejected' ? 'bg-red-500/90 text-white' : 'bg-amber-500/90 text-white') }}">
                                            {{ $evidence->status }}
                                        </div>

                                        <a href="{{ asset('storage/' . $evidence->file_path) }}" target="_blank" class="block w-full h-full">
                                            <img src="{{ asset('storage/' . $evidence->file_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        </a>
                                    </div>

                                    {{-- NOTE WASPANG --}}
                                    @if($evidence->description)
                                        <div class="mt-3 px-2">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase">Note Waspang:</p>
                                            <p class="text-xs text-gray-700 dark:text-gray-300 font-medium leading-snug">{{ $evidence->description }}</p>
                                        </div>
                                    @endif

                                    {{-- AKSI PER FOTO (APPROVE / REJECT / RESET) --}}
                                    <div class="mt-3 flex flex-col flex-1 justify-end">
                                        @if($evidence->status == 'approved')
                                            <div class="px-2 pb-1 flex justify-center">
                                                <form method="POST" action="{{ route('admin.evidences.reset', $evidence->id_evidence) }}" onsubmit="return confirm('Batalkan approval eviden ini?')">
                                                    @csrf
                                                    <button type="submit" class="h-8 px-4 rounded-full bg-red-100 dark:bg-red-800/40 text-red-700 dark:text-red-300 text-[11px] font-bold hover:bg-red-200 transition shadow-sm">
                                                        <i class="fa-solid fa-rotate-left mr-1"></i> Batalkan Approval
                                                    </button>
                                                </form>
                                            </div>
                                        
                                        @elseif($evidence->status == 'rejected')
                                            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-3 border border-red-100 dark:border-red-900/50 flex flex-col items-center text-center">
                                                <p class="text-[10px] font-black text-red-700 dark:text-red-400 uppercase tracking-wide mb-1">Reason Reject:</p>
                                                <p class="text-xs text-red-900 dark:text-red-300 font-medium leading-snug mb-3">{{ $evidence->review_note }}</p>
                                                
                                                <form method="POST" action="{{ route('admin.evidences.reset', $evidence->id_evidence) }}">
                                                    @csrf
                                                    <button type="submit" class="h-8 px-4 rounded-full bg-red-100 dark:bg-red-800/40 text-red-700 dark:text-red-300 text-[11px] font-bold hover:bg-red-200 transition shadow-sm">
                                                        <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Reset
                                                    </button>
                                                </form>
                                            </div>
                                        
                                        @else
                                            <div class="grid grid-cols-2 gap-2" x-show="!showReject">
                                                <button type="button" @click="showReject = true" class="h-9 rounded-xl bg-red-50 text-red-600 text-xs font-bold hover:bg-red-100 transition border border-red-100">
                                                    Reject
                                                </button>
                                                <form method="POST" action="{{ route('admin.evidences.approve', $evidence->id_evidence) }}">
                                                    @csrf
                                                    <button type="submit" class="w-full h-9 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition shadow-sm shadow-emerald-500/20">
                                                        Approve
                                                    </button>
                                                </form>
                                            </div>

                                            <div x-show="showReject" x-collapse x-cloak>
                                                <form method="POST" action="{{ route('admin.evidences.reject', $evidence->id_evidence) }}" class="bg-slate-50 dark:bg-gray-900 rounded-xl p-2 border border-slate-200 dark:border-gray-700">
                                                    @csrf
                                                    <textarea name="review_note" rows="2" required placeholder="Tuliskan ID foto yang direject (Misal: ID-89 blur)..." class="w-full rounded-lg border-gray-300 dark:border-gray-700 text-xs p-2.5 focus:ring-red-500 focus:border-red-500 resize-none bg-white dark:bg-gray-950"></textarea>
                                                    <div class="flex items-center gap-2 mt-2">
                                                        <button type="button" @click="showReject = false" class="flex-1 h-8 rounded-lg bg-gray-200 text-gray-700 text-xs font-bold hover:bg-gray-300 transition">Batal</button>
                                                        <button type="submit" class="flex-1 h-8 rounded-lg bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition">Kirim Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full py-8 text-center text-xs text-gray-400 italic">
                                    Belum ada eviden final yang diunggah untuk item material ini.
                                </div>
                            @endforelse
                        </div>

                    </div>
                </div>

            </div>

        @empty
            <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 p-10 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-2xl mb-4">📭</div>
                <h3 class="text-sm font-black text-gray-900 dark:text-white">Tidak Ada Material Finishing</h3>
                <p class="text-xs text-gray-500 mt-1">Tidak ada item BOQ material yang diwajibkan memiliki eviden finishing.</p>
            </div>
        @endforelse
    </div>

    {{-- FOOTER NAVIGATION --}}
    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-800">
        <a href="{{ route('admin.evidences.review.pengukuran', $project->id_project) }}" class="h-11 px-6 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition inline-flex items-center shadow-sm">
            ← Step 3 (Pengukuran)
        </a>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.projects.download_preview', $project->id_project) }}" class="h-11 px-5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-slate-50 hover:bg-slate-100 text-sm font-bold inline-flex items-center justify-center gap-2 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                Bulk Download
            </a>

            @if($finishingApproved)
                <a href="{{ route('admin.projects.review_boq', $project->id_project) }}" class="h-11 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-black transition shadow-md shadow-blue-500/20 inline-flex items-center">
                    Review BOQ →
                </a>
            @endif
        </div>
    </div>

</div>

@endsection