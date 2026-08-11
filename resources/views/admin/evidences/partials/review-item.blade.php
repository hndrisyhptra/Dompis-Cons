@php
    $total = $items->count();
    $approvedCount = $items->where('status', 'approved')->count();
    $rejectedCount = $items->where('status', 'rejected')->count();
    $pendingCount = $items->where('status', 'pending')->count();

    // Logika Status Group
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
        default => $number ?? 1,
    };

    // =========================================================================
    // DYNAMIC ROUTE PREFIX & ID DETECTOR: Reguler vs PT 2
    // =========================================================================
    $isPt2 = $isPt2 ?? false; 
    $routePrefix = $isPt2 ? 'admin.pt2.evidence.' : 'admin.evidences.';
@endphp

<div x-data="{ open: false }"
     class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm transition-all">

    {{-- HEADER KATEGORI / DESIGNATOR (JUDUL UTAMA) --}}
    <button type="button"
            @click="open = !open"
            class="w-full p-5 flex items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">

        <div class="flex items-center gap-4 min-w-0">
            <div class="w-10 h-10 rounded-2xl border {{ $statusClass }} flex items-center justify-center text-sm font-black shrink-0 shadow-sm">
                {{ $iconText }}
            </div>
            <div class="text-left min-w-0">
                <h3 class="text-base font-black text-gray-900 dark:text-white truncate font-mono tracking-tight">
                    {{ $title }}
                </h3>
                <p class="text-xs font-medium text-gray-500 truncate mt-0.5">
                    Total {{ $total }} Foto · <span class="text-emerald-600 font-bold">{{ $approvedCount }} Approved</span> · <span class="text-amber-600 font-bold">{{ $pendingCount }} Pending</span>
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

    {{-- KONTEN COLLAPSIBLE (MUNCUL SAAT DIKLIK) --}}
    <div x-show="open" x-collapse x-cloak class="border-t border-gray-100 dark:border-gray-800 bg-slate-50/50 dark:bg-slate-900/50">
        <div class="p-5 space-y-5">

            {{-- JIKA INI STEP 2/4 (BOQ) --}}
            @if(isset($description) && !empty($description) && $description !== 'ada')
                <div class="bg-white dark:bg-gray-950 p-4 rounded-2xl border border-blue-100 dark:border-blue-900/50 shadow-xs space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded text-[10px] font-black uppercase tracking-wider">Detail</span>
                    </div>
                    
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nama Item Material:</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5 leading-snug">
                            {{ $subtitle_item_name ?? $description }}
                        </p>
                    </div>

                    @if(isset($plan) && isset($actual))
                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                            <div class="bg-slate-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-gray-800">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Target Plan</p>
                                <p class="text-sm font-black text-blue-600 dark:text-blue-400 mt-0.5 font-mono">{{ $plan }}</p>
                            </div>
                            <div class="bg-slate-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-gray-800">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Aktual Lapangan</p>
                                <p class="text-sm font-black text-emerald-600 dark:text-emerald-400 mt-0.5 font-mono">{{ $actual }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- HEADER AKSI & BULK APPROVE --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <p class="text-xs text-gray-500 leading-relaxed">
                    Periksa eviden di bawah ini. Anda dapat melakukan aksi approve/reject per foto atau menyetujui sekaligus.
                </p>

                @if($pendingCount > 0)
                    <form method="POST" action="{{ route($routePrefix . 'bulk-approve') }}">
                        @csrf
                        @foreach($items->where('status', 'pending') as $pendingItem)
                            @php $evidenceId = $isPt2 ? $pendingItem->id_pt2_evidence : $pendingItem->id_evidence; @endphp
                            <input type="hidden" name="evidence_ids[]" value="{{ $evidenceId }}">
                        @endforeach
                        
                        <button type="submit" onclick="return confirm('Approve semua {{ $pendingCount }} eviden pending di kategori ini?')"
                                class="h-10 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            Bulk Approval ({{ $pendingCount }})
                        </button>
                    </form>
                @endif
            </div>

            {{-- GRID FOTO / FILE EVIDEN --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($items as $evidence)
                    @php 
                        // Deteksi primary key secara cerdas
                        $evidenceId = $isPt2 ? $evidence->id_pt2_evidence : $evidence->id_evidence; 
                    @endphp

                    <div class="bg-white dark:bg-gray-950 rounded-[1.5rem] border border-gray-200 dark:border-gray-800 p-2.5 shadow-sm flex flex-col transition-all hover:border-blue-300">
                        
                        <div class="relative aspect-video rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 group flex items-center justify-center">
                            
                            {{-- ID BADGE --}}
                            <div class="absolute top-2 left-2 z-10 bg-black/70 backdrop-blur-sm text-white text-[10px] font-black px-2 py-1 rounded-lg flex items-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-tag text-blue-300"></i>
                                ID-{{ $evidenceId }}
                            </div>

                            {{-- STATUS BADGE --}}
                            <div class="absolute top-2 right-2 z-10 text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded-lg backdrop-blur-sm shadow-sm
                                {{ $evidence->status == 'approved' ? 'bg-emerald-500/90 text-white' : ($evidence->status == 'rejected' ? 'bg-red-500/90 text-white' : 'bg-amber-500/90 text-white') }}">
                                {{ $evidence->status }}
                            </div>

                            @if(str_ends_with(strtolower($evidence->file_path), '.sor'))
                                <a href="{{ asset('storage/' . $evidence->file_path) }}" download class="w-full h-full bg-indigo-50 dark:bg-indigo-950/50 flex flex-col items-center justify-center p-3 text-center group-hover:scale-105 transition-transform">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs mb-1 shadow-sm">SOR</div>
                                    <p class="text-[10px] font-bold text-indigo-900 dark:text-indigo-300 truncate w-full px-1">{{ basename($evidence->file_path) }}</p>
                                    <span class="text-[9px] text-indigo-500">Klik untuk Download</span>
                                </a>
                            @else
                                <a href="{{ asset('storage/' . $evidence->file_path) }}" target="_blank" class="block w-full h-full">
                                    <img src="{{ asset('storage/' . $evidence->file_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                </a>
                            @endif
                        </div>

                        @if($evidence->description)
                            <div class="mt-3 px-2">
                                <p class="text-[10px] font-bold text-gray-400 uppercase">Note Waspang:</p>
                                <p class="text-xs text-gray-700 dark:text-gray-300 font-medium leading-snug">{{ $evidence->description }}</p>
                            </div>
                        @endif

                        {{-- AKSI PER FOTO --}}
                        <div class="mt-3 flex flex-col flex-1 justify-end" x-data="{ showReject: false }">
                            @if($evidence->status == 'approved')
                                <div class="px-2 pb-1 flex justify-center">
                                    <form method="POST" action="{{ route($routePrefix . 'reset', $evidenceId) }}" onsubmit="return confirm('Batalkan approval eviden ini?')">
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
                                    <form method="POST" action="{{ route($routePrefix . 'reset', $evidenceId) }}">
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
                                    <form method="POST" action="{{ route($routePrefix . 'approve', $evidenceId) }}">
                                        @csrf
                                        <button type="submit" class="w-full h-9 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition shadow-sm shadow-emerald-500/20">
                                            Approve
                                        </button>
                                    </form>
                                </div>
                                <div x-show="showReject" x-collapse x-cloak>
                                    <form method="POST" action="{{ route($routePrefix . 'reject', $evidenceId) }}" class="bg-slate-50 dark:bg-gray-900 rounded-xl p-2 border border-slate-200 dark:border-gray-700">
                                        @csrf
                                        <textarea name="review_note" rows="2" required placeholder="Tuliskan ID foto yang direject (Misal: Blur)..." class="w-full rounded-lg border-gray-300 dark:border-gray-700 text-xs p-2.5 focus:ring-red-500 focus:border-red-500 resize-none bg-white dark:bg-gray-950"></textarea>
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
                    <div class="col-span-full py-10 text-center">
                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-2xl text-slate-400">📊</div>
                        <p class="text-sm font-bold text-gray-500">Belum ada file/eviden diunggah.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>