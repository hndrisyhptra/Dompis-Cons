@extends('layouts.surveyor')

@section('title', 'Status Export')

@section('content')

    {{-- Top Bar --}}
    <div class="sticky top-0 z-30 bg-indigo-700 rounded-b-[1.5rem] shadow-md px-4 py-3 flex items-center gap-3 safe-top">
        <a href="{{ route('gis-cad.index') }}" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-black text-white truncate">
                {{ $export->original_file_name ?? ($export->survey?->displayTitle() ?? 'Export #' . $export->id_gis_cad_export) }}
            </p>
            <p class="text-[11px] text-indigo-100">GIS to CAD Generator</p>
        </div>
    </div>

    <div class="px-4 pt-6 space-y-4">

        {{-- Status Card --}}
        <div id="statusCard" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 text-center">
            <div id="statusIcon" class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl"></div>
            <p id="statusLabel" class="font-black text-slate-800 text-sm"></p>
            <p id="statusStage" class="text-xs text-slate-500 mt-1"></p>

            <div id="statusErrorBox" class="hidden mt-3 bg-red-50 border border-red-200 rounded-xl p-3 text-left">
                <p class="text-[11px] font-bold text-red-700 mb-1">Detail Error:</p>
                <p id="statusErrorText" class="text-[11px] text-red-600 font-mono break-words"></p>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-4 text-left">
                <div class="bg-slate-50 rounded-xl p-3">
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Titik</p>
                    <p id="statusPoints" class="text-lg font-black text-slate-800">{{ $export->points_count }}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-3">
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Jalur Kabel</p>
                    <p id="statusPolylines" class="text-lg font-black text-slate-800">{{ $export->polylines_count }}</p>
                </div>
            </div>

            <div id="statusUtm" class="mt-2 text-[11px] text-slate-500"></div>
        </div>

        {{-- Download --}}
        <div id="downloadBox" class="hidden space-y-2">
            <a href="{{ route('gis-cad.download.dxf', $export->uuid) }}"
               class="flex items-center justify-center gap-2 h-12 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-sm font-black shadow-lg shadow-emerald-600/30">
                <i class="fa-solid fa-file-arrow-down"></i> Download DXF
            </a>
            @if($export->bom_path)
                <a href="{{ route('gis-cad.download.bom', $export->uuid) }}"
                   class="flex items-center justify-center gap-2 h-11 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-black">
                    <i class="fa-solid fa-file-excel"></i> Download BOM (Excel)
                </a>
            @endif
        </div>

        @if($export->isFailed())
            <form method="POST" action="{{ route('gis-cad.confirm', $export->uuid) }}">
                @csrf
                <input type="hidden" name="template" value="{{ $export->template }}">
                <button type="submit" class="w-full h-11 rounded-xl bg-indigo-600 text-white text-xs font-black shadow-sm">
                    <i class="fa-solid fa-rotate-right mr-1"></i> Coba Generate Ulang
                </button>
            </form>
        @endif
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const statusUrl = @json(route('gis-cad.status', $export->uuid));

    const cfg = {
        draft: { icon: 'fa-pen', color: 'bg-amber-100 text-amber-600', label: 'Draft' },
        queued: { icon: 'fa-hourglass-half', color: 'bg-slate-100 text-slate-500', label: 'Menunggu Antrean' },
        processing: { icon: 'fa-gear fa-spin', color: 'bg-blue-100 text-blue-600', label: 'Sedang Diproses' },
        completed: { icon: 'fa-circle-check', color: 'bg-emerald-100 text-emerald-600', label: 'Selesai' },
        failed: { icon: 'fa-circle-xmark', color: 'bg-red-100 text-red-600', label: 'Gagal' },
    };

    let pollTimer = null;

    function render(data) {
        const c = cfg[data.status] || cfg.queued;

        const iconEl = document.getElementById('statusIcon');
        iconEl.className = 'w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl ' + c.color;
        iconEl.innerHTML = '<i class="fa-solid ' + c.icon + '"></i>';

        document.getElementById('statusLabel').textContent = c.label;
        document.getElementById('statusStage').textContent = data.stage || '';
        document.getElementById('statusPoints').textContent = data.points_count;
        document.getElementById('statusPolylines').textContent = data.polylines_count;

        document.getElementById('statusUtm').textContent = data.utm_zone ? ('Zona UTM: ' + data.utm_zone) : '';

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
