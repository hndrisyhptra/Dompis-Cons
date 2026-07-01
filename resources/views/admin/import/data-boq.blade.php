@extends('layouts.admin')

@section('content')

<div
    x-data="boqDetailModal()"
    class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">
                        BOQ Monitoring
                    </p>

                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">
                        Data BOQ
                    </h1>

                    <p class="text-sm text-slate-500 mt-2 max-w-2xl">
                        Monitoring hasil import BOQ per LOP, nilai pekerjaan, status assignment, dan detail item designator.
                    </p>
                </div>

                <a href="{{ route('admin.import.boq') }}"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload">
                            <path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>
                        </svg>
                    </span>
                    <span>Bulk Import BOQ</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">

                {{-- LOP DENGAN BOQ --}}
                <div class="rounded-3xl bg-white border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">
                                LOP Dengan BOQ
                            </p>

                            <p class="text-3xl font-black text-slate-900 mt-2">
                                {{ number_format($totalLopBoq) }}
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                BOQ berhasil diimport
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-spreadsheet-icon lucide-file-spreadsheet"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                             <path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- TOTAL NILAI BOQ --}}
                <div class="rounded-3xl bg-white border border-emerald-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-emerald-700 font-bold uppercase">
                                Total Nilai BOQ
                            </p>

                            <p class="text-2xl font-black text-emerald-700 mt-2">
                                Rp {{ number_format($totalBoqValue, 0, ',', '.') }}
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                Akumulasi seluruh BOQ
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hand-coins-icon lucide-hand-coins"><path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"/><path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/>
                                <path d="m2 16 6 6"/><circle cx="16" cy="9" r="2.9"/><circle cx="6" cy="5" r="3"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- SUDAH ASSIGN --}}
                <div class="rounded-3xl bg-white border border-indigo-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-indigo-700 font-bold uppercase">
                                Sudah Assign
                            </p>

                            <p class="text-3xl font-black text-indigo-700 mt-2">
                                {{ number_format($sudahAssign) }}
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                Sudah plotting ke Waspang
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round-arrow-left-icon lucide-user-round-arrow-left">
                                <path d="m19 16-3 3"/><path d="M2 21a8 8 0 0 1 12.664-6.5"/><path d="M22 19h-6l3 3"/><circle cx="10" cy="8" r="5"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- BELUM ASSIGN --}}
                <div class="rounded-3xl bg-white border border-amber-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-amber-700 font-bold uppercase">
                                Belum Assign
                            </p>

                            <p class="text-3xl font-black text-amber-700 mt-2">
                                {{ number_format($belumAssign) }}
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                Waiting assignment Waspang
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hourglass-icon lucide-hourglass"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/>
                                <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        {{-- FILTER --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <form method="GET"
                  action="{{ route('admin.data-boq') }}"
                  class="grid grid-cols-1 lg:grid-cols-12 gap-3">

                <div class="lg:col-span-6">
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                        Search
                    </label>

                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Cari PID SAP, Nama LOP, STO, Branch, Mitra..."
                           class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm px-4">
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                        Package
                    </label>

                    <select name="package"
                            class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm px-4">
                        <option value="">Semua Package</option>

                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id_package }}" @selected($package == $pkg->id_package)>
                                {{ $pkg->package_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-end gap-2">
                    <button class="w-full h-12 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black">
                        Cari
                    </button>

                    @if(!empty($search) || !empty($package))
                        <a href="{{ route('admin.data-boq') }}"
                           class="h-12 px-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-black inline-flex items-center justify-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">

            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">
                        List BOQ per LOP
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Menampilkan data BOQ hasil import.
                    </p>
                </div>

                <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">
                    {{ $lops->total() }} data
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">PID SAP</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">ID IHLD</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Nama LOP</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Package</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Item</th>
                            <th class="px-5 py-4 text-right text-xs font-black text-slate-500 uppercase">Total Plan</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($lops as $lop)
                            @php
                                $items = $lop->boqItems ?? collect();

                                $materialCount = $items->filter(fn($item) => str_starts_with(strtoupper($item->designator ?? ''), 'M-'))->count();
                                $jasaCount = $items->filter(fn($item) => str_starts_with(strtoupper($item->designator ?? ''), 'J-'))->count();

                                $totalPlan = $items->sum('total_price');
                                $totalQty = $items->sum('quantity_plan');

                                $modalItems = $items->map(function ($item) {
                                    $designator = $item->designator ?? '-';
                                    $isMaterial = str_starts_with(strtoupper($designator), 'M-');

                                    return [
                                        'designator' => $designator,
                                        'type' => $isMaterial ? 'Material' : 'Jasa',
                                        'item_name' => $item->item_name ?? '-',
                                        'unit' => $item->unit ?? '-',
                                        'quantity_plan' => (float) ($item->quantity_plan ?? 0),
                                        'quantity_actual' => (float) ($item->quantity_actual ?? 0),
                                        'unit_price' => (float) ($item->unit_price ?? 0),
                                        'total_price' => (float) ($item->total_price ?? 0),
                                    ];
                                })->values();
                            @endphp

                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/70 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-black text-slate-900 dark:text-white">
                                        {{ $lop->project?->pid_sap ?? $lop->pid_sap ?? '-' }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $lop->project?->pid ?? '-' }}
                                    </p>
                                </td>

                                 <td class="px-5 py-4 text-center">
                                    <span class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-black">
                                        {{ $lop->id_ihld ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 min-w-[260px]">
                                    <p class="font-black text-slate-900 dark:text-white">
                                        {{ $lop->lop_name ?? '-' }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ $lop->branch ?? '-' }} · {{ $lop->sto ?? '-' }} · {{ $lop->mitra_name ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-black">
                                        {{ $lop->package?->package_name ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $items->count() }}</p>
                                    <p class="text-xs text-slate-500">item</p>
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <p class="font-black text-slate-900 dark:text-white">
                                        Rp {{ number_format($totalPlan, 0, ',', '.') }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Qty {{ number_format($totalQty, 0, ',', '.') }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <button type="button"
                                            @click='open({
                                                lopName: @json($lop->lop_name ?? '-'),
                                                pidSap: @json($lop->project?->pid_sap ?? $lop->pid_sap ?? '-'),
                                                pid: @json($lop->project?->pid ?? '-'),
                                                packageName: @json($lop->package?->package_name ?? '-'),
                                                branch: @json($lop->branch ?? '-'),
                                                sto: @json($lop->sto ?? '-'),
                                                mitra: @json($lop->mitra_name ?? '-'),
                                                totalItem: {{ $items->count() }},
                                                materialCount: {{ $materialCount }},
                                                jasaCount: {{ $jasaCount }},
                                                totalPlan: {{ (float) $totalPlan }},
                                                items: @json($modalItems),
                                            })'
                                            class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-blue-50 text-blue-700 text-xs font-black hover:bg-blue-100">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="w-16 h-16 rounded-3xl bg-slate-100 mx-auto flex items-center justify-center text-2xl mb-4">
                                        📊
                                    </div>

                                    <p class="text-sm font-black text-slate-700 dark:text-slate-200">
                                        Belum ada data BOQ
                                    </p>

                                    <p class="text-xs text-slate-500 mt-1">
                                        Silakan import BOQ terlebih dahulu.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if($lops->hasPages())
                <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $lops->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- GLOBAL MODAL DETAIL --}}
    <div
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    >
        <div
            @click.away="close()"
            class="bg-white dark:bg-slate-900 w-full max-w-6xl max-h-[90vh] overflow-hidden rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-2xl"
        >

            <div class="bg-gradient-to-br from-blue-700 to-indigo-700 px-6 py-5 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold opacity-80">
                            Detail BOQ
                        </p>

                        <h2 class="text-lg md:text-xl font-black leading-snug break-words" x-text="selected.lopName"></h2>

                        <p class="text-xs mt-1 opacity-90">
                            <span x-text="selected.pidSap"></span>
                            ·
                            <span x-text="selected.packageName"></span>
                        </p>
                    </div>

                    <button type="button"
                            @click="close()"
                            class="w-10 h-10 rounded-2xl bg-white/20 hover:bg-white/30 text-white text-xl">
                        ×
                    </button>
                </div>
            </div>

            <div class="p-5 overflow-y-auto max-h-[72vh] space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 p-4">
                        <p class="text-xs text-slate-500 font-bold">Total Item</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white" x-text="selected.totalItem"></p>
                    </div>

                    <div class="rounded-3xl bg-emerald-50 p-4">
                        <p class="text-xs text-emerald-700 font-bold">Material</p>
                        <p class="text-2xl font-black text-emerald-700" x-text="selected.materialCount"></p>
                    </div>

                    <div class="rounded-3xl bg-amber-50 p-4">
                        <p class="text-xs text-amber-700 font-bold">Jasa</p>
                        <p class="text-2xl font-black text-amber-700" x-text="selected.jasaCount"></p>
                    </div>

                    <div class="rounded-3xl bg-blue-50 p-4">
                        <p class="text-xs text-blue-700 font-bold">Total Plan</p>
                        <p class="text-lg font-black text-blue-700" x-text="formatRupiah(selected.totalPlan)"></p>
                    </div>
                </div>

                <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-slate-500 font-bold">PID</p>
                            <p class="font-black text-slate-900 dark:text-white" x-text="selected.pid"></p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500 font-bold">Branch</p>
                            <p class="font-black text-slate-900 dark:text-white" x-text="selected.branch"></p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500 font-bold">STO</p>
                            <p class="font-black text-slate-900 dark:text-white" x-text="selected.sto"></p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500 font-bold">Mitra</p>
                            <p class="font-black text-slate-900 dark:text-white" x-text="selected.mitra"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Designator</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Item Name</th>
                                    <th class="px-4 py-3 text-center text-xs font-black text-slate-500 uppercase">Unit</th>
                                    <th class="px-4 py-3 text-right text-xs font-black text-slate-500 uppercase">Qty Plan</th>
                                    <th class="px-4 py-3 text-right text-xs font-black text-slate-500 uppercase">Qty Actual</th>
                                    <th class="px-4 py-3 text-right text-xs font-black text-slate-500 uppercase">Unit Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-black text-slate-500 uppercase">Total</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <template x-for="item in selected.items" :key="item.designator">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/70">
                                        <td class="px-4 py-3 font-black text-slate-900 dark:text-white whitespace-nowrap" x-text="item.designator"></td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span
                                                class="px-2.5 py-1 rounded-full text-[11px] font-black"
                                                :class="item.type === 'Material'
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-amber-50 text-amber-700'"
                                                x-text="item.type">
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300 min-w-[320px]" x-text="item.item_name"></td>
                                        <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-300" x-text="item.unit"></td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white" x-text="formatNumber(item.quantity_plan)"></td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white" x-text="formatNumber(item.quantity_actual)"></td>
                                        <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap" x-text="formatRupiah(item.unit_price)"></td>
                                        <td class="px-4 py-3 text-right font-black text-blue-700 whitespace-nowrap" x-text="formatRupiah(item.total_price)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex justify-end">
                        <button type="button"
                                @click="close()"
                                class="h-11 px-5 rounded-2xl bg-slate-900 text-white text-sm font-black hover:bg-slate-800">
                            Tutup
                        </button>
                    </div>
                </div>                
            </div>

            

        </div>
    </div>

</div>

<script>
    function boqDetailModal() {
        return {
            show: false,

            selected: {
                lopName: '-',
                pidSap: '-',
                pid: '-',
                packageName: '-',
                branch: '-',
                sto: '-',
                mitra: '-',
                totalItem: 0,
                materialCount: 0,
                jasaCount: 0,
                totalPlan: 0,
                items: [],
            },

            open(data) {
                this.selected = data;
                this.show = true;
                document.body.classList.add('overflow-hidden');
            },

            close() {
                this.show = false;
                document.body.classList.remove('overflow-hidden');
            },

            formatRupiah(value) {
                value = Number(value || 0);

                return 'Rp ' + value.toLocaleString('id-ID', {
                    maximumFractionDigits: 0
                });
            },

            formatNumber(value) {
                value = Number(value || 0);

                return value.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }
        }
    }
</script>

@endsection