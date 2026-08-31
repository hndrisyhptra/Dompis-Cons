@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Generate / Export CAD
            </h1>
            <p class="text-sm text-gray-500">
                Konversi hasil Survey Lapangan (KML/KMZ) menjadi file DXF AutoCAD siap desain FTTx
            </p>
        </div>

        <a href="{{ route('admin.gis-cad.create') }}"
           class="h-11 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm shadow-indigo-600/20 inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Export Baru
        </a>
    </div>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-500 font-bold uppercase">Total Export</p>
                    <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $stats['total'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Seluruh riwayat export CAD</p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-600">
                        <path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M9 15h6"/><path d="M9 11h1"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-500 font-bold uppercase">Diproses</p>
                    <p class="text-3xl font-black text-blue-600 mt-2">{{ $stats['processing'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Draft, antre &amp; sedang digenerate</p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600">
                        <path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-500 font-bold uppercase">Selesai</p>
                    <p class="text-3xl font-black text-emerald-600 mt-2">{{ $stats['completed'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">DXF siap diunduh</p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-500 font-bold uppercase">Gagal</p>
                    <p class="text-3xl font-black text-red-600 mt-2">{{ $stats['failed'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Perlu dicoba ulang</p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600">
                        <circle cx="12" cy="12" r="9"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- SEARCH & FILTER --}}
    <form method="GET" action="{{ route('admin.gis-cad.index') }}"
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        @if(request('status_filter'))
            <input type="hidden" name="status_filter" value="{{ request('status_filter') }}">
        @endif

        <div class="flex flex-col sm:flex-row gap-3">
            <input type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama file, judul survey, project, atau nama peminta..."
                class="flex-1 h-11 rounded-xl border-gray-300 text-sm">

            <div class="flex gap-2">
                <button class="h-11 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold">
                    Cari
                </button>

                @if (request('search'))
                    <a href="{{ route('admin.gis-cad.index', ['status_filter' => request('status_filter')]) }}"
                    class="h-11 px-5 rounded-xl border border-gray-300 text-sm font-bold flex items-center">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('admin.gis-cad.index', ['search' => request('search')]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold {{ !request('status_filter') ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600' }}">Semua ({{ $stats['total'] }})</a>
        <a href="{{ route('admin.gis-cad.index', ['status_filter' => 'draft', 'search' => request('search')]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'draft' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600' }}">Draft</a>
        <a href="{{ route('admin.gis-cad.index', ['status_filter' => 'processing', 'search' => request('search')]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }}">Diproses</a>
        <a href="{{ route('admin.gis-cad.index', ['status_filter' => 'completed', 'search' => request('search')]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'completed' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600' }}">Selesai ({{ $stats['completed'] }})</a>
        <a href="{{ route('admin.gis-cad.index', ['status_filter' => 'failed', 'search' => request('search')]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'failed' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600' }}">Gagal ({{ $stats['failed'] }})</a>
    </div>

    {{-- TABLE --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Sumber / File</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Survey / Project</th>
                        <th class="px-4 py-3 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Titik</th>
                        <th class="px-4 py-3 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Rute</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Diminta Oleh</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Update Terakhir</th>
                        <th class="px-4 py-3 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($exports as $export)
                        @php
                            $badge = match($export->status) {
                                'draft' => ['bg-amber-100 text-amber-700', 'bg-amber-500', 'Draft'],
                                'queued' => ['bg-slate-100 text-slate-600', 'bg-slate-400', 'Antre'],
                                'processing' => ['bg-blue-100 text-blue-700', 'bg-blue-500', 'Diproses'],
                                'completed' => ['bg-emerald-100 text-emerald-700', 'bg-emerald-500', 'Selesai'],
                                'failed' => ['bg-red-100 text-red-700', 'bg-red-500', 'Gagal'],
                                default => ['bg-slate-100 text-slate-600', 'bg-slate-400', $export->status],
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 min-w-[220px]">
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ $export->original_file_name ?? ($export->survey?->displayTitle() ?? 'Export #' . $export->id_gis_cad_export) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $export->source_type === 'kml_upload' ? 'Upload KML/KMZ' : 'Data Survey Existing' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                <p class="font-semibold">{{ $export->survey?->displayTitle() ?? '-' }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $export->project?->project_name ?? '-' }}
                                    @if($export->project?->pid) &middot; PID: {{ $export->project->pid }} @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-indigo-600">
                                {{ $export->points_count }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-slate-700">
                                {{ $export->polylines_count }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-lg {{ $badge[0] }} text-xs font-bold inline-flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $badge[1] }}"></span> {{ $badge[2] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-semibold">
                                {{ $export->requester->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $export->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ $export->isDraft() ? route('admin.gis-cad.review', $export->uuid) : route('admin.gis-cad.show', $export->uuid) }}"
                                       class="h-9 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition inline-flex items-center gap-1.5">
                                        {{ $export->isDraft() ? 'Review' : 'Lihat' }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.gis-cad.destroy', $export->uuid) }}"
                                          onsubmit="return confirm('Hapus riwayat export ini beserta file terkait?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="h-9 w-9 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-400 hover:text-red-600 hover:border-red-200 transition inline-flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-400 flex items-center justify-center mx-auto mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M9 15h6"/><path d="M9 11h1"/></svg>
                                </div>
                                <p class="font-black text-gray-900 text-lg">Belum Ada Export CAD</p>
                                <p class="text-gray-500 text-sm mt-1">Upload KML/KMZ atau pakai data survey existing untuk generate DXF pertama.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($exports->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                {{ $exports->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
