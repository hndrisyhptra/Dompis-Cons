@extends('layouts.surveyor')

@section('title', $survey->title)

@push('head')
<style>
    #surveyMap { height: 52vh; min-height: 340px; width: 100%; }
    .leaflet-div-icon { background: transparent; border: none; }
    .pin {
        width: 30px; height: 30px; border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,.35);
        border: 2px solid white;
    }
    .pin i { transform: rotate(45deg); color: white; font-size: 13px; }
    .pin-tiang { background: #2563eb; }
    .pin-odc { background: #dc2626; }
    .pin-odp { background: #f59e0b; }
    .pin-jc { background: #7c3aed; }
    .pin-end { background: #059669; }
    .mode-btn.active { background: #1d4ed8 !important; color: white !important; border-color: #1d4ed8 !important; }
    .sheet-backdrop { background: rgba(15,23,42,.55); backdrop-filter: blur(2px); }
</style>
@endpush

@section('content')

    {{-- Top Bar --}}
    <div class="sticky top-0 z-30 bg-blue-700 rounded-b-[1.5rem] shadow-md px-4 py-3 flex items-center gap-3 safe-top">
        <a href="{{ route('surveyor.index') }}" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-black text-white truncate">{{ $survey->displayTitle() }}</p>
            <p class="text-[11px] text-blue-100">
                {{ $survey->status === 'completed' ? '✅ Selesai' : '⏳ On Progress' }}
                &middot; oleh {{ $survey->surveyor->name ?? '-' }}
            </p>
        </div>
        <button id="btnSurveyMenu" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0">
            <i class="fa-solid fa-ellipsis-vertical text-sm"></i>
        </button>
    </div>

    {{-- Map --}}
    <div class="relative">
        <div id="surveyMap"></div>

        {{-- Locate Me --}}
        <button id="btnLocate" class="absolute top-3 right-3 z-[400] w-10 h-10 rounded-xl bg-white shadow-lg border border-slate-200 flex items-center justify-center text-blue-600">
            <i class="fa-solid fa-location-crosshairs"></i>
        </button>

        {{-- GPS accuracy pill --}}
        <div id="gpsPill" class="hidden absolute top-3 left-3 z-[400] bg-white/95 shadow-md rounded-xl px-3 py-1.5 text-[10px] font-bold text-slate-600 border border-slate-200">
            <i class="fa-solid fa-satellite-dish text-emerald-500 mr-1"></i><span id="gpsAccuracyText">-</span>
        </div>

        {{-- Route drawing banner --}}
        <div id="routeBanner" class="hidden absolute bottom-3 inset-x-3 z-[400] bg-orange-600 text-white rounded-xl px-3.5 py-2.5 shadow-lg text-xs font-bold flex items-center justify-between gap-2">
            <span><i class="fa-solid fa-route mr-1"></i> Mode Gambar Rute — Tap peta untuk menambah titik (<span id="routeVertexCount">0</span> titik)</span>
        </div>
    </div>

    {{-- Mode Toolbar --}}
    <div class="px-4 pt-3">
        <div class="grid grid-cols-3 gap-2">
            <button data-mode="tag" class="mode-btn active px-2 py-2.5 rounded-xl border border-slate-200 bg-white text-[11px] font-bold text-slate-700 flex flex-col items-center gap-1 transition">
                <i class="fa-solid fa-map-pin"></i> Tag Titik
            </button>
            <button data-mode="route" class="mode-btn px-2 py-2.5 rounded-xl border border-slate-200 bg-white text-[11px] font-bold text-slate-700 flex flex-col items-center gap-1 transition">
                <i class="fa-solid fa-route"></i> Gambar Rute
            </button>
            <button data-mode="end" class="mode-btn px-2 py-2.5 rounded-xl border border-slate-200 bg-white text-[11px] font-bold text-slate-700 flex flex-col items-center gap-1 transition">
                <i class="fa-solid fa-flag-checkered"></i> Ending Site
            </button>
        </div>

        {{-- Route mode action buttons (muncul saat mode = route) --}}
        <div id="routeActions" class="hidden grid grid-cols-3 gap-2 mt-2">
            <button id="btnRouteGps" class="px-2 py-2 rounded-xl bg-blue-50 text-blue-700 text-[11px] font-bold border border-blue-100">
                <i class="fa-solid fa-crosshairs mr-1"></i> +GPS
            </button>
            <button id="btnRouteUndo" class="px-2 py-2 rounded-xl bg-slate-100 text-slate-600 text-[11px] font-bold border border-slate-200">
                <i class="fa-solid fa-rotate-left mr-1"></i> Undo
            </button>
            <button id="btnRouteFinish" class="px-2 py-2 rounded-xl bg-emerald-600 text-white text-[11px] font-bold">
                <i class="fa-solid fa-check mr-1"></i> Simpan
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="px-4 pt-4">
        <div class="grid grid-cols-4 gap-2 text-center">
            <div class="bg-white rounded-xl border border-slate-100 py-2.5">
                <p class="text-base font-black text-blue-600" id="statTiang">{{ $survey->points->where('type','tiang_eksisting')->count() }}</p>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Tiang</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 py-2.5">
                <p class="text-base font-black text-amber-600" id="statCatuan">{{ $survey->points->where('type','catuan')->count() }}</p>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Catuan</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 py-2.5">
                <p class="text-base font-black text-orange-600" id="statRute">{{ $survey->routes->count() }}</p>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Rute</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 py-2.5">
                <p class="text-base font-black text-emerald-600" id="statJarak">{{ number_format($survey->totalRouteDistanceMeters(),0,',','.') }}m</p>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Jarak</p>
            </div>
        </div>
    </div>

    {{-- List Tabs --}}
    <div class="px-4 pt-4">
        <div class="flex items-center gap-2 text-xs font-bold border-b border-slate-200">
            <button data-tab="points" class="list-tab px-3 py-2 border-b-2 border-blue-600 text-blue-700">Titik</button>
            <button data-tab="routes" class="list-tab px-3 py-2 border-b-2 border-transparent text-slate-400">Rute Kabel</button>
        </div>

        <div id="pointsList" class="py-3 space-y-2"></div>
        <div id="routesList" class="hidden py-3 space-y-2"></div>
    </div>

    {{-- Bottom Sheet: Tambah Titik --}}
    <div id="pointSheet" class="hidden fixed inset-0 z-[500] sheet-backdrop items-end justify-center">
        <div class="w-full max-w-md bg-white rounded-t-3xl p-5 pb-8 max-h-[85vh] overflow-y-auto">
            <div class="w-10 h-1.5 bg-slate-200 rounded-full mx-auto mb-4"></div>
            <h3 class="text-sm font-black text-slate-900 mb-4">Tandai Titik Baru</h3>

            <form id="pointForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Jenis Titik</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" data-type="tiang_eksisting" class="point-type-btn px-3 py-2.5 rounded-xl border-2 border-blue-600 bg-blue-50 text-blue-700 text-xs font-bold">
                            <i class="fa-solid fa-tower-broadcast mr-1"></i> Tiang Eksisting
                        </button>
                        <button type="button" data-type="catuan" class="point-type-btn px-3 py-2.5 rounded-xl border-2 border-slate-200 text-slate-600 text-xs font-bold">
                            <i class="fa-solid fa-box-archive mr-1"></i> Catuan
                        </button>
                    </div>
                </div>

                <div id="catuanTypeWrap" class="hidden">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Catuan</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" data-catuan="ODC" class="catuan-type-btn px-2 py-2 rounded-xl border-2 border-slate-200 text-slate-600 text-xs font-bold">ODC</button>
                        <button type="button" data-catuan="ODP" class="catuan-type-btn px-2 py-2 rounded-xl border-2 border-slate-200 text-slate-600 text-xs font-bold">ODP</button>
                        <button type="button" data-catuan="JC" class="catuan-type-btn px-2 py-2 rounded-xl border-2 border-slate-200 text-slate-600 text-xs font-bold">JC</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama / Label (opsional)</label>
                    <input type="text" id="pointName" placeholder="Contoh: Tiang-12 / ODP-03"
                           class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Latitude</label>
                        <input type="text" id="pointLat" required class="w-full h-11 rounded-xl border-slate-200 text-sm font-mono focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Longitude</label>
                        <input type="text" id="pointLng" required class="w-full h-11 rounded-xl border-slate-200 text-sm font-mono focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none">
                    </div>
                </div>
                <button type="button" id="btnUseGpsForPoint" class="w-full h-10 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
                    <i class="fa-solid fa-crosshairs mr-1"></i> Gunakan Lokasi GPS Saat Ini
                </button>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Foto Eviden (opsional)</label>
                    <input type="file" id="pointPhoto" accept="image/*" capture="environment"
                           class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:text-xs file:font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan (opsional)</label>
                    <textarea id="pointNotes" rows="2" class="w-full rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none"></textarea>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" id="btnCancelPoint" class="flex-1 h-11 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold">Batal</button>
                    <button type="submit" class="flex-1 h-11 rounded-xl bg-blue-600 text-white text-sm font-bold shadow-lg shadow-blue-600/30">Simpan Titik</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bottom Sheet: Simpan Rute --}}
    <div id="routeSheet" class="hidden fixed inset-0 z-[500] sheet-backdrop items-end justify-center">
        <div class="w-full max-w-md bg-white rounded-t-3xl p-5 pb-8">
            <div class="w-10 h-1.5 bg-slate-200 rounded-full mx-auto mb-4"></div>
            <h3 class="text-sm font-black text-slate-900 mb-4">Simpan Rute Kabel</h3>
            <p class="text-xs text-slate-500 mb-3"><span id="routeSheetCount">0</span> titik rute akan disimpan.</p>
            <input type="text" id="routeName" placeholder="Nama rute (contoh: Rute Kabel Utama)"
                   class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none mb-4">
            <div class="flex gap-3">
                <button type="button" id="btnCancelRoute" class="flex-1 h-11 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold">Batal</button>
                <button type="button" id="btnSaveRoute" class="flex-1 h-11 rounded-xl bg-orange-600 text-white text-sm font-bold shadow-lg shadow-orange-600/30">Simpan Rute</button>
            </div>
        </div>
    </div>

    {{-- Bottom Sheet: Ending Site --}}
    <div id="endSheet" class="hidden fixed inset-0 z-[500] sheet-backdrop items-end justify-center">
        <div class="w-full max-w-md bg-white rounded-t-3xl p-5 pb-8">
            <div class="w-10 h-1.5 bg-slate-200 rounded-full mx-auto mb-4"></div>
            <h3 class="text-sm font-black text-slate-900 mb-4">Set Titik Ending Site</h3>
            <input type="text" id="endName" placeholder="Nama lokasi ending site"
                   class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none mb-3">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <input type="text" id="endLat" placeholder="Latitude" class="w-full h-11 rounded-xl border-slate-200 text-sm font-mono focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none">
                <input type="text" id="endLng" placeholder="Longitude" class="w-full h-11 rounded-xl border-slate-200 text-sm font-mono focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none">
            </div>
            <button type="button" id="btnUseGpsForEnd" class="w-full h-10 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100 mb-4">
                <i class="fa-solid fa-crosshairs mr-1"></i> Gunakan Lokasi GPS Saat Ini
            </button>
            <div class="flex gap-3">
                <button type="button" id="btnCancelEnd" class="flex-1 h-11 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold">Batal</button>
                <button type="button" id="btnSaveEnd" class="flex-1 h-11 rounded-xl bg-emerald-600 text-white text-sm font-bold shadow-lg shadow-emerald-600/30">Simpan</button>
            </div>
        </div>
    </div>

@endsection

@section('bottom-nav')
    <div class="fixed bottom-0 left-0 right-0 z-40 safe-bottom">
        <div class="max-w-md mx-auto glass border-t border-slate-200/70 px-4 py-3 shadow-[0_-8px_24px_-8px_rgba(15,23,42,.15)] flex gap-2">
            <form method="POST" action="{{ route('surveyor.kml', $survey->id) }}" class="flex-1" onsubmit="return false;">
                <a href="{{ route('surveyor.kml', $survey->id) }}"
                   class="flex items-center justify-center gap-2 h-12 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-black w-full">
                    <i class="fa-solid fa-file-arrow-down"></i> Unduh KML
                </a>
            </form>

            @if($survey->status !== 'completed')
                <button id="btnCompleteSurvey" class="flex-[1.4] h-12 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-xs font-black shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> Selesaikan Survey
                </button>
            @else
                <div class="flex-[1.4] h-12 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-black border border-emerald-200 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> Survey Selesai
                </div>
            @endif
        </div>
    </div>

    <form id="completeForm" method="POST" action="{{ route('surveyor.complete', $survey->id) }}" class="hidden">
        @csrf
    </form>
    <form id="deleteForm" method="POST" action="{{ route('surveyor.destroy', $survey->id) }}" class="hidden">
        @csrf @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const SURVEY_ID = {{ $survey->id }};
    const ROUTES = {
        pointStore: @json(route('surveyor.points.store', $survey->id)),
        pointDestroyBase: '{{ url('/surveyor/points') }}',
        pointUpdateBase: '{{ url('/surveyor/points') }}',
        routeStore: @json(route('surveyor.routes.store', $survey->id)),
        routeDestroyBase: '{{ url('/surveyor/routes') }}',
        endingSite: @json(route('surveyor.ending-site.store', $survey->id)),
        kml: @json(route('surveyor.kml', $survey->id)),
    };

    const initialPoints = @json($survey->points->values());
    const initialRoutes = @json($survey->routes->values());
    const initialEnd = {
        lat: @json($survey->ending_site_lat),
        lng: @json($survey->ending_site_lng),
        name: @json($survey->ending_site_name),
    };

    let map, markersLayer, routesLayer, endMarker;
    let mode = 'tag';
    let pendingLatLng = null;
    let drawingPath = []; // array of [lat,lng]
    let drawingPolyline = null;

    /* ---------------- MAP INIT ---------------- */
    function initMap() {
        const center = initialPoints.length
            ? [initialPoints[0].latitude, initialPoints[0].longitude]
            : (initialEnd.lat ? [initialEnd.lat, initialEnd.lng] : [-2.5489, 118.0149]);

        map = L.map('surveyMap', { zoomControl: false }).setView(center, initialPoints.length || initialEnd.lat ? 16 : 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 22, attribution: '&copy; OpenStreetMap' }).addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        L.control.layers({
            'Peta': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
            'Satelit': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' })
        }, {}, { position: 'bottomright' }).addTo(map);

        markersLayer = L.layerGroup().addTo(map);
        routesLayer = L.layerGroup().addTo(map);

        initialPoints.forEach(renderPointMarker);
        initialRoutes.forEach(renderRouteLine);
        if (initialEnd.lat && initialEnd.lng) renderEndMarker(initialEnd.lat, initialEnd.lng, initialEnd.name);

        fitAllIfPossible();

        map.on('click', function (e) {
            onMapClick(e.latlng);
        });
    }

    function fitAllIfPossible() {
        const group = L.featureGroup([...markersLayer.getLayers(), ...routesLayer.getLayers(), ...(endMarker ? [endMarker] : [])]);
        if (group.getLayers().length) {
            try { map.fitBounds(group.getBounds().pad(0.25)); } catch (e) {}
        }
    }

    function pinIcon(cls, iconClass) {
        return L.divIcon({
            className: 'leaflet-div-icon',
            html: `<div class="pin ${cls}"><i class="fa-solid ${iconClass}"></i></div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 28],
            popupAnchor: [0, -26],
        });
    }

    function iconFor(point) {
        if (point.type === 'catuan') {
            const map2 = { ODC: 'pin-odc', ODP: 'pin-odp', JC: 'pin-jc' };
            return pinIcon(map2[point.catuan_type] || 'pin-odc', 'fa-box-archive');
        }
        return pinIcon('pin-tiang', 'fa-tower-broadcast');
    }

    function renderPointMarker(point) {
        const marker = L.marker([point.latitude, point.longitude], { icon: iconFor(point) });
        marker.bindPopup(popupHtmlForPoint(point));
        marker._pointId = point.id;
        marker.addTo(markersLayer);
        return marker;
    }

    function popupHtmlForPoint(point) {
        const label = point.type === 'catuan' ? ('Catuan ' + (point.catuan_type || '')) : 'Tiang Eksisting';
        return `<div style="min-width:170px" class="text-xs">
            <b>${escapeHtml(point.name || label)}</b><br>
            <span style="color:#64748b">${label}</span><br>
            <span style="font-family:monospace;font-size:10px">${point.latitude}, ${point.longitude}</span>
        </div>`;
    }

    function renderRouteLine(route) {
        const latlngs = (route.path || []).map(p => [p[0], p[1]]);
        const line = L.polyline(latlngs, { color: '#ea580c', weight: 4, opacity: 0.9 });
        line.bindPopup(`<b>${escapeHtml(route.name)}</b><br><span style="font-size:11px">${formatDistance(route.distance_meters)}</span>`);
        line._routeId = route.id;
        line.addTo(routesLayer);
        return line;
    }

    function renderEndMarker(lat, lng, name) {
        if (endMarker) map.removeLayer(endMarker);
        endMarker = L.marker([lat, lng], { icon: pinIcon('pin-end', 'fa-flag-checkered') });
        endMarker.bindPopup(`<b>${escapeHtml(name || 'Ending Site')}</b>`);
        endMarker.addTo(map);
    }

    function formatDistance(m) {
        m = parseFloat(m || 0);
        return m >= 1000 ? (m / 1000).toFixed(2) + ' km' : m.toFixed(0) + ' m';
    }

    function escapeHtml(s) {
        return (s || '').toString().replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    /* ---------------- MODE SWITCH ---------------- */
    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            mode = this.dataset.mode;

            document.getElementById('routeActions').classList.toggle('hidden', mode !== 'route');
            document.getElementById('routeBanner').classList.toggle('hidden', mode !== 'route');

            if (mode !== 'route') resetDrawing();
        });
    });

    function onMapClick(latlng) {
        if (mode === 'tag') {
            pendingLatLng = latlng;
            openPointSheet(latlng.lat, latlng.lng);
        } else if (mode === 'route') {
            addRouteVertex([latlng.lat, latlng.lng]);
        } else if (mode === 'end') {
            openEndSheet(latlng.lat, latlng.lng);
        }
    }

    /* ---------------- GPS ---------------- */
    document.getElementById('btnLocate').addEventListener('click', function () {
        getCurrentPosition(function (pos) {
            map.setView([pos.coords.latitude, pos.coords.longitude], 18);
            showGpsPill(pos.coords.accuracy);
        });
    });

    function getCurrentPosition(cb) {
        if (!navigator.geolocation) {
            Swal.fire({ icon: 'error', title: 'GPS tidak tersedia', text: 'Perangkat tidak mendukung geolocation.' });
            return;
        }
        navigator.geolocation.getCurrentPosition(cb, function (err) {
            Swal.fire({ icon: 'error', title: 'Gagal ambil lokasi', text: err.message });
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 3000 });
    }

    function showGpsPill(accuracy) {
        document.getElementById('gpsPill').classList.remove('hidden');
        document.getElementById('gpsAccuracyText').textContent = 'Akurasi ±' + Math.round(accuracy) + 'm';
        clearTimeout(window._gpsPillTimeout);
        window._gpsPillTimeout = setTimeout(() => document.getElementById('gpsPill').classList.add('hidden'), 4000);
    }

    /* ---------------- POINT SHEET ---------------- */
    let selectedType = 'tiang_eksisting';
    let selectedCatuan = null;

    document.querySelectorAll('.point-type-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            selectedType = this.dataset.type;
            document.querySelectorAll('.point-type-btn').forEach(b => {
                b.classList.remove('border-blue-600', 'bg-blue-50', 'text-blue-700');
                b.classList.add('border-slate-200', 'text-slate-600');
            });
            this.classList.remove('border-slate-200', 'text-slate-600');
            this.classList.add('border-blue-600', 'bg-blue-50', 'text-blue-700');
            document.getElementById('catuanTypeWrap').classList.toggle('hidden', selectedType !== 'catuan');
        });
    });

    document.querySelectorAll('.catuan-type-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            selectedCatuan = this.dataset.catuan;
            document.querySelectorAll('.catuan-type-btn').forEach(b => {
                b.classList.remove('border-blue-600', 'bg-blue-50', 'text-blue-700');
                b.classList.add('border-slate-200', 'text-slate-600');
            });
            this.classList.remove('border-slate-200', 'text-slate-600');
            this.classList.add('border-blue-600', 'bg-blue-50', 'text-blue-700');
        });
    });

    function openPointSheet(lat, lng) {
        document.getElementById('pointForm').reset();
        document.getElementById('pointLat').value = lat ? lat.toFixed(7) : '';
        document.getElementById('pointLng').value = lng ? lng.toFixed(7) : '';
        toggleSheet('pointSheet', true);
    }

    document.getElementById('btnCancelPoint').addEventListener('click', () => toggleSheet('pointSheet', false));

    document.getElementById('btnUseGpsForPoint').addEventListener('click', function () {
        getCurrentPosition(function (pos) {
            document.getElementById('pointLat').value = pos.coords.latitude.toFixed(7);
            document.getElementById('pointLng').value = pos.coords.longitude.toFixed(7);
            showGpsPill(pos.coords.accuracy);
        });
    });

    document.getElementById('pointForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const fd = new FormData();
        fd.append('type', selectedType);
        if (selectedType === 'catuan') fd.append('catuan_type', selectedCatuan || 'ODP');
        fd.append('name', document.getElementById('pointName').value);
        fd.append('latitude', document.getElementById('pointLat').value);
        fd.append('longitude', document.getElementById('pointLng').value);
        fd.append('notes', document.getElementById('pointNotes').value);
        const photo = document.getElementById('pointPhoto').files[0];
        if (photo) fd.append('photo', photo);

        fetch(ROUTES.pointStore, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd,
        }).then(r => r.json()).then(data => {
            if (!data.success) throw new Error('Gagal menyimpan');
            renderPointMarker(data.point);
            addPointToList(data.point);
            updateStats();
            toggleSheet('pointSheet', false);
            toastOk('Titik berhasil ditandai');
        }).catch(() => {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Titik gagal disimpan. Cek koneksi internet.' });
        });
    });

    /* ---------------- ROUTE DRAWING ---------------- */
    function addRouteVertex(latlng) {
        drawingPath.push(latlng);
        document.getElementById('routeVertexCount').textContent = drawingPath.length;
        document.getElementById('routeSheetCount').textContent = drawingPath.length;

        if (drawingPolyline) map.removeLayer(drawingPolyline);
        drawingPolyline = L.polyline(drawingPath, { color: '#ea580c', weight: 4, dashArray: '6 6' }).addTo(map);
    }

    function resetDrawing() {
        drawingPath = [];
        if (drawingPolyline) { map.removeLayer(drawingPolyline); drawingPolyline = null; }
        document.getElementById('routeVertexCount').textContent = '0';
    }

    document.getElementById('btnRouteGps').addEventListener('click', function () {
        getCurrentPosition(function (pos) {
            addRouteVertex([pos.coords.latitude, pos.coords.longitude]);
            map.panTo([pos.coords.latitude, pos.coords.longitude]);
            showGpsPill(pos.coords.accuracy);
        });
    });

    document.getElementById('btnRouteUndo').addEventListener('click', function () {
        drawingPath.pop();
        if (drawingPolyline) map.removeLayer(drawingPolyline);
        drawingPolyline = drawingPath.length ? L.polyline(drawingPath, { color: '#ea580c', weight: 4, dashArray: '6 6' }).addTo(map) : null;
        document.getElementById('routeVertexCount').textContent = drawingPath.length;
    });

    document.getElementById('btnRouteFinish').addEventListener('click', function () {
        if (drawingPath.length < 2) {
            Swal.fire({ icon: 'warning', title: 'Minimal 2 titik', text: 'Tambahkan minimal 2 titik untuk membentuk rute.' });
            return;
        }
        document.getElementById('routeSheetCount').textContent = drawingPath.length;
        toggleSheet('routeSheet', true);
    });

    document.getElementById('btnCancelRoute').addEventListener('click', () => toggleSheet('routeSheet', false));

    document.getElementById('btnSaveRoute').addEventListener('click', function () {
        fetch(ROUTES.routeStore, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: document.getElementById('routeName').value, path: drawingPath }),
        }).then(r => r.json()).then(data => {
            if (!data.success) throw new Error('Gagal');
            renderRouteLine(data.route);
            addRouteToList(data.route);
            updateStats();
            resetDrawing();
            toggleSheet('routeSheet', false);
            toastOk('Rute kabel berhasil disimpan');
        }).catch(() => {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Rute gagal disimpan. Cek koneksi internet.' });
        });
    });

    /* ---------------- ENDING SITE ---------------- */
    function openEndSheet(lat, lng) {
        document.getElementById('endLat').value = lat ? lat.toFixed(7) : '';
        document.getElementById('endLng').value = lng ? lng.toFixed(7) : '';
        toggleSheet('endSheet', true);
    }

    document.getElementById('btnCancelEnd').addEventListener('click', () => toggleSheet('endSheet', false));

    document.getElementById('btnUseGpsForEnd').addEventListener('click', function () {
        getCurrentPosition(function (pos) {
            document.getElementById('endLat').value = pos.coords.latitude.toFixed(7);
            document.getElementById('endLng').value = pos.coords.longitude.toFixed(7);
            showGpsPill(pos.coords.accuracy);
        });
    });

    document.getElementById('btnSaveEnd').addEventListener('click', function () {
        const lat = parseFloat(document.getElementById('endLat').value);
        const lng = parseFloat(document.getElementById('endLng').value);
        if (isNaN(lat) || isNaN(lng)) {
            Swal.fire({ icon: 'warning', title: 'Koordinat belum lengkap' });
            return;
        }
        fetch(ROUTES.endingSite, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ ending_site_lat: lat, ending_site_lng: lng, ending_site_name: document.getElementById('endName').value }),
        }).then(r => r.json()).then(data => {
            if (!data.success) throw new Error('Gagal');
            renderEndMarker(lat, lng, document.getElementById('endName').value);
            toggleSheet('endSheet', false);
            toastOk('Titik ending site tersimpan');
        }).catch(() => {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Ending site gagal disimpan.' });
        });
    });

    /* ---------------- LISTS ---------------- */
    const pointsListEl = document.getElementById('pointsList');
    const routesListEl = document.getElementById('routesList');

    function typeLabel(p) {
        if (p.type === 'catuan') return 'Catuan ' + (p.catuan_type || '');
        return 'Tiang Eksisting';
    }

    function typeBadgeClass(p) {
        if (p.type === 'catuan') {
            return { ODC: 'bg-red-100 text-red-700', ODP: 'bg-amber-100 text-amber-700', JC: 'bg-purple-100 text-purple-700' }[p.catuan_type] || 'bg-amber-100 text-amber-700';
        }
        return 'bg-blue-100 text-blue-700';
    }

    function addPointToList(p) {
        const row = document.createElement('div');
        row.className = 'bg-white rounded-xl border border-slate-100 p-3 flex items-center gap-3';
        row.dataset.pointRow = p.id;
        row.innerHTML = `
            <div class="w-9 h-9 rounded-lg ${typeBadgeClass(p)} flex items-center justify-center shrink-0">
                <i class="fa-solid ${p.type === 'catuan' ? 'fa-box-archive' : 'fa-tower-broadcast'} text-xs"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-black text-slate-800 truncate">${escapeHtml(p.name) || typeLabel(p)}</p>
                <p class="text-[10px] text-slate-400 font-mono">${parseFloat(p.latitude).toFixed(5)}, ${parseFloat(p.longitude).toFixed(5)}</p>
            </div>
            <button class="btn-delete-point w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center shrink-0" data-id="${p.id}">
                <i class="fa-solid fa-trash text-xs"></i>
            </button>`;
        pointsListEl.prepend(row);
    }

    function addRouteToList(r) {
        const row = document.createElement('div');
        row.className = 'bg-white rounded-xl border border-slate-100 p-3 flex items-center gap-3';
        row.dataset.routeRow = r.id;
        row.innerHTML = `
            <div class="w-9 h-9 rounded-lg bg-orange-100 text-orange-700 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-route text-xs"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-black text-slate-800 truncate">${escapeHtml(r.name)}</p>
                <p class="text-[10px] text-slate-400">${formatDistance(r.distance_meters)} &middot; ${(r.path || []).length} titik</p>
            </div>
            <button class="btn-delete-route w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center shrink-0" data-id="${r.id}">
                <i class="fa-solid fa-trash text-xs"></i>
            </button>`;
        routesListEl.prepend(row);
    }

    document.addEventListener('click', function (e) {
        const delPoint = e.target.closest('.btn-delete-point');
        const delRoute = e.target.closest('.btn-delete-route');

        if (delPoint) {
            const id = delPoint.dataset.id;
            Swal.fire({ icon: 'warning', title: 'Hapus titik ini?', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Hapus' }).then(res => {
                if (!res.isConfirmed) return;
                fetch(`${ROUTES.pointDestroyBase}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(() => {
                        document.querySelector(`[data-point-row="${id}"]`)?.remove();
                        markersLayer.eachLayer(m => { if (m._pointId == id) markersLayer.removeLayer(m); });
                        updateStats();
                        toastOk('Titik dihapus');
                    });
            });
        }

        if (delRoute) {
            const id = delRoute.dataset.id;
            Swal.fire({ icon: 'warning', title: 'Hapus rute ini?', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Hapus' }).then(res => {
                if (!res.isConfirmed) return;
                fetch(`${ROUTES.routeDestroyBase}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(() => {
                        document.querySelector(`[data-route-row="${id}"]`)?.remove();
                        routesLayer.eachLayer(l => { if (l._routeId == id) routesLayer.removeLayer(l); });
                        updateStats();
                        toastOk('Rute dihapus');
                    });
            });
        }
    });

    document.querySelectorAll('.list-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.list-tab').forEach(t => t.classList.remove('border-blue-600', 'text-blue-700'));
            document.querySelectorAll('.list-tab').forEach(t => t.classList.add('border-transparent', 'text-slate-400'));
            this.classList.remove('border-transparent', 'text-slate-400');
            this.classList.add('border-blue-600', 'text-blue-700');

            document.getElementById('pointsList').classList.toggle('hidden', this.dataset.tab !== 'points');
            document.getElementById('routesList').classList.toggle('hidden', this.dataset.tab !== 'routes');
        });
    });

    /* ---------------- STATS ---------------- */
    function updateStats() {
        const pointRows = document.querySelectorAll('[data-point-row]');
        document.getElementById('statTiang').textContent = [...pointRows].filter(r => r.querySelector('.fa-tower-broadcast')).length;
        document.getElementById('statCatuan').textContent = [...pointRows].filter(r => r.querySelector('.fa-box-archive')).length;
        document.getElementById('statRute').textContent = document.querySelectorAll('[data-route-row]').length;

        // Jarak dihitung ulang dari layer polyline yang aktif di peta
        let sum = 0;
        routesLayer.eachLayer(l => {
            const latlngs = l.getLatLngs();
            for (let i = 0; i < latlngs.length - 1; i++) sum += latlngs[i].distanceTo(latlngs[i + 1]);
        });
        document.getElementById('statJarak').textContent = formatDistance(sum);
    }

    /* ---------------- SHEETS ---------------- */
    function toggleSheet(id, show) {
        const el = document.getElementById(id);
        el.classList.toggle('hidden', !show);
        el.classList.toggle('flex', show);
    }

    document.querySelectorAll('.sheet-backdrop').forEach(sheet => {
        sheet.addEventListener('click', function (e) {
            if (e.target === sheet) sheet.classList.add('hidden');
        });
    });

    function toastOk(msg) {
        const root = document.getElementById('flashToastRoot');
        const toast = document.createElement('div');
        toast.className = 'bg-emerald-600 text-white p-3 rounded-2xl text-xs font-bold shadow-xl flex items-center gap-2';
        toast.innerHTML = `<i class="fa-solid fa-circle-check"></i><span>${msg}</span>`;
        root.appendChild(toast);
        setTimeout(() => { toast.style.transition = 'opacity .4s'; toast.style.opacity = '0'; }, 1800);
        setTimeout(() => toast.remove(), 2300);
    }

    /* ---------------- MENU / COMPLETE / DELETE ---------------- */
    document.getElementById('btnSurveyMenu')?.addEventListener('click', function () {
        Swal.fire({
            title: 'Opsi Survey',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Hapus Survey',
            confirmButtonColor: '#dc2626',
            denyButtonText: 'Unduh KML',
            cancelButtonText: 'Tutup',
        }).then(res => {
            if (res.isConfirmed) {
                Swal.fire({ icon: 'warning', title: 'Yakin hapus survey ini?', text: 'Semua titik dan rute akan ikut terhapus.', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Hapus' }).then(r2 => {
                    if (r2.isConfirmed) document.getElementById('deleteForm').submit();
                });
            } else if (res.isDenied) {
                window.location.href = ROUTES.kml;
            }
        });
    });

    document.getElementById('btnCompleteSurvey')?.addEventListener('click', function () {
        Swal.fire({
            icon: 'question',
            title: 'Selesaikan Survey?',
            text: 'File KML final akan dibuat dari seluruh titik & rute yang sudah ditandai.',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            confirmButtonText: 'Ya, Selesaikan',
        }).then(res => {
            if (res.isConfirmed) document.getElementById('completeForm').submit();
        });
    });

    /* ---------------- INIT ---------------- */
    initMap();

    // Render list awal
    initialPoints.forEach(addPointToList);
    initialRoutes.forEach(addRouteToList);
})();
</script>
@endpush
