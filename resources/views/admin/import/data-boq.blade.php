@extends('layouts.admin')

@section('content')

<div
    x-data="boqDetailModal()"
    class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6"
>
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">BOQ Monitoring</p>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">Data BOQ</h1>
                    <p class="text-sm text-slate-500 mt-2 max-w-2xl">
                        Monitoring nilai BOQ per LOP berdasarkan harga package terbaru dari designator package price.
                    </p>
                </div>

                <a href="{{ route('admin.import.boq') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 13v8"/>
                        <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/>
                        <path d="m8 17 4-4 4 4"/>
                    </svg>
                    <span>Bulk Import BOQ</span>
                </a>
            </div>

            {{-- SUMMARY --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
                <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <p class="text-xs text-slate-500 font-bold uppercase">LOP Dengan BOQ</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white mt-2">{{ number_format($totalLopBoq ?? 0) }}</p>
                    <p class="text-xs text-slate-500 mt-1">LOP memiliki item BOQ</p>
                </div>

                <div class="rounded-3xl bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-900 p-5 shadow-sm">
                    <p class="text-xs text-indigo-700 font-bold uppercase">Total Nilai BOQ</p>
                    <p class="text-xl md:text-2xl font-black text-indigo-700 mt-2">Rp {{ number_format($totalBoqValue ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 mt-1">Total jasa + material</p>
                </div>

                <div class="rounded-3xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-900 p-5 shadow-sm">
                    <p class="text-xs text-amber-700 font-bold uppercase">Total Jasa</p>
                    <p class="text-xl md:text-2xl font-black text-amber-700 mt-2">Rp {{ number_format($totalJasaValue ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 mt-1">Designator type jasa</p>
                </div>

                <div class="rounded-3xl bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-900 p-5 shadow-sm">
                    <p class="text-xs text-emerald-700 font-bold uppercase">Total Material</p>
                    <p class="text-xl md:text-2xl font-black text-emerald-700 mt-2">Rp {{ number_format($totalMaterialValue ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 mt-1">Designator type material</p>
                </div>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.data-boq') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                <div class="lg:col-span-6">
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Cari PID SAP, Nama LOP, ID IHLD, STO, Branch, Mitra..."
                           class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm px-4">
                </div>

                <div class="lg:col-span-4">
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Package</label>
                    <select name="package"
                            class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm px-4">
                        <option value="">Semua Package</option>
                        @foreach($packages ?? [] as $pkg)
                            <option value="{{ $pkg->id_package }}"
                                @selected((string) ($package ?? '') === (string) $pkg->id_package)>
                                {{ $pkg->package_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-end gap-2">
                    <button type="submit"
                            class="w-full h-12 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black">
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
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">List BOQ per LOP</h2>
                    <p class="text-xs text-slate-500 mt-1">Nilai dihitung ulang dari harga package terbaru × quantity plan.</p>
                </div>

                <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">
                    {{ number_format($lops->total()) }} data
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Nama LOP</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">ID IHLD</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Package</th>
                            <th class="px-5 py-4 text-right text-xs font-black text-slate-500 uppercase">Total Jasa</th>
                            <th class="px-5 py-4 text-right text-xs font-black text-slate-500 uppercase">Total Material</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($lops as $lop)
                            @php
                                $items = $boqItemsByLop->get($lop->id_lop, collect());

                                $materialCount = $items
                                    ->filter(fn ($item) => strtolower($item->type ?? '') === 'material')
                                    ->count();

                                $jasaCount = $items
                                    ->filter(fn ($item) => strtolower($item->type ?? '') === 'jasa')
                                    ->count();

                                $totalJasa = (float) ($lop->total_jasa ?? 0);
                                $totalMaterial = (float) ($lop->total_material ?? 0);
                                $totalPlan = $totalJasa + $totalMaterial;

                                $modalItems = $items->map(function ($item) {
                                    return [
                                        'designator' => $item->designator ?? '-',
                                        'type' => ucfirst(strtolower($item->type ?? '-')),
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
                                <td class="px-5 py-4 min-w-[280px]">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $lop->lop_name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ $lop->branch ?? '-' }} · {{ $lop->sto ?? '-' }}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">
                                        PID SAP: {{ $lop->project_pid_sap ?? $lop->pid_sap ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">
                                        {{ $lop->id_ihld ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-black">
                                        {{ $lop->package_name ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <p class="font-black text-amber-700">Rp {{ number_format($totalJasa, 0, ',', '.') }}</p>
                                    <p class="text-[11px] text-slate-500 mt-1">{{ number_format($jasaCount) }} item jasa</p>
                                </td>

                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <p class="font-black text-emerald-700">Rp {{ number_format($totalMaterial, 0, ',', '.') }}</p>
                                    <p class="text-[11px] text-slate-500 mt-1">{{ number_format($materialCount) }} item material</p>
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <button type="button"
                                            @click='open({
                                                lopName: @json($lop->lop_name ?? '-'),
                                                idIhld: @json($lop->id_ihld ?? '-'),
                                                pidSap: @json($lop->project_pid_sap ?? $lop->pid_sap ?? '-'),
                                                pid: @json($lop->project_pid ?? '-'),
                                                packageName: @json($lop->package_name ?? '-'),
                                                branch: @json($lop->branch ?? '-'),
                                                sto: @json($lop->sto ?? '-'),
                                                mitra: @json($lop->mitra_name ?? '-'),
                                                totalItem: {{ $items->count() }},
                                                materialCount: {{ $materialCount }},
                                                jasaCount: {{ $jasaCount }},
                                                totalJasa: {{ (float) $totalJasa }},
                                                totalMaterial: {{ (float) $totalMaterial }},
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
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <p class="text-sm font-black text-slate-700 dark:text-slate-200">Belum ada data BOQ</p>
                                    <p class="text-xs text-slate-500 mt-1">Silakan import BOQ terlebih dahulu atau ubah filter pencarian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lops->hasPages())
                <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $lops->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL DETAIL --}}
    <div x-show="show"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="close()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">

        <div @click.outside="close()"
             class="bg-white dark:bg-slate-900 w-full max-w-6xl max-h-[90vh] overflow-hidden rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-2xl">

            <div class="bg-gradient-to-br from-blue-700 to-indigo-700 px-6 py-5 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold opacity-80">Detail BOQ</p>
                        <h2 class="text-lg md:text-xl font-black leading-snug break-words" x-text="selected.lopName"></h2>
                        <p class="text-xs mt-1 opacity-90">
                            <span x-text="selected.idIhld"></span> ·
                            <span x-text="selected.pidSap"></span> ·
                            <span x-text="selected.packageName"></span>
                        </p>
                    </div>

                    <button type="button"
                            @click="close()"
                            class="w-10 h-10 rounded-2xl bg-white/20 hover:bg-white/30 text-white text-xl shrink-0">
                        ×
                    </button>
                </div>
            </div>

            <div class="p-5 overflow-y-auto max-h-[72vh] space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 p-4 border border-slate-200 dark:border-slate-800">
                        <p class="text-xs text-slate-500 font-bold">Total Item</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="selected.totalItem"></p>
                    </div>

                    <div class="rounded-3xl bg-amber-50 p-4 border border-amber-100">
                        <p class="text-xs text-amber-700 font-bold">Total Jasa</p>
                        <p class="text-lg font-black text-amber-700 mt-1" x-text="formatRupiah(selected.totalJasa)"></p>
                        <p class="text-[11px] text-amber-700/70 mt-1"><span x-text="selected.jasaCount"></span> item jasa</p>
                    </div>

                    <div class="rounded-3xl bg-emerald-50 p-4 border border-emerald-100">
                        <p class="text-xs text-emerald-700 font-bold">Total Material</p>
                        <p class="text-lg font-black text-emerald-700 mt-1" x-text="formatRupiah(selected.totalMaterial)"></p>
                        <p class="text-[11px] text-emerald-700/70 mt-1"><span x-text="selected.materialCount"></span> item material</p>
                    </div>

                    <div class="rounded-3xl bg-blue-50 p-4 border border-blue-100">
                        <p class="text-xs text-blue-700 font-bold">Total BOQ</p>
                        <p class="text-lg font-black text-blue-700 mt-1" x-text="formatRupiah(selected.totalPlan)"></p>
                    </div>
                </div>

                <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-slate-500 font-bold">ID IHLD</p>
                            <p class="font-black text-slate-900 dark:text-white mt-1" x-text="selected.idIhld"></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-bold">PID</p>
                            <p class="font-black text-slate-900 dark:text-white mt-1" x-text="selected.pid"></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-bold">Branch</p>
                            <p class="font-black text-slate-900 dark:text-white mt-1" x-text="selected.branch"></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-bold">STO</p>
                            <p class="font-black text-slate-900 dark:text-white mt-1" x-text="selected.sto"></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-bold">Mitra</p>
                            <p class="font-black text-slate-900 dark:text-white mt-1" x-text="selected.mitra"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">Detail Designator</h3>
                        <p class="text-xs text-slate-500 mt-1">Harga unit menggunakan designator package price terbaru.</p>
                    </div>

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
                                <template x-for="(item, index) in selected.items" :key="`${item.designator}-${index}`">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/70">
                                        <td class="px-4 py-3 font-black text-slate-900 dark:text-white whitespace-nowrap" x-text="item.designator"></td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-black"
                                                  :class="String(item.type).toLowerCase() === 'material'
                                                      ? 'bg-emerald-50 text-emerald-700'
                                                      : 'bg-amber-50 text-amber-700'"
                                                  x-text="item.type"></span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300 min-w-[320px]" x-text="item.item_name"></td>
                                        <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-300" x-text="item.unit"></td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white" x-text="formatNumber(item.quantity_plan)"></td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white" x-text="formatNumber(item.quantity_actual)"></td>
                                        <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap" x-text="formatRupiah(item.unit_price)"></td>
                                        <td class="px-4 py-3 text-right font-black text-blue-700 whitespace-nowrap" x-text="formatRupiah(item.total_price)"></td>
                                    </tr>
                                </template>

                                <template x-if="selected.items.length === 0">
                                    <tr>
                                        <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada detail item BOQ.</td>
                                    </tr>
                                </template>
                            </tbody>

                            <tfoot class="bg-slate-50 dark:bg-slate-800/70">
                                <tr>
                                    <td colspan="7" class="px-4 py-3 text-right text-xs font-black text-slate-500 uppercase">Total BOQ</td>
                                    <td class="px-4 py-3 text-right font-black text-blue-700 whitespace-nowrap" x-text="formatRupiah(selected.totalPlan)"></td>
                                </tr>
                            </tfoot>
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
                idIhld: '-',
                pidSap: '-',
                pid: '-',
                packageName: '-',
                branch: '-',
                sto: '-',
                mitra: '-',
                totalItem: 0,
                materialCount: 0,
                jasaCount: 0,
                totalJasa: 0,
                totalMaterial: 0,
                totalPlan: 0,
                items: [],
            },

            open(data) {
                this.selected = {
                    ...this.selected,
                    ...data,
                    items: Array.isArray(data.items) ? data.items : [],
                };

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
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                });
            },

            formatNumber(value) {
                value = Number(value || 0);

                return value.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                });
            },
        }
    }
</script>

@endsection