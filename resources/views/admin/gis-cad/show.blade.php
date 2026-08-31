@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.gis-cad.index') }}"
           class="w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ $export->original_file_name ?? ($export->survey?->displayTitle() ?? 'Export #' . $export->id_gis_cad_export) }}
            </h1>
            <p class="text-sm text-gray-500">GIS to CAD Generator &middot; Status Export</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

        {{-- Status Card --}}
        <div class="lg:col-span-2 rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
            <div class="flex items-center gap-4">
                <div id="statusIcon" class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 text-2xl"></div>
                <div>
                    <p id="statusLabel" class="font-black text-gray-900 dark:text-white text-lg"></p>
                    <p id="statusStage" class="text-sm text-gray-500 mt-0.5"></p>
                </div>
            </div>

            <div id="statusErrorBox" class="hidden mt-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 rounded-xl p-4">
                <p class="text-xs font-bold text-red-700 dark:text-red-400 mb-1">Detail Error:</p>
                <p id="statusErrorText" class="text-xs text-red-600 dark:text-red-400 font-mono break-words"></p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-5">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-4">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Titik</p>
                    <p id="statusPoints" class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ $export->points_count }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-4">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Jalur Kabel</p>
                    <p id="statusPolylines" class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ $export->polylines_count }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-4">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Zona UTM</p>
                    <p id="statusUtm" class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ $export->utm_zone ?? '-' }}</p>
                </div>
            </div>

            @if($export->isFailed())
                <form method="POST" action="{{ route('admin.gis-cad.confirm', $export->uuid) }}" class="mt-5">
                    @csrf
                    <input type="hidden" name="template" value="{{ $export->template }}">
                    <button type="submit" class="h-11 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black shadow-sm transition">
                        Coba Generate Ulang
                    </button>
                </form>
            @endif
        </div>

        {{-- Info & Download --}}
        <div class="space-y-5">
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                <p class="text-sm font-black text-gray-900 dark:text-white mb-3">Informasi</p>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Sumber</dt>
                        <dd class="font-semibold text-gray-800 dark:text-gray-200 text-right">{{ $export->source_type === 'kml_upload' ? 'Upload KML/KMZ' : 'Data Survey Existing' }}</dd>
                    </div>
                    @if($export->survey)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Survey</dt>
                            <dd class="font-semibold text-gray-800 dark:text-gray-200 text-right">{{ $export->survey->displayTitle() }}</dd>
                        </div>
                    @endif
                    @if($export->project)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Project</dt>
                            <dd class="font-semibold text-gray-800 dark:text-gray-200 text-right">{{ $export->project->project_name }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Template</dt>
                        <dd class="font-semibold text-gray-800 dark:text-gray-200 text-right">{{ $export->template === 'custom' ? 'Custom Layer' : 'Standard FTTx' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Diminta Oleh</dt>
                        <dd class="font-semibold text-gray-800 dark:text-gray-200 text-right">{{ $export->requester->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Update Terakhir</dt>
                        <dd class="font-semibold text-gray-800 dark:text-gray-200 text-right">{{ $export->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>

            <div id="downloadBox" class="hidden space-y-2">
                <a href="{{ route('admin.gis-cad.download.dxf', $export->uuid) }}"
                   class="flex items-center justify-center gap-2 h-12 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-sm font-black shadow-lg shadow-emerald-600/30 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    Download DXF
                </a>
                @if($export->bom_path)
                    <a href="{{ route('admin.gis-cad.download.bom', $export->uuid) }}"
                       class="flex items-center justify-center gap-2 h-11 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 text-gray-700 dark:text-gray-300 text-xs font-black transition hover:bg-gray-50 dark:hover:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/></svg>
                        Download BOM (Excel)
                    </a>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const statusUrl = @json(route('admin.gis-cad.status', $export->uuid));

    const cfg = {
        draft: { icon: 'pen', color: 'bg-amber-100 text-amber-600', label: 'Draft' },
        queued: { icon: 'hourglass', color: 'bg-slate-100 text-slate-500', label: 'Menunggu Antrean' },
        processing: { icon: 'gear', color: 'bg-blue-100 text-blue-600', label: 'Sedang Diproses' },
        completed: { icon: 'check', color: 'bg-emerald-100 text-emerald-600', label: 'Selesai' },
        failed: { icon: 'cross', color: 'bg-red-100 text-red-600', label: 'Gagal' },
    };

    const icons = {
        pen: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
        hourglass: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg>',
        gear: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin" style="animation-duration:2.5s"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2Z"/><circle cx="12" cy="12" r="3"/></svg>',
        check: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
        cross: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
    };

    let pollTimer = null;

    function render(data) {
        const c = cfg[data.status] || cfg.queued;

        const iconEl = document.getElementById('statusIcon');
        iconEl.className = 'w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 text-2xl ' + c.color;
        iconEl.innerHTML = icons[c.icon];

        document.getElementById('statusLabel').textContent = c.label;
        document.getElementById('statusStage').textContent = data.stage || '';
        document.getElementById('statusPoints').textContent = data.points_count;
        document.getElementById('statusPolylines').textContent = data.polylines_count;
        document.getElementById('statusUtm').textContent = data.utm_zone || '-';

        const errBox = document.getElementById('statusErrorBox');
        if (data.status === 'failed' && data.error_message) {
            errBox.classList.remove('hidden');
            document.getElementById('statusErrorText').textContent = data.error_message;
        } else {
            errBox.classList.add('hidden');
        }

        const downloadBox = document.getElementById('downloadBox');
        if (data.status === 'completed' && data.has_dxf) {
            downloadBox.classList.remove('hidden');
        } else {
            downloadBox.classList.add('hidden');
        }

        if (data.status === 'completed' || data.status === 'failed') {
            if (pollTimer) clearInterval(pollTimer);
        }
    }

    function poll() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.json())
            .then((res) => { if (res.success) render(res.data); })
            .catch(() => {});
    }

    poll();
    pollTimer = setInterval(poll, 3000);
})();
</script>
@endpush
