@extends('layouts.pm')

@section('content')
<div class="space-y-6 relative">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Map Monitoring & Clustering</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Visualisasi sebaran site Project Area 3</p>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    <style>
        .leaflet-control-layers {
            border-radius: 12px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
            padding: 8px 12px !important;
            font-family: inherit !important;
            font-size: 13px !important;
            font-weight: 600 !important;
        }
    </style>

    <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm relative">
        
        <button id="floating-clear-btn" onclick="clearActiveLayers()" 
                class="hidden absolute top-6 right-6 z-[400] bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-lg border-2 border-white transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            <span id="clear-btn-text">Bersihkan Peta</span>
        </button>

        <div id="map" class="w-full h-[650px] rounded-xl z-10 border border-gray-100 dark:border-gray-800"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-omnivore/0.3.4/leaflet-omnivore.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Inisialisasi Peta - Set koordinat default berpusat di Jateng, DIY, Jatim, Bali, Nusra
        const map = L.map('map').setView([-8.000000, 115.500000], 7);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const layers = {
            'OSP (Fiber)': L.markerClusterGroup(),
            'NODE B (Site)': L.markerClusterGroup(),
            'HEM': L.markerClusterGroup(),
            'OLO (Partner)': L.markerClusterGroup(),
            'Eksternal': L.markerClusterGroup()
        };

        Object.keys(layers).forEach(key => map.addLayer(layers[key]));

        const programColors = {
            'OSP': '#2563eb',
            'NODE B': '#7c3aed',
            'HEM': '#d97706',
            'OLO': '#16a34a',
            'Konstruksi Eksternal': '#dc2626'
        };

        // Variabel Status Komponen Aktif
        let currentKmlLayer = null;
        let currentRadiusLayer = null;
        let measureOrigin = null; // Menyimpan Titik Proyek A awal
        let measureLine = null;   // Menyimpan Garis Penghubung A ke B
        
        const floatingBtn = document.getElementById('floating-clear-btn');
        const clearBtnText = document.getElementById('clear-btn-text');

        fetch("{{ route('pm.api.map.data') }}")
            .then(response => response.json())
            .then(data => {
                data.forEach((project) => {
                    let lat = parseFloat(project.latitude);
                    let lng = parseFloat(project.longitude);

                    if (lat > 90 || lat < -90) {
                        let temp = lat; lat = lng; lng = temp;
                    }

                    if (isNaN(lat) || isNaN(lng)) return;

                    const color = programColors[project.program] || '#6b7280';

                    const customIcon = L.divIcon({
                        html: `<div style="background-color: ${color}; width: 14px; height: 14px; border: 2px solid white; border-radius: 50%; box-shadow: 0 0 8px rgba(0,0,0,0.35);"></div>`,
                        className: 'custom-pin-container',
                        iconSize: [14, 14]
                    });

                    // Isi Popup dengan tambahan menu pengukur jarak antar-site
                    const popupContent = `
                        <div class="p-1 font-sans" style="min-width: 240px;">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded" style="background-color: ${color}20; color: ${color}">
                                ${project.program}
                            </span>
                            <h4 class="text-sm font-bold text-gray-900 mt-2 mb-1">${project.project_name}</h4>
                            <p class="text-xs text-gray-500 mb-1"><b>ID IHLD:</b> ${project.id_ihld}</p>
                            <p class="text-xs text-gray-500 mb-1"><b>Branch - STO:</b> ${project.branch} - ${project.sto}</p>
                            <p class="text-xs text-gray-500 mb-2"><b>Status Progres:</b> <span class="capitalize text-indigo-600 font-semibold">${project.status_progress === 'finishing' ? 'Finish' : project.status_progress}</span></p>
                            <hr class="my-2 border-gray-100" />
                            
                            <div class="space-y-1.5">
                                <button onclick="loadKmlLayer('${project.kml_url}')" 
                                        class="w-full text-center text-[11px] bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1.5 px-2 rounded-lg transition shadow-sm">
                                    Tampilkan Jalur KML
                                </button>
                                
                                <button onclick="toggleDeliveryRadius(${lat}, ${lng})" 
                                        class="w-full text-center text-[11px] bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1.5 px-2 rounded-lg transition shadow-sm">
                                    🎯 Cek Radius 10KM
                                </button>

                                <button onclick="setMeasureOrigin(${lat}, ${lng}, '${project.project_name.replace(/'/g, "\\'")}')" 
                                        class="w-full text-center text-[11px] bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-2 rounded-lg transition shadow-sm">
                                    Jadikan Titik Awal Ukur
                                </button>
                            </div>
                        </div>
                    `;

                    const marker = L.marker([lat, lng], { icon: customIcon }).bindPopup(popupContent);

                    // TRIGGER UTAMA: Jika mode pengukur jarak aktif, klik pin kedua langsung menghitung jarak
                    marker.on('click', function (e) {
                        if (measureOrigin) {
                            const destLatLng = e.target.getLatLng();
                            const originLatLng = L.latLng(measureOrigin.lat, measureOrigin.lng);
                            
                            // Hitung jarak presisi dalam satuan meter
                            const distance = originLatLng.distanceTo(destLatLng);
                            
                            let readableDistance = "";
                            if (distance >= 1000) {
                                readableDistance = (distance / 1000).toFixed(2) + " KM";
                            } else {
                                readableDistance = distance.toFixed(0) + " Meter";
                            }

                            // Hapus garis lama jika PM ingin mengukur ke titik lain lagi
                            if (measureLine) {
                                map.removeLayer(measureLine);
                            }

                            // Gambar garis putus-putus biru premium menghubungkan Project 1 ke Project 2
                            measureLine = L.polyline([originLatLng, destLatLng], {
                                color: '#2563eb',
                                weight: 3,
                                dashArray: '6, 8',
                                opacity: 0.85
                            }).addTo(map);

                            // Sematkan Label Angka Jarak Tepat di Tengah Garis Penghubung
                            measureLine.bindTooltip(`<b>Jarak :</b> ${readableDistance}`, {
                                permanent: true,
                                direction: 'center',
                                className: 'bg-white dark:bg-gray-950 px-2.5 py-1.5 text-xs font-bold rounded-xl shadow-md border border-blue-200 text-blue-700 whitespace-nowrap z-[500]'
                            }).openTooltip();
                            
                            console.log(`Jarak terhitung dari ${measureOrigin.name}: ${readableDistance}`);
                        }
                    });

                    if (project.program === 'OSP') layers['OSP (Fiber)'].addLayer(marker);
                    else if (project.program === 'NODE B') layers['NODE B (Site)'].addLayer(marker);
                    else if (project.program === 'HEM') layers['HEM'].addLayer(marker);
                    else if (project.program === 'OLO') layers['OLO (Partner)'].addLayer(marker);
                    else layers['Eksternal'].addLayer(marker);
                });

                L.control.layers(null, layers, { collapsed: false }).addTo(map);
            })
            .catch(error => console.error("Gagal memuat API koordinat peta:", error));

        // Fungsi Kunci Titik Awal Pengukuran Jarak (Titik Proyek 1)
        window.setMeasureOrigin = function (lat, lng, name) {
            measureOrigin = { lat: lat, lng: lng, name: name };
            
            // Perbarui label tombol melayang untuk memberi tanda petunjuk bagi PM
            clearBtnText.innerText = `Batal Ukur (Dari: ${name})`;
            floatingBtn.classList.remove('hidden');
            
            // Tutup popup proyek 1 secara otomatis agar PM leluasa mengklik proyek 2
            map.closePopup();
        };

        // Fungsi Tampilkan KML Vektor
        window.loadKmlLayer = function (kmlUrl) {
            clearActiveLayers();

            currentKmlLayer = omnivore.kml(kmlUrl)
                .on('ready', function() {
                    const bounds = currentKmlLayer.getBounds();
                    if (bounds.isValid()) {
                        map.fitBounds(bounds, { padding: [40, 40] });
                    }
                    clearBtnText.innerText = "Bersihkan Peta";
                    floatingBtn.classList.remove('hidden');
                })
                .on('error', function(err) {
                    console.error("Gagal merender KML:", err);
                    alert("Gagal memuat rute KML. Pastikan berkas tersedia di storage.");
                })
                .addTo(map);
        };

        // Fungsi Tampilkan Radius Jarak
        window.toggleDeliveryRadius = function (lat, lng) {
            clearActiveLayers();

            currentRadiusLayer = L.circle([lat, lng], {
                color: '#10b981',
                fillColor: '#10b981',
                fillOpacity: 0.15,
                radius: 10000 
            }).addTo(map);

            map.fitBounds(currentRadiusLayer.getBounds(), { padding: [20, 20] });
            clearBtnText.innerText = "Bersihkan Peta";
            floatingBtn.classList.remove('hidden');
        };

        // Fungsi Reset/Membersihkan Peta Total
        window.clearActiveLayers = function () {
            if (currentKmlLayer) { map.removeLayer(currentKmlLayer); currentKmlLayer = null; }
            if (currentRadiusLayer) { map.removeLayer(currentRadiusLayer); currentRadiusLayer = null; }
            if (measureLine) { map.removeLayer(measureLine); measureLine = null; }
            
            measureOrigin = null;
            clearBtnText.innerText = "Bersihkan Peta";
            floatingBtn.classList.add('hidden');
        };
    });
</script>
@endsection