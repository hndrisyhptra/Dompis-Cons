@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Map Monitoring
            </h1>
            <p class="text-sm text-gray-500">
                Sebaran project dan lokasi upload evidence
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-sm text-gray-500">Map Project</p>
            <h2 class="text-2xl font-bold text-blue-700 mt-1">
                {{ $projects->count() }}
            </h2>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-sm text-gray-500">Evidence GPS</p>
            <h2 class="text-2xl font-bold text-green-700 mt-1">
                {{ $evidences->count() }}
            </h2>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-sm text-gray-500">Upload Pending</p>
            <h2 class="text-2xl font-bold text-yellow-600 mt-1">
                {{ $evidences->where('status', 'pending')->count() }}
            </h2>
        </div>

    </div>

    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div id="map" class="w-full h-[650px]"></div>

    </div>

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const map = L.map('map').setView([-7.2575, 112.7521], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const projectIcon = L.divIcon({
        className: '',
        html: `<div style="background:#2563eb;color:white;width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-weight:bold;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.25)">P</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14]
    });

    const evidenceIcon = L.divIcon({
        className: '',
        html: `<div style="background:#16a34a;color:white;width:22px;height:22px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;border:2px solid white;box-shadow:0 2px 8px rgba(0,0,0,.25)">E</div>`,
        iconSize: [22, 22],
        iconAnchor: [11, 11]
    });

    const projects = @json($projects);
    const evidences = @json($evidences);

    projects.forEach(project => {
        const marker = L.marker([project.latitude, project.longitude], {
            icon: projectIcon
        }).addTo(map);

        const waspang = project.assignments?.[0]?.waspang?.name ?? '-';

        marker.bindPopup(`
            <div style="min-width:220px">
                <b>${project.project_name}</b><br>
                <small>${project.branch ?? '-'} · ${project.sto ?? '-'}</small><br><br>
                <b>Waspang:</b> ${waspang}<br>
                <b>Status:</b> ${project.status ?? '-'}<br>
                <b>Alamat:</b> ${project.location_address ?? '-'}
            </div>
        `);
    });

    evidences.forEach(evidence => {
        const marker = L.marker([evidence.latitude, evidence.longitude], {
            icon: evidenceIcon
        }).addTo(map);

        const img = evidence.file_path
            ? `<img src="/storage/${evidence.file_path}" style="width:100%;height:100px;object-fit:cover;border-radius:10px;margin-top:8px">`
            : '';

        marker.bindPopup(`
            <div style="min-width:220px">
                <b>${evidence.project?.project_name ?? 'Evidence'}</b><br>
                <small>${evidence.stage} · ${evidence.evidence_type}</small><br><br>
                <b>Status:</b> ${evidence.status}<br>
                <b>Uploader:</b> ${evidence.uploader?.name ?? '-'}<br>
                ${img}
            </div>
        `);
    });

});
</script>

@endsection