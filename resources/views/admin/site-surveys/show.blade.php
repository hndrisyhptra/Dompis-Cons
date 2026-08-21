@extends('layouts.admin')

@section('content')

@php
    $isDone = $survey->status === 'completed';
    $totalRouteMeters = $survey->routes->sum('distance_meters');
@endphp

<div class="space-y-5">

    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.site-surveys.index') }}" class="text-xs font-bold text-gray-500 hover:text-blue-600">&larr; Hasil Survey Lapangan</a>
            </div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ $survey->displayTitle() }}
            </h1>
            <p class="text-sm text-gray-500">
                @if($survey->project)
                    {{ $survey->project->project_name }} &middot; PID: {{ $survey->project->pid ?? '-' }}
                @else
                    {{ $survey->project_name ?? 'Tidak dikaitkan ke project' }}
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($isDone)
                <span class="px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-700 text-xs font-bold inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                </span>
            @else
                <span class="px-3 py-1.5 rounded-xl bg-amber-100 text-amber-700 text-xs font-bold inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Berjalan
                </span>
            @endif

            <a href="{{ route('surveyor.kml', $survey->id) }}"
               class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition inline-flex items-center gap-2">
                Unduh KML
            </a>
        </div>
    </div>

    {{-- INFO CARDS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-[11px] font-bold text-gray-400 uppercase">Surveyor</p>
            <p class="text-sm font-black text-gray-900 dark:text-white mt-1">{{ $survey->surveyor->name ?? '-' }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-[11px] font-bold text-gray-400 uppercase">Titik Tersurvei</p>
            <p class="text-2xl font-black text-blue-600 mt-1">{{ $survey->points->count() }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-[11px] font-bold text-gray-400 uppercase">Rute Kabel</p>
            <p class="text-2xl font-black text-slate-700 mt-1">{{ $survey->routes->count() }}</p>
            <p class="text-[11px] font-semibold text-emerald-600 mt-0.5">
                &asymp; {{ $totalRouteMeters >= 1000 ? number_format($totalRouteMeters / 1000, 2, ',', '.') . ' km' : number_format($totalRouteMeters, 0, ',', '.') . ' m' }} total
            </p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-[11px] font-bold text-gray-400 uppercase">Update Terakhir</p>
            <p class="text-sm font-black text-gray-900 dark:text-white mt-1">{{ $survey->updated_at->diffForHumans() }}</p>
        </div>
    </div>

    @if($survey->notes)
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
            <p class="text-[11px] font-bold text-blue-500 uppercase mb-1">Catatan Surveyor</p>
            <p class="text-sm text-blue-700">{{ $survey->notes }}</p>
        </div>
    @endif

    {{-- MAP TOOLBAR --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-3 flex flex-wrap items-center gap-2">
        <span class="text-[11px] font-bold text-gray-400 uppercase pl-1 pr-1">Tampilkan:</span>

        <label class="layer-toggle inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer select-none">
            <input type="checkbox" data-layer="tiang" checked class="accent-blue-600 w-3.5 h-3.5">
            <span class="w-2 h-2 rounded-full bg-blue-600"></span> Tiang
        </label>
        <label class="layer-toggle inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer select-none">
            <input type="checkbox" data-layer="odc" checked class="accent-red-600 w-3.5 h-3.5">
            <span class="w-2 h-2 rounded-full bg-red-600"></span> ODC
        </label>
        <label class="layer-toggle inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer select-none">
            <input type="checkbox" data-layer="odp" checked class="accent-amber-500 w-3.5 h-3.5">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span> ODP
        </label>
        <label class="layer-toggle inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer select-none">
            <input type="checkbox" data-layer="jc" checked class="accent-indigo-600 w-3.5 h-3.5">
            <span class="w-2 h-2 rounded-full bg-indigo-600"></span> JC
        </label>
        <label class="layer-toggle inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer select-none">
            <input type="checkbox" data-layer="routes" checked class="accent-orange-600 w-3.5 h-3.5">
            <span class="w-3 h-0.5 rounded bg-orange-600"></span> Rute Kabel
        </label>
        <label class="layer-toggle inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer select-none">
            <input type="checkbox" data-layer="end" checked class="accent-emerald-600 w-3.5 h-3.5">
            <span class="w-2 h-2 rounded-full bg-emerald-600"></span> Ending Site
        </label>

        <span class="w-px h-5 bg-gray-200 dark:bg-gray-700 mx-1"></span>

        <button type="button" id="btnMeasure"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ruler-icon lucide-ruler"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>
            Ukur Jarak
        </button>

        <button type="button" id="btnMeasureClear" class="hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-xs font-bold text-red-600 hover:bg-red-50 transition">
            Hapus Pengukuran
        </button>

        <button type="button" id="btnFullscreen"
            class="ml-auto inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
            </svg>
            Fullscreen
        </button>
    </div>

    {{-- MAP --}}
    <div id="mapCard" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden relative">
        <div id="map" class="w-full" style="height: 620px;"></div>

        {{-- Measurement badge --}}
        <div id="measureBadge" class="hidden absolute top-3 left-1/2 -translate-x-1/2 z-[500] bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-lg items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m14.622 17.897-10.68-2.913a1 1 0 0 1-.79-.965V3.535a1 1 0 0 1 1-1H15a1 1 0 0 1 1 1v13.5"/><path d="M15 4.5 4.5 15"/>
            </svg>
            <span>Klik peta untuk ukur &middot; Jarak: <span id="measureValue">0 m</span></span>
        </div>

        {{-- Legend --}}
        <div class="absolute bottom-3 left-3 z-[400] bg-white/95 dark:bg-gray-900/95 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2.5 shadow-md text-[10px] font-bold text-gray-600 dark:text-gray-300 space-y-1.5">
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-600"></span> Tiang Eksisting</div>
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-600"></span> Catuan ODC</div>
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Catuan ODP</div>
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-indigo-600"></span> Catuan JC</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-0.5 rounded bg-orange-600"></span> Rute Kabel</div>
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-600"></span> Ending Site</div>
        </div>
    </div>

    {{-- LISTS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-black text-gray-900 dark:text-white">Titik Tiang &amp; Catuan</h2>
            </div>
            <div class="max-h-96 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($survey->points as $point)
                    @php
                        $labels = ['tiang_eksisting' => 'Tiang Eksisting', 'catuan' => 'Catuan ' . $point->catuan_type];
                        $colors = [
                            'tiang_eksisting' => 'bg-blue-100 text-blue-700',
                            'ODC' => 'bg-red-100 text-red-700',
                            'ODP' => 'bg-amber-100 text-amber-700',
                            'JC' => 'bg-indigo-100 text-indigo-700',
                        ];
                        $colorKey = $point->type === 'catuan' ? $point->catuan_type : 'tiang_eksisting';
                    @endphp
                    <button type="button" data-flyto-point="{{ $point->id }}"
                        class="w-full text-left px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ $point->name ?: ($labels[$point->type] ?? $point->type) }}</p>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ number_format($point->latitude, 6) }}, {{ number_format($point->longitude, 6) }}</p>
                        </div>
                        <span class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-extrabold {{ $colors[$colorKey] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $labels[$point->type] ?? $point->type }}
                        </span>
                    </button>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-400">Belum ada titik yang ditandai.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h2 class="text-sm font-black text-gray-900 dark:text-white">Rute Kabel</h2>
                <span class="text-[11px] font-bold text-emerald-600">
                    &asymp; {{ $totalRouteMeters >= 1000 ? number_format($totalRouteMeters / 1000, 2, ',', '.') . ' km' : number_format($totalRouteMeters, 0, ',', '.') . ' m' }}
                </span>
            </div>
            <div class="max-h-96 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($survey->routes as $route)
                    <button type="button" data-flyto-route="{{ $route->id }}"
                        class="w-full text-left px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ $route->name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ count($route->path ?? []) }} titik vertex</p>
                        </div>
                        <span class="shrink-0 text-xs font-black text-emerald-600">
                            {{ $route->distance_meters >= 1000 ? number_format($route->distance_meters / 1000, 2) . ' km' : number_format($route->distance_meters, 0) . ' m' }}
                        </span>
                    </button>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-400">Belum ada rute kabel yang digambar.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
    #map { z-index: 0; }
    .marker-dot { border-radius: 999px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,.35); }
    .route-label {
        background: rgba(15,23,42,.85);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 2px 6px;
        font-size: 10px;
        font-weight: 700;
        box-shadow: none;
    }
    .route-label::before { display: none; }
    .layer-toggle:has(input:checked) { background: #eff6ff; border-color: #bfdbfe; }
    .measure-vertex { background: #7c3aed; border: 2px solid white; border-radius: 999px; box-shadow: 0 1px 4px rgba(0,0,0,.4); }
</style>

@php
    // Disiapkan sebagai variabel PHP biasa dulu (bukan langsung di dalam @json(...))
    // karena directive @json() Blade memecah argumennya dengan explode(',', ...) -
    // kalau array literalnya taruh langsung di dalam @json(...), koma di dalam
    // array ikut kepotong dan bikin PHP hasil kompilasi Blade jadi rusak.
    $pointsForJs = $survey->points->map(function ($p) {
        return [
            'id' => $p->id,
            'type' => $p->type,
            'catuan_type' => $p->catuan_type,
            'name' => $p->name,
            'lat' => (float) $p->latitude,
            'lng' => (float) $p->longitude,
            'notes' => $p->notes,
            'photo_url' => $p->photo_path ? \Illuminate\Support\Facades\Storage::url($p->photo_path) : null,
        ];
    });

    $routesForJs = $survey->routes->map(function ($r) {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'path' => $r->path,
            'distance_meters' => (float) $r->distance_meters,
        ];
    });

    $endingSiteForJs = $survey->hasEndingSite() ? [
        'lat' => (float) $survey->ending_site_lat,
        'lng' => (float) $survey->ending_site_lng,
        'name' => $survey->ending_site_name,
    ] : null;
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {

    const POINTS = @json($pointsForJs);
    const ROUTES_DATA = @json($routesForJs);
    const ENDING_SITE = @json($endingSiteForJs);

    const map = L.map('map', { zoomControl: false }).setView([-2.5489, 118.0149], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.control.layers({
        'OpenStreetMap': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'Satellite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri'
        })
    }, {}, { position: 'bottomright' }).addTo(map);

    /* ---------------- LAYER GROUPS ---------------- */
    const layers = {
        tiang: L.layerGroup().addTo(map),
        odc: L.layerGroup().addTo(map),
        odp: L.layerGroup().addTo(map),
        jc: L.layerGroup().addTo(map),
        routes: L.layerGroup().addTo(map),
        end: L.layerGroup().addTo(map),
    };

    const markerRefs = {};   // point.id -> L.Marker
    const routeRefs = {};    // route.id -> L.Polyline

    function dotIcon(color, size) {
        size = size || 20;
        return L.divIcon({
            className: '',
            html: `<div class="marker-dot" style="width:${size}px;height:${size}px;background:${color};"></div>`,
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
        });
    }

    function formatDistance(m) {
        m = parseFloat(m || 0);
        return m >= 1000 ? (m / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' km' : Math.round(m).toLocaleString('id-ID') + ' m';
    }

    function escapeHtml(s) {
        return (s || '').toString().replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function popupForPoint(p) {
        const label = p.type === 'catuan' ? ('Catuan ' + (p.catuan_type || '')) : 'Tiang Eksisting';
        const coordText = p.lat.toFixed(6) + ', ' + p.lng.toFixed(6);
        const gmapsUrl = `https://www.google.com/maps?q=${p.lat},${p.lng}`;
        const photoHtml = p.photo_url
            ? `<a href="${p.photo_url}" target="_blank"><img src="${p.photo_url}" style="width:100%;border-radius:8px;margin-top:6px;max-height:120px;object-fit:cover;"></a>`
            : '';
        return `<div style="min-width:190px" class="text-xs">
            <b>${escapeHtml(p.name || label)}</b><br>
            <span style="color:#64748b">${label}</span><br>
            <span style="font-family:monospace;font-size:10px" data-coord="${coordText}">${coordText}</span>
            ${p.notes ? `<div style="margin-top:4px;color:#475569">${escapeHtml(p.notes)}</div>` : ''}
            ${photoHtml}
            <div style="margin-top:8px;display:flex;gap:6px;">
                <button onclick="navigator.clipboard.writeText('${coordText}').then(()=>this.textContent='Tersalin!')" style="flex:1;background:#f1f5f9;border:none;border-radius:6px;padding:5px 8px;font-size:10px;font-weight:700;cursor:pointer;">Salin Koordinat</button>
                <a href="${gmapsUrl}" target="_blank" style="flex:1;text-align:center;background:#eff6ff;color:#1d4ed8;border-radius:6px;padding:5px 8px;font-size:10px;font-weight:700;text-decoration:none;">Buka di Maps</a>
            </div>
        </div>`;
    }

    POINTS.forEach(function (p) {
        let color = '#2563eb', group = layers.tiang;
        if (p.type === 'catuan') {
            if (p.catuan_type === 'ODC') { color = '#dc2626'; group = layers.odc; }
            else if (p.catuan_type === 'ODP') { color = '#f59e0b'; group = layers.odp; }
            else if (p.catuan_type === 'JC') { color = '#4f46e5'; group = layers.jc; }
        }
        const marker = L.marker([p.lat, p.lng], { icon: dotIcon(color) });
        marker.bindPopup(popupForPoint(p));
        marker.addTo(group);
        markerRefs[p.id] = marker;
    });

    ROUTES_DATA.forEach(function (r) {
        const latlngs = (r.path || []).map(function (pt) { return [pt[0], pt[1]]; });
        if (latlngs.length < 2) return;
        const line = L.polyline(latlngs, { color: '#ea580c', weight: 4, opacity: 0.9 });
        line.bindPopup(`<div class="text-xs" style="min-width:170px"><b>${escapeHtml(r.name)}</b><br><span style="color:#059669;font-weight:700">${formatDistance(r.distance_meters)}</span></div>`);
        line.bindTooltip(formatDistance(r.distance_meters), { permanent: true, direction: 'center', className: 'route-label' });
        line.addTo(layers.routes);
        routeRefs[r.id] = line;
    });

    if (ENDING_SITE) {
        const endMarker = L.marker([ENDING_SITE.lat, ENDING_SITE.lng], { icon: dotIcon('#059669', 22) });
        endMarker.bindPopup(`<div class="text-xs"><b>${escapeHtml(ENDING_SITE.name || 'Ending Site')}</b></div>`);
        endMarker.addTo(layers.end);
    }

    /* ---------------- FIT BOUNDS ---------------- */
    const allLayers = [].concat(
        Object.values(markerRefs), Object.values(routeRefs),
        ENDING_SITE ? [L.marker([ENDING_SITE.lat, ENDING_SITE.lng])] : []
    );
    if (allLayers.length) {
        try { map.fitBounds(L.featureGroup(allLayers).getBounds().pad(0.2)); } catch (e) {}
    }

    /* ---------------- LAYER TOGGLES ---------------- */
    document.querySelectorAll('[data-layer]').forEach(function (input) {
        input.addEventListener('change', function () {
            const key = this.dataset.layer;
            if (this.checked) map.addLayer(layers[key]);
            else map.removeLayer(layers[key]);
        });
    });

    /* ---------------- FLY-TO FROM LIST ---------------- */
    document.querySelectorAll('[data-flyto-point]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.flytoPoint;
            const marker = markerRefs[id];
            if (!marker) return;
            const key = Object.keys(layers).find(k => layers[k].hasLayer(marker));
            if (key) document.querySelector(`[data-layer="${key}"]`).checked = true, map.addLayer(layers[key]);
            map.flyTo(marker.getLatLng(), 18, { duration: 0.6 });
            setTimeout(() => marker.openPopup(), 650);
        });
    });

    document.querySelectorAll('[data-flyto-route]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.flytoRoute;
            const line = routeRefs[id];
            if (!line) return;
            document.querySelector('[data-layer="routes"]').checked = true;
            map.addLayer(layers.routes);
            map.flyToBounds(line.getBounds(), { duration: 0.6, padding: [40, 40] });
            const original = line.options.weight;
            line.setStyle({ weight: 8 });
            setTimeout(() => { line.setStyle({ weight: original }); line.openPopup(); }, 650);
        });
    });

    /* ---------------- MEASURE TOOL ---------------- */
    let measuring = false;
    let measurePoints = [];
    let measureLine = null;
    let measureVertexMarkers = [];
    const btnMeasure = document.getElementById('btnMeasure');
    const btnMeasureClear = document.getElementById('btnMeasureClear');
    const measureBadge = document.getElementById('measureBadge');
    const measureValueEl = document.getElementById('measureValue');

    function clearMeasure() {
        measurePoints = [];
        if (measureLine) { map.removeLayer(measureLine); measureLine = null; }
        measureVertexMarkers.forEach(m => map.removeLayer(m));
        measureVertexMarkers = [];
        measureValueEl.textContent = '0 m';
        btnMeasureClear.classList.add('hidden');
    }

    function totalMeasureDistance() {
        let sum = 0;
        for (let i = 0; i < measurePoints.length - 1; i++) {
            sum += measurePoints[i].distanceTo(measurePoints[i + 1]);
        }
        return sum;
    }

    function redrawMeasureLine() {
        if (measureLine) map.removeLayer(measureLine);
        if (measurePoints.length > 1) {
            measureLine = L.polyline(measurePoints, { color: '#7c3aed', weight: 3, dashArray: '6 6' }).addTo(map);
        }
        measureValueEl.textContent = formatDistance(totalMeasureDistance());
        if (measurePoints.length) btnMeasureClear.classList.remove('hidden');
    }

    function onMeasureClick(e) {
        const vertex = L.circleMarker(e.latlng, { radius: 5, className: 'measure-vertex', color: '#7c3aed', fillColor: '#7c3aed', fillOpacity: 1, weight: 2 }).addTo(map);
        measureVertexMarkers.push(vertex);
        measurePoints.push(e.latlng);
        redrawMeasureLine();
    }

    btnMeasure.addEventListener('click', function () {
        measuring = !measuring;
        if (measuring) {
            this.classList.add('bg-purple-600', 'text-white', 'border-purple-600');
            this.classList.remove('text-gray-600', 'dark:text-gray-300');
            measureBadge.classList.remove('hidden');
            measureBadge.classList.add('flex');
            map.getContainer().style.cursor = 'crosshair';
            map.on('click', onMeasureClick);
        } else {
            this.classList.remove('bg-purple-600', 'text-white', 'border-purple-600');
            this.classList.add('text-gray-600', 'dark:text-gray-300');
            measureBadge.classList.add('hidden');
            measureBadge.classList.remove('flex');
            map.getContainer().style.cursor = '';
            map.off('click', onMeasureClick);
        }
    });

    btnMeasureClear.addEventListener('click', function (e) {
        e.stopPropagation();
        clearMeasure();
    });

    /* ---------------- FULLSCREEN ---------------- */
    const mapCard = document.getElementById('mapCard');
    document.getElementById('btnFullscreen').addEventListener('click', function () {
        if (!document.fullscreenElement) {
            mapCard.requestFullscreen?.();
        } else {
            document.exitFullscreen?.();
        }
    });
    document.addEventListener('fullscreenchange', function () {
        if (document.fullscreenElement === mapCard) {
            document.getElementById('map').style.height = '100vh';
        } else {
            document.getElementById('map').style.height = '620px';
        }
        setTimeout(() => map.invalidateSize(), 150);
    });

});
</script>

@endsection
