@extends('layouts.admin')

@php
    $typeLabels = [
        'tiang' => 'Tiang',
        'odp' => 'ODP',
        'odc' => 'ODC',
        'otb' => 'OTB',
        'jc' => 'JC',
        'ending_site' => 'Ending Site',
        'unknown' => 'Belum Dikenali',
    ];
    $lowConfidenceCount = collect($dataset['points'] ?? [])->where('confidence', 'low')->count();
@endphp

@section('content')

<div class="space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.gis-cad.index') }}"
           class="w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Review Sebelum Generate</h1>
            <p class="text-sm text-gray-500">
                {{ $export->original_file_name ?? ($export->survey?->displayTitle() ?? 'Export #' . $export->id_gis_cad_export) }}
                &middot; {{ count($dataset['points'] ?? []) }} titik, {{ count($dataset['polylines'] ?? []) }} rute
            </p>
        </div>
    </div>

    @if($lowConfidenceCount > 0)
        <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 rounded-2xl p-4 text-sm text-amber-800 dark:text-amber-300 font-semibold flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            <span>{{ $lowConfidenceCount }} titik tidak bisa ditebak otomatis tipenya (nama Placemark/Folder tidak mengandung kode standar). Mohon pilih tipenya manual di tabel di bawah sebelum generate DXF.</span>
        </div>
    @endif

    @if(!empty($dataset['skipped']))
        <details class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 text-sm text-gray-600 dark:text-gray-400">
            <summary class="font-black cursor-pointer text-gray-800 dark:text-gray-200">{{ count($dataset['skipped']) }} item dilewati (geometry tidak didukung)</summary>
            <ul class="mt-2 space-y-1 text-xs">
                @foreach($dataset['skipped'] as $s)
                    <li>&bull; {{ $s['name'] ?: '(tanpa nama)' }} - <span class="font-mono">{{ $s['reason'] }}</span></li>
                @endforeach
            </ul>
        </details>
    @endif

    <form method="POST" id="reviewForm">
        @csrf

        {{-- Template --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm mb-5">
            <p class="text-sm font-black text-gray-900 dark:text-white mb-3">Template Export</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-xl">
                <label class="flex items-center gap-3 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 cursor-pointer has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50 dark:has-[:checked]:bg-indigo-950/40 transition">
                    <input type="radio" name="template" value="standard_fttx" checked class="accent-indigo-600">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Standard FTTx</span>
                </label>
                <label class="flex items-center gap-3 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 cursor-pointer has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50 dark:has-[:checked]:bg-indigo-950/40 transition">
                    <input type="radio" name="template" value="custom" class="accent-indigo-600">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Custom Layer</span>
                </label>
            </div>
            <p class="text-xs text-gray-400 mt-2">Custom layer saat ini masih memakai mapping layer standar yang sama - override nama layer custom akan menyusul.</p>
        </div>

        {{-- Points --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 shadow-sm mb-5 overflow-hidden">
            <div class="p-5 pb-3">
                <p class="text-sm font-black text-gray-900 dark:text-white">Titik ({{ count($dataset['points'] ?? []) }})</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Nama / Label</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Tipe</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Koordinat</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Folder Asal</th>
                            <th class="px-4 py-2.5 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($dataset['points'] ?? [] as $i => $point)
                            <tr class="{{ $point['confidence'] === 'low' ? 'bg-amber-50/60 dark:bg-amber-950/20' : '' }}">
                                <input type="hidden" name="overrides[{{ $i }}][ref]" value="{{ $point['ref'] }}">
                                <td class="px-4 py-2.5 min-w-[180px]">
                                    <input type="text" name="overrides[{{ $i }}][name]" value="{{ $point['name'] }}"
                                           placeholder="Nama / Label"
                                           class="w-full h-9 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-xs font-bold">
                                </td>
                                <td class="px-4 py-2.5 min-w-[160px]">
                                    <select name="overrides[{{ $i }}][type]" required
                                            class="w-full h-9 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-xs font-semibold {{ $point['confidence'] === 'low' ? 'ring-2 ring-amber-300' : '' }}">
                                        <option value="" {{ $point['type'] === 'unknown' ? 'selected' : '' }} disabled>-- Pilih Tipe --</option>
                                        @foreach(['tiang','odp','odc','otb','jc','ending_site'] as $t)
                                            <option value="{{ $t }}" {{ $point['type'] === $t ? 'selected' : '' }}>{{ $typeLabels[$t] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-gray-500 font-mono whitespace-nowrap">
                                    {{ number_format($point['lat'], 6) }}, {{ number_format($point['lng'], 6) }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">
                                    {{ $point['source_folder'] ?: '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <input type="checkbox" name="remove[]" value="{{ $point['ref'] }}" class="accent-red-600 w-4 h-4">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Polylines --}}
        @if(!empty($dataset['polylines']))
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 shadow-sm mb-5 overflow-hidden">
                <div class="p-5 pb-3">
                    <p class="text-sm font-black text-gray-900 dark:text-white">Jalur Kabel ({{ count($dataset['polylines']) }})</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Nama Rute</th>
                                <th class="px-4 py-2.5 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Jumlah Titik Jalur</th>
                                <th class="px-4 py-2.5 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Hapus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($dataset['polylines'] as $line)
                                <tr>
                                    <td class="px-4 py-2.5 font-bold text-gray-800 dark:text-gray-200 text-xs">{{ $line['name'] }}</td>
                                    <td class="px-4 py-2.5 text-center text-xs text-gray-500">{{ count($line['coordinates']) }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <input type="checkbox" name="remove[]" value="{{ $line['ref'] }}" class="accent-red-600 w-4 h-4">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="flex items-center justify-end gap-3">
            <button type="submit" formmethod="POST" formaction="{{ route('admin.gis-cad.review.update', $export->uuid) }}"
                    class="h-11 px-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-black shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                Simpan Koreksi
            </button>
            <button type="submit" formmethod="POST" formaction="{{ route('admin.gis-cad.confirm', $export->uuid) }}"
                    class="h-11 px-6 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-700 text-white text-sm font-black shadow-lg shadow-indigo-600/30 hover:opacity-90 transition">
                Generate DXF
            </button>
        </div>
    </form>

</div>
@endsection
