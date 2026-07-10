@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 px-4 py-2 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- KOTAK CARD MODERNISED TOP BAR BACK CONTROLS --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="min-w-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 uppercase tracking-wider">
                    Review Tahap Akhir
                </span>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight mt-2">
                    Rekapitulasi Quantity BOQ (Plan vs Actual)
                </h1>
                <div class="pt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400 font-bold">
                    <span>LOP: <b class="text-slate-700 dark:text-slate-300"> {{ $project->project_name }}</b></span>
                </div>
                <div class="pt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400 font-bold">
                    <span>Branch: <b class="text-slate-700 dark:text-slate-300"> {{ $project->lop?->branch ?? '-' }} </b></span>
                    <span> • </span>
                    <span>STO: <b class="text-slate-700 dark:text-slate-300"> {{ $project->lop?->sto ?? '-' }} </b></span>
                    </div>
            </div>
            
            <a href="{{ route('admin.projects.review.finishing', $project->id_project) }}"
            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-black shadow-xs inline-flex items-center justify-center transition hover:bg-slate-50 shrink-0">
                ← Kembali ke Review Finishing
            </a>
        </div>
    </div>

    {{-- GENERATE METRICS RINGKASAN AKURASI (KABEL & TIANG) --}}
    @php
        $boqItems = $project->boqItems ?? collect();
        
        $materialBoqItems = $boqItems->filter(function ($boq) {
            $designator = $boq->designatorData ?? $boq->designatorDataByCode;
            return str_starts_with($boq->designator, 'M-') || optional($designator)->type === 'material';
        });

        $kpiItems = $materialBoqItems->filter(function($item) {
            return optional($item->designatorData)->progress_category !== null;
        });

        $kabelItems = $kpiItems->filter(function($item) {
            $category = trim(strtoupper($item->designatorData->progress_category));
            return $category === 'KABEL';
        });

        $tiangItems = $kpiItems->filter(function($item) {
            $category = trim(strtoupper($item->designatorData->progress_category));
            return $category === 'TIANG';
        });

        $planKabel = $kabelItems->sum('quantity_plan');
        $actualKabel = $kabelItems->sum('quantity_actual');
        $planTiang = $tiangItems->sum('quantity_plan');
        $actualTiang = $tiangItems->sum('quantity_actual');

        $accKabel = $planKabel > 0 ? round(($actualKabel / $planKabel) * 100) : 0;
        $accTiang = $planTiang > 0 ? round(($actualTiang / $planTiang) * 100) : 0;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- SUMMARY KABEL --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-5 rounded-3xl shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-4">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 border border-emerald-100 dark:border-emerald-900/60 uppercase">
                        Kategori Kabel
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 border-b border-slate-50 dark:border-slate-800 pb-4">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Total Plan</p>
                        <p class="text-xl font-black text-slate-800 dark:text-white mt-0.5 font-mono">{{ number_format($planKabel, 0, ',', '.') }} <span class="text-xs font-normal text-slate-400">meter</span></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Total Aktual Lapangan</p>
                        <p class="text-xl font-black text-emerald-600 mt-0.5 font-mono">{{ number_format($actualKabel, 0, ',', '.') }} <span class="text-xs font-normal text-slate-400">meter</span></p>
                    </div>
                </div>
            </div>
            <span class="text-xs text-right font-black text-emerald-600">Persentase {{ $accKabel }}%</span>
            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mt-4">
                <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: {{ $accKabel }}%"></div>
            </div>
        </div>

        {{-- SUMMARY TIANG --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-5 rounded-3xl shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-4">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold bg-blue-50 dark:bg-blue-950/40 text-blue-600 border border-blue-100 dark:border-blue-900/60 uppercase">
                        Kategori Tiang
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 border-b border-slate-50 dark:border-slate-800 pb-4">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Total Plan</p>
                        <p class="text-xl font-black text-slate-800 dark:text-white mt-0.5 font-mono">{{ number_format($planTiang, 0, ',', '.') }} <span class="text-xs font-normal text-slate-400">pcs</span></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Total Aktual Lapangan</p>
                        <p class="text-xl font-black text-blue-600 mt-0.5 font-mono">{{ number_format($actualTiang, 0, ',', '.') }} <span class="text-xs font-normal text-slate-400">pcs</span></p>
                    </div>
                </div>
            </div>
            <span class="text-xs text-right font-black text-blue-600">Persentase {{ $accTiang }}%</span>
            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mt-4">
                <div class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width: {{ $accTiang }}%"></div>
            </div>
        </div>
    </div>

    {{-- TABEL MATERIAL DETIL --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-50 dark:border-slate-800/80 bg-slate-50/30">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Item Designator</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/60 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <th class="py-3.5 px-6">Designator</th>
                        <th class="py-3.5 px-4">Uraian Pekerjaan</th>
                        <th class="py-3.5 px-4 text-center">Volume Plan</th>
                        <th class="py-3.5 px-4 text-center">Volume Actual</th>
                        <th class="py-3.5 px-4 text-center">Satuan</th>
                        <th class="py-3.5 px-6 text-center">Status Pemenuhan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($materialBoqItems as $item)
                        @php
                            $isMatch = (float)$item->quantity_actual >= (float)$item->quantity_plan;
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            <td class="py-4 px-6 font-mono font-bold text-slate-600 dark:text-slate-400">
                                <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 border rounded border-slate-200 dark:border-slate-700">
                                    {{ $item->designator }}
                                </span>
                            </td>
                            <td class="py-4 px-4 font-black text-slate-800 dark:text-slate-200">
                                {{ $item->item_name }}
                            </td>
                            <td class="py-4 px-4 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                {{ number_format($item->quantity_plan, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 text-center font-mono font-black {{ $isMatch ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ number_format($item->quantity_actual ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 text-center text-slate-400">
                                {{ $item->unit }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if((float)$item->quantity_actual > (float)$item->quantity_plan)
                                    {{-- STATUS BARU: JIKA AKTUAL MELEBIHI TARGET PLAN --}}
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-extrabold text-[10px] border border-blue-100">
                                        ▲ Kelebihan Volume
                                    </span>
                                @elseif((float)$item->quantity_actual == (float)$item->quantity_plan)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-extrabold text-[10px] border border-emerald-100">
                                        ✓ Terpenuhi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-extrabold text-[10px] border border-amber-100">
                                        ⚠ Selisih Kurang
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 font-bold bg-white dark:bg-slate-900">
                                📁 Tidak ada record data material pada tabel BOQ proyek ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- SUBMIT ACC LOCK BUTTON --}}
    <div class="flex justify-end pt-2">
        <button type="button" onclick="finalizeUjiTerima()" class="h-11 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            Kunci & Terbitkan Berkas Uji Terima (UT)
        </button>
    </div>

</div>

{{-- FIX: MEMINDAHKAN SCRIPT LANGSUNG AGAR PASTI DIEKSEKUSI OLEH BROWSER --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function finalizeUjiTerima() {
    Swal.fire({
        title: 'Cetak Berkas Uji Terima?',
        text: "Quantity BOQ Actual akan dikunci dan dijadikan acuan dokumen BA UT untuk Rekon.",
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Ya, Terbitkan Dokumen!',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl shadow-xl font-sans text-xs'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ 
                title: 'Menyimpan Dokumen...', 
                text: 'Mengunci database rekapitulasi LOP.', 
                allowOutsideClick: false,
                didOpen: () => { 
                    Swal.showLoading(); 
                } 
            });
        }
    });
}
</script>
@endsection