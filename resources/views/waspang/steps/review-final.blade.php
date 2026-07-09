@extends('layouts.waspang') {{-- Sesuaikan dengan layout parent mobile Anda --}}

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-32 font-sans selection:bg-blue-500 selection:text-white">

    {{-- TOP GLASSMORPHIC STICKY HEADER --}}
    <div class="sticky top-0 z-50 bg-blue-700/95 backdrop-blur-md text-white px-5 pt-6 pb-5 rounded-b-[2rem] shadow-lg shadow-blue-900/10 transition-all">
        <div class="flex items-center gap-4">
            <a href="{{ route('waspang.projects.finishing', $project->id_project) }}" 
               class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left-icon lucide-chevron-left">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </a>
            <div class="min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-200/80">Step 4: Validasi Akhir</span>
                <h1 class="text-lg font-black tracking-tight truncate mt-0.5">Review BOQ Final</h1>
            </div>
        </div>
    </div>

    {{-- Project Info --}}
    <div class="px-4 mt-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs">
            <div class="mb-3">
                <p class="text-xs text-gray-400 font-medium">Nama LOP</p>
                <p class="text-sm font-bold text-gray-900 break-words mt-0.5">{{ $project->project_name }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 border-t border-gray-50 pt-3">
                <div>
                    <p class="text-xs text-gray-400 font-medium">STO</p>
                    <p class="text-xs font-bold text-gray-800 font-mono mt-0.5">{{ $project->lop?->sto ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-medium">Branch</p>
                    <p class="text-xs font-bold text-gray-800 mt-0.5">{{ $project->lop?->branch ?? '-' }}</p>
                </div>
                <div class="col-span-2 border-t border-gray-50 pt-2">
                    <p class="text-xs text-gray-400 font-medium">Mitra Pelaksana</p>
                    <p class="text-xs font-bold text-gray-800 mt-0.5 break-words">{{ $project->lop?->mitra_name ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- METRICS SUMMARY WIDGET (DASHBOARD-STYLE) --}}
    @php
        // 1. Saring item material yang benar-benar memiliki data designator
        $kpiItems = $materialBoqItems->filter(function($item) {
            return optional($item->designatorData)->progress_category !== null;
        });

        // 2. Kelompokkan berdasarkan progress_category master tabel (Case-Insensitive & Trimmed)
        $kabelItems = $kpiItems->filter(function($item) {
            $category = trim(strtoupper($item->designatorData->progress_category));
            return $category === 'KABEL';
        });

        $tiangItems = $kpiItems->filter(function($item) {
            $category = trim(strtoupper($item->designatorData->progress_category));
            return $category === 'TIANG';
        });

        // 3. Akumulasi Plan & Actual khusus untuk KABEL dan TIANG
        $planKabel   = $kabelItems->sum('quantity_plan');
        $actualKabel = $kabelItems->sum('quantity_actual');

        $planTiang   = $tiangItems->sum('quantity_plan');
        $actualTiang = $tiangItems->sum('quantity_actual');

        // 4. Hitung Persentase Akurasi Masing-Masing (Max 100%)
        $accuracyKabel = $planKabel > 0 ? min(100, round(($actualKabel / $planKabel) * 100)) : 0;
        $accuracyTiang = $planTiang > 0 ? min(100, round(($actualTiang / $planTiang) * 100)) : 0;
    @endphp

    <div class="px-4 mt-4">
        <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Match Quantity</h3>
                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] font-extrabold">
                    {{ $summary['matched'] }} / {{ $summary['total_items'] }} Item Match
                </span>
            </div>

            {{-- GRID KOMPARASI SIDE-BY-SIDE --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- CARD TOTAL TARGET PLAN --}}
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 flex flex-col justify-between">
                    <div>
                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-target-icon lucide-target">
                                <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>
                            </svg>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Total Plan</p>
                    </div>
                    <div class="mt-2 space-y-1">
                        <div class="flex items-baseline justify-between">
                            <span class="text-[10px] font-bold text-slate-400">Kabel:</span>
                            <p class="text-sm font-black text-slate-800">{{ number_format($planKabel, 0, ',', '.') }} <span class="text-[9px] font-normal text-slate-400">meter</span></p>
                        </div>
                        <div class="flex items-baseline justify-between border-t border-slate-200/60 pt-1">
                            <span class="text-[10px] font-bold text-slate-400">Tiang:</span>
                            <p class="text-sm font-black text-slate-800">{{ number_format($planTiang, 0, ',', '.') }} <span class="text-[9px] font-normal text-slate-400">pcs</span></p>
                        </div>
                    </div>
                </div>

                {{-- CARD TOTAL AKTUAL LAPANGAN --}}
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 flex flex-col justify-between">
                    <div>
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big-icon lucide-circle-check-big">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>    
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Total Actual</p>
                    </div>
                    <div class="mt-2 space-y-1">
                        <div class="flex items-baseline justify-between">
                            <span class="text-[10px] font-bold text-slate-400">Kabel:</span>
                            <p class="text-sm font-black text-emerald-600">{{ number_format($actualKabel, 0, ',', '.') }} <span class="text-[9px] font-normal text-slate-400">meter</span></p>
                        </div>
                        <div class="flex items-baseline justify-between border-t border-slate-200/60 pt-1">
                            <span class="text-[10px] font-bold text-slate-400">Tiang:</span>
                            <p class="text-sm font-black text-emerald-600">{{ number_format($actualTiang, 0, ',', '.') }} <span class="text-[9px] font-normal text-slate-400">pcs</span></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PROGRESS BARS AKURASI (TERPISAH KABEL & TIANG) --}}
            <div class="mt-4 pt-3 border-t border-slate-50 space-y-3">
                {{-- PROGRESS KABEL (HIJAU) --}}
                <div>
                    <div class="flex justify-between text-[11px] text-slate-500 font-medium mb-1">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Volume Kabel Terpenuhi:
                        </span>
                        <span class="font-bold text-emerald-600">{{ $accuracyKabel }}%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: {{ $accuracyKabel }}%"></div>
                    </div>
                </div>

                {{-- PROGRESS TIANG (BIRU) --}}
                <div>
                    <div class="flex justify-between text-[11px] text-slate-500 font-medium mb-1">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            Volume Tiang Terpenuhi:
                        </span>
                        <span class="font-bold text-blue-600">{{ $accuracyTiang }}%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width: {{ $accuracyTiang }}%"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- DETAIL ITEM COMPARISON LIST --}}
    <div class="px-4 mt-6 space-y-3">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Item BOQ Plan vs BOQ Actual</h3>
            <span class="text-[11px] text-slate-500 font-medium">Scroll ke bawah</span>
        </div>
        
        @forelse($materialBoqItems as $item)
            @php
                $isMatch = (float)$item->quantity_actual >= (float)$item->quantity_plan;
                // Deteksi warna badge / text dinamis
                $themeClass = $isMatch 
                    ? 'border-emerald-100 bg-emerald-50/40 text-emerald-700' 
                    : 'border-amber-100 bg-amber-50/40 text-amber-700';
            @endphp
            
            <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs hover:border-blue-200 transition-all duration-200">
                {{-- Bagian Atas Card: Informasi Identitas Material --}}
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-[10px] font-mono font-bold text-slate-600 tracking-tight">
                            {{ $item->designator }}
                        </span>
                        <h4 class="text-sm font-black text-slate-800 mt-2 break-words leading-snug">
                            {{ $item->item_name }}
                        </h4>
                    </div>

                    {{-- Status Rounded Icon Indicator --}}
                    @if($isMatch)
                        <span class="shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black shadow-xs shadow-emerald-200">✓</span>
                    @else
                        <span class="shrink-0 w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black shadow-xs shadow-amber-200">!</span>
                    @endif
                </div>

                {{-- Bagian Bawah Card: Perbandingan Berdampingan (Side-by-Side) --}}
                <div class="grid grid-cols-2 gap-2 mt-4 pt-3.5 border-t border-dashed border-slate-100">
                    <div class="bg-slate-50/60 rounded-xl px-3 py-2.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Target Plan</p>
                        <p class="text-sm font-extrabold text-slate-700 mt-0.5">
                            {{ number_format($item->quantity_plan, 0, ',', '.') }}
                            <span class="text-[10px] text-slate-400 font-normal ml-0.5">{{ $item->unit }}</span>
                        </p>
                    </div>

                    <div class="rounded-xl px-3 py-2.5 border border-dashed {{ $isMatch ? 'border-emerald-200 bg-emerald-50/20' : 'border-amber-200 bg-amber-50/20' }}">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Aktual Lapangan</p>
                        <p class="text-sm font-black {{ $isMatch ? 'text-emerald-600' : 'text-amber-600' }} mt-0.5">
                            {{ number_format($item->quantity_actual ?? 0, 0, ',', '.') }}
                            <span class="text-[10px] font-normal text-slate-400 ml-0.5">{{ $item->unit }}</span>
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl border border-slate-100 p-8 text-center text-xs text-slate-400 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-slate-300 mb-2.5"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                Tidak ada item material BOQ yang terdata pada proyek ini.
            </div>
        @endforelse
    </div>

    {{-- BOTTOM BAR ACTION BUTTON (MODERN FIXATION STYLE) --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-lg border-t border-slate-100 px-5 py-4 z-50 shadow-xl max-w-md mx-auto rounded-t-[1.8rem]">
        <button type="button" 
                onclick="confirmSubmitUt()" 
                class="w-full h-12 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm tracking-wide shadow-lg shadow-emerald-600/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
            Kunci & Kirim Berkas UT
        </button>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmSubmitUt() {
    Swal.fire({
        title: 'Kunci Data BOQ?',
        text: "Setelah dikirim, data kuantitas aktual akan dikunci secara permanen untuk proses cetak berkas Uji Terima (UT).",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#059669', // Emerald 600
        cancelButtonColor: '#64748B',  // Slate 500
        confirmButtonText: 'Ya, Kunci & Kirim!',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl shadow-2xl font-sans',
            title: 'text-lg font-black text-slate-800',
            confirmButton: 'rounded-xl px-4 py-2 text-xs font-bold',
            cancelButton: 'rounded-xl px-4 py-2 text-xs font-bold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses Berkas...',
                text: 'Mohon tunggu sebentar.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Logika kelanjutan AJAX submit anda disematkan di sini
            // window.location.href = "/url-proses-uji-terima";
        }
    });
}
</script>
@endsection