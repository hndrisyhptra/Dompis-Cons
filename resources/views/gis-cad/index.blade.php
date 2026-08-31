@extends('layouts.surveyor')

@section('title', 'Riwayat Export AutoCAD')

@section('content')

    {{-- Top Bar --}}
    <div class="sticky top-0 z-30 bg-indigo-700 rounded-b-[1.5rem] shadow-md px-4 py-3 flex items-center gap-3 safe-top">
        <a href="{{ route('surveyor.index') }}" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-black text-white truncate">Riwayat Export AutoCAD</p>
            <p class="text-[11px] text-indigo-100">GIS to CAD Generator</p>
        </div>
        <a href="{{ route('gis-cad.create') }}" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0">
            <i class="fa-solid fa-plus text-sm"></i>
        </a>
    </div>

    <div class="px-4 pt-4 space-y-3">
        @forelse($exports as $export)
            @php
                $badge = match($export->status) {
                    'draft' => ['bg-amber-100 text-amber-700 border-amber-200', '📝 Draft'],
                    'queued' => ['bg-slate-100 text-slate-600 border-slate-200', '⏳ Antre'],
                    'processing' => ['bg-blue-100 text-blue-700 border-blue-200 animate-pulse', '⚙️ Diproses'],
                    'completed' => ['bg-emerald-100 text-emerald-700 border-emerald-200', '✅ Selesai'],
                    'failed' => ['bg-red-100 text-red-700 border-red-200', '❌ Gagal'],
                    default => ['bg-slate-100 text-slate-600 border-slate-200', $export->status],
                };
            @endphp
            <a href="{{ $export->isDraft() ? route('gis-cad.review', $export->uuid) : route('gis-cad.show', $export->uuid) }}"
               class="block bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-black text-slate-900 truncate">
                            {{ $export->original_file_name ?? ($export->survey?->displayTitle() ?? 'Export #' . $export->id_gis_cad_export) }}
                        </p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            {{ $export->source_type === 'kml_upload' ? 'Upload KML/KMZ' : 'Data Survey Existing' }}
                            &middot; {{ $export->points_count }} titik, {{ $export->polylines_count }} rute
                        </p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-lg text-[10px] font-bold border whitespace-nowrap {{ $badge[0] }}">{{ $badge[1] }}</span>
                </div>
                <div class="flex items-center gap-4 mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 font-semibold">
                    <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock"></i> {{ $export->updated_at->diffForHumans() }}</span>
                    @if($export->requester)
                        <span class="flex items-center gap-1.5 ml-auto"><i class="fa-regular fa-user"></i> {{ $export->requester->name }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="text-center py-14 bg-white rounded-2xl border border-dashed border-slate-200">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-400 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-drafting-compass text-2xl"></i>
                </div>
                <p class="font-black text-slate-800">Belum Ada Export</p>
                <p class="text-xs text-slate-500 mt-1 px-8">Upload KML/KMZ atau pakai data survey existing untuk generate DXF pertamamu.</p>
            </div>
        @endforelse

        @if($exports->hasPages())
            <div class="pb-4">{{ $exports->links() }}</div>
        @endif
    </div>

@endsection

@section('bottom-nav')
    @include('surveyor.partials.bottom-nav', ['active' => ''])
@endsection
