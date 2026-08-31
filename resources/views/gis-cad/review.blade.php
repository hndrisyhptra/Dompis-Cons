@extends('layouts.surveyor')

@section('title', 'Review Dataset')

@php
    $typeLabels = [
        'tiang' => 'Tiang',
        'odp' => 'ODP',
        'odc' => 'ODC',
        'otb' => 'OTB',
        'jc' => 'JC',
        'ending_site' => 'Ending Site',
        'unknown' => '❓ Belum Dikenali',
    ];
    $lowConfidenceCount = collect($dataset['points'] ?? [])->where('confidence', 'low')->count();
@endphp

@section('content')

    {{-- Top Bar --}}
    <div class="sticky top-0 z-30 bg-indigo-700 rounded-b-[1.5rem] shadow-md px-4 py-3 flex items-center gap-3 safe-top">
        <a href="{{ route('gis-cad.create') }}" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-black text-white truncate">Review Sebelum Generate</p>
            <p class="text-[11px] text-indigo-100">{{ count($dataset['points'] ?? []) }} titik &middot; {{ count($dataset['polylines'] ?? []) }} rute</p>
        </div>
    </div>

    <div class="px-4 pt-4 space-y-4 pb-4">

        @if($lowConfidenceCount > 0)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 text-xs text-amber-800 font-semibold flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span>{{ $lowConfidenceCount }} titik tidak bisa ditebak otomatis tipenya (nama Placemark/Folder tidak mengandung kode standar). Mohon pilih tipenya manual di bawah sebelum generate DXF.</span>
            </div>
        @endif

        @if(!empty($dataset['skipped']))
            <details class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 text-xs text-slate-600">
                <summary class="font-black cursor-pointer">{{ count($dataset['skipped']) }} item dilewati (geometry tidak didukung)</summary>
                <ul class="mt-2 space-y-1">
                    @foreach($dataset['skipped'] as $s)
                        <li>&bull; {{ $s['name'] ?: '(tanpa nama)' }} - <span class="font-mono">{{ $s['reason'] }}</span></li>
                    @endforeach
                </ul>
            </details>
        @endif

        <form method="POST" id="reviewForm">
            @csrf

            {{-- Template --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-4">
                <p class="text-sm font-black text-slate-900 mb-2">Template Export</p>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 border border-slate-200 rounded-xl px-3 py-2.5 cursor-pointer has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="template" value="standard_fttx" checked class="accent-indigo-600">
                        <span class="text-xs font-bold text-slate-700">Standard FTTx</span>
                    </label>
                    <label class="flex items-center gap-2 border border-slate-200 rounded-xl px-3 py-2.5 cursor-pointer has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="template" value="custom" class="accent-indigo-600">
                        <span class="text-xs font-bold text-slate-700">Custom Layer</span>
                    </label>
                </div>
                <p class="text-[10px] text-slate-400 mt-2">Custom layer saat ini masih memakai mapping layer standar yang sama - override nama layer custom akan menyusul.</p>
            </div>

            {{-- Points --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-4">
                <p class="text-sm font-black text-slate-900 mb-3">Titik ({{ count($dataset['points'] ?? []) }})</p>
                <div class="space-y-2">
                    @foreach($dataset['points'] ?? [] as $i => $point)
                        <div class="border rounded-xl p-3 {{ $point['confidence'] === 'low' ? 'border-amber-300 bg-amber-50/40' : 'border-slate-100' }}">
                            <input type="hidden" name="overrides[{{ $i }}][ref]" value="{{ $point['ref'] }}">
                            <div class="flex items-start gap-2">
                                <div class="flex-1 min-w-0 space-y-1.5">
                                    <input type="text" name="overrides[{{ $i }}][name]" value="{{ $point['name'] }}"
                                           placeholder="Nama / Label"
                                           class="w-full h-9 rounded-lg border-slate-200 text-xs font-bold">
                                    <select name="overrides[{{ $i }}][type]" required
                                            class="w-full h-9 rounded-lg border-slate-200 text-xs font-semibold {{ $point['confidence'] === 'low' ? 'ring-2 ring-amber-300' : '' }}">
                                        <option value="" {{ $point['type'] === 'unknown' ? 'selected' : '' }} disabled>-- Pilih Tipe --</option>
                                        @foreach(['tiang','odp','odc','otb','jc','ending_site'] as $t)
                                            <option value="{{ $t }}" {{ $point['type'] === $t ? 'selected' : '' }}>{{ $typeLabels[$t] }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400">
                                        {{ number_format($point['lat'], 6) }}, {{ number_format($point['lng'], 6) }}
                                        @if($point['source_folder']) &middot; folder: {{ $point['source_folder'] }} @endif
                                    </p>
                                </div>
                                <label class="shrink-0 flex flex-col items-center gap-1 pt-1">
                                    <input type="checkbox" name="remove[]" value="{{ $point['ref'] }}" class="accent-red-600">
                                    <span class="text-[9px] text-slate-400 font-bold">Hapus</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Polylines --}}
            @if(!empty($dataset['polylines']))
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-4">
                    <p class="text-sm font-black text-slate-900 mb-3">Jalur Kabel ({{ count($dataset['polylines']) }})</p>
                    <div class="space-y-2">
                        @foreach($dataset['polylines'] as $line)
                            <div class="border border-slate-100 rounded-xl p-3 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-black text-slate-800 truncate">{{ $line['name'] }}</p>
                                    <p class="text-[10px] text-slate-400">{{ count($line['coordinates']) }} titik jalur</p>
                                </div>
                                <label class="shrink-0 flex flex-col items-center gap-1">
                                    <input type="checkbox" name="remove[]" value="{{ $line['ref'] }}" class="accent-red-600">
                                    <span class="text-[9px] text-slate-400 font-bold">Hapus</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-2 sticky bottom-4">
                <button type="submit" formmethod="POST" formaction="{{ route('gis-cad.review.update', $export->uuid) }}"
                        class="h-12 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-black shadow-sm">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Koreksi
                </button>
                <button type="submit" formmethod="POST" formaction="{{ route('gis-cad.confirm', $export->uuid) }}"
                        class="h-12 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-700 text-white text-xs font-black shadow-lg shadow-indigo-600/30">
                    <i class="fa-solid fa-bolt mr-1"></i> Generate DXF
                </button>
            </div>
        </form>
    </div>

@endsection

@section('bottom-nav')
@endsection
