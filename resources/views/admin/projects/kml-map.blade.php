@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                View KML
            </h1>
            <p class="text-sm text-gray-500">
                {{ $project->lop_name ?? $project->project_name ?? 'Detail Peta KML' }}
            </p>
        </div>

        <a href="{{ route('projects.index') }}"
           class="h-10 px-4 inline-flex items-center rounded-xl border border-gray-300 text-sm font-bold">
            Kembali
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div id="map" class="w-full" style="height: 650px;"></div>
    </div>

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-omnivore/leaflet-omnivore.min.js"></script>
<script src="https://unpkg.com/togeojson@0.16.0"></script>

<script>
document.addEventListener('DOMContentLoaded', async function () {
    const map = L.map('map').setView([-2.5489, 118.0149], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const kmlUrl = @json($kmlUrl);

    const response = await fetch(kmlUrl);
    const kmlText = await response.text();

    const parser = new DOMParser();
    const kml = parser.parseFromString(kmlText, 'text/xml');
    const geojson = toGeoJSON.kml(kml);

    let totalLength = 0;

    const kmlLayer = L.geoJSON(geojson, {
        pointToLayer: function (feature, latlng) {
            return L.marker(latlng, {
                icon: L.divIcon({
                    className: '',
                    html: `
                        <div style="
                            width: 22px;
                            height: 22px;
                            background: #2563eb;
                            border: 3px solid white;
                            border-radius: 999px;
                            box-shadow: 0 2px 8px rgba(0,0,0,.35);
                        "></div>
                    `,
                    iconSize: [22, 22],
                    iconAnchor: [11, 11]
                })
            });
        },

        style: function (feature) {
            if (feature.geometry.type === 'LineString' || feature.geometry.type === 'MultiLineString') {
                return {
                    color: '#2563eb',
                    weight: 4,
                    opacity: 0.9
                };
            }

            if (feature.geometry.type === 'Polygon' || feature.geometry.type === 'MultiPolygon') {
                return {
                    color: '#16a34a',
                    weight: 3,
                    opacity: 0.9,
                    fillColor: '#22c55e',
                    fillOpacity: 0.25
                };
            }

            return {};
        },

        onEachFeature: function (feature, layer) {
            const props = feature.properties || {};

            let lengthText = '';

            if (feature.geometry.type === 'LineString') {
                const length = calculateLineLength(feature.geometry.coordinates);
                totalLength += length;
                lengthText = `<br><b>Panjang:</b> ${formatLength(length)}`;
            }

            if (feature.geometry.type === 'MultiLineString') {
                let multiLength = 0;

                feature.geometry.coordinates.forEach(coords => {
                    multiLength += calculateLineLength(coords);
                });

                totalLength += multiLength;
                lengthText = `<br><b>Panjang:</b> ${formatLength(multiLength)}`;
            }

            const name = props.name || 'Tanpa Nama';
            const description = props.description || '-';

            layer.bindPopup(`
                <div style="min-width:220px">
                    <b>${name}</b>
                    <div style="margin-top:6px;font-size:12px;">
                        ${description}
                        ${lengthText}
                    </div>
                </div>
            `);

            if (name && feature.geometry.type !== 'Point') {
                layer.bindTooltip(name, {
                    permanent: false,
                    direction: 'top'
                });
            }
        }
    }).addTo(map);

    if (kmlLayer.getBounds().isValid()) {
        map.fitBounds(kmlLayer.getBounds(), {
            padding: [30, 30]
        });
    }

    L.control.layers({
        'OpenStreetMap': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'Satellite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri'
        })
    }, {
        'KML Layer': kmlLayer
    }).addTo(map);

    const totalInfo = L.control({ position: 'bottomleft' });

    totalInfo.onAdd = function () {
        const div = L.DomUtil.create('div', 'bg-white rounded-xl shadow px-4 py-3 text-sm');
        div.innerHTML = `<b>Total Panjang KML:</b><br>${formatLength(totalLength)}`;
        return div;
    };

    totalInfo.addTo(map);
});

    function calculateLineLength(coords)
    {
        let total = 0;

        for (let i = 0; i < coords.length - 1; i++) {
            const from = L.latLng(coords[i][1], coords[i][0]);
            const to = L.latLng(coords[i + 1][1], coords[i + 1][0]);

            total += from.distanceTo(to);
        }

        return total;
    }

    function formatLength(meter)
    {
        if (meter >= 1000) {
            return (meter / 1000).toFixed(2) + ' km';
        }

        return meter.toFixed(0) + ' m';
    }
</script>

@endsection