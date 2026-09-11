@extends('layouts.waspang')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#F8FAFC] pb-28">

    @php
        // Stage 4d (refactor Persiapan 5 sub-step): lihat
        // ANALISA_REFACTOR_PERSIAPAN.md bag. Q.2 utk spec lengkap. Semua
        // variabel di bawah ($summary, $seq, $stageCode, $step, dst) sudah
        // disiapkan controller (WaspangController::persiapan()) -- view ini
        // TIDAK menghitung ulang status dari evidence, murni menampilkan
        // posisi NYATA LOP (project_stages.sequence) supaya selalu sinkron.
        $progressPercent = $summary['progress'] ?? 0;
    @endphp

    {{-- HEADER & STEPPER --}}
    @include('waspang.partials.stepper')

    {{-- STATUS CARD: Status LOP + Progress --}}
    <div class="px-4 mt-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Status LOP</p>
                    <p class="text-sm font-black text-slate-900 mt-0.5">{{ $summary['effectiveStageLabel'] ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Progress</p>
                    <p class="text-lg font-black text-[#1565D8] mt-0.5">{{ $progressPercent }}%</p>
                </div>
            </div>
            <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full bg-[#1565D8] rounded-full transition-all" style="width: {{ $progressPercent }}%"></div>
            </div>
        </div>
    </div>

    {{-- PROJECT INFO CARD --}}
    <div class="px-4 mt-3">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="mb-3">
                <p class="text-xs text-slate-400 font-medium">Nama LOP</p>
                <p class="text-sm font-bold text-slate-900 break-words mt-0.5">{{ $project->project_name }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 border-t border-slate-50 pt-3">
                <div>
                    <p class="text-xs text-slate-400 font-medium">STO</p>
                    <p class="text-xs font-bold text-slate-800 font-mono mt-0.5">{{ $project->lop?->sto ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-medium">Branch</p>
                    <p class="text-xs font-bold text-slate-800 mt-0.5">{{ $project->lop?->branch ?? '-' }}</p>
                </div>
                <div class="col-span-2 border-t border-slate-50 pt-2">
                    <p class="text-xs text-slate-400 font-medium">Mitra Pelaksana</p>
                    <p class="text-xs font-bold text-slate-800 mt-0.5 break-words">{{ $project->lop?->mitra_name ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- SUB-STEP ACCORDIONS: Inisiasi / Survey / Perizinan / --}}
    {{-- Material Delivery                                   --}}
    {{-- ========================================================= --}}
    <div class="px-4 mt-6 space-y-3">

        <div class="flex items-center justify-between mb-1">
            <div>
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Step 1 · Persiapan</h2>
                <p class="text-[11px] text-slate-500">Lengkapi sub-step berikut secara berurutan</p>
            </div>
        </div>

        {{-- SUB-STEP 1: INISIASI (read-only, informasional) --}}
        <div x-data="{ open: false }" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <button type="button" @click="open = !open" class="w-full p-4 flex items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 bg-emerald-50 text-emerald-600">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">1. Inisiasi</h3>
                        <p class="text-[10.5px] font-medium text-slate-400 mt-0.5">PID & BOQ awal diupload Admin</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-700">Selesai</span>
                    <i class="fa-solid text-[10px] text-slate-400 transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>
            </button>
            <div x-show="open" x-transition x-cloak class="border-t border-slate-50 bg-slate-50/30 p-4">
                <p class="text-xs text-slate-500 leading-relaxed">Project ini sudah diinisiasi &amp; ditugaskan (assign) ke Anda oleh Admin. Dokumen PID dan BOQ awal sudah tersedia di sistem -- lanjutkan ke sub-step Survey untuk mulai input BOQ lapangan.</p>
            </div>
        </div>

        @include('waspang.partials.survey-workflow')

        <div x-data="{ open: {{ $step['perizinan']['active'] ? 'true' : 'false' }}, selesai: false }" class="bg-white rounded-2xl border overflow-hidden shadow-xs {{ $step['perizinan']['active'] ? 'border-[#1565D8]/40' : 'border-slate-200' }}">
            <button type="button" @click="open = !open" class="w-full p-4 flex items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0
                        {{ $step['perizinan']['done'] ? 'bg-emerald-50 text-emerald-600' : ($step['perizinan']['active'] ? 'bg-blue-50 text-[#1565D8]' : 'bg-slate-50 text-slate-400') }}">
                        @if($step['perizinan']['done'])
                            <i class="fa-solid fa-check"></i>
                        @else
                            3
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">3. Perizinan</h3>
                        <p class="text-[10.5px] font-medium text-slate-400 mt-0.5">{{ $lop->permitCategory?->name ?? 'Kategori belum dipilih' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                        {{ $step['perizinan']['done'] ? 'bg-emerald-100 text-emerald-700' : ($step['perizinan']['active'] ? 'bg-blue-100 text-[#1565D8]' : 'bg-slate-100 text-slate-500') }}">
                        {{ $step['perizinan']['done'] ? 'Selesai' : ($step['perizinan']['active'] ? 'Aktif' : 'Menunggu') }}
                    </span>
                    <i class="fa-solid text-[10px] text-slate-400 transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>
            </button>

            <div x-show="open" x-transition x-cloak class="border-t border-slate-50 bg-slate-50/30 p-4 space-y-3">

                {{-- RIWAYAT PERIZINAN (Stage 4f -- "Add Perizinan": kategori + kronologi + eviden opsional dalam 1 submission, bisa berkali-kali) --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-[11px] font-black text-slate-600">Riwayat Perizinan</p>
                        @if($step['perizinan']['active'])
                            <button type="button" onclick="openAddPerizinanModal()" class="text-[11px] font-black text-[#1565D8]">
                                <i class="fa-solid fa-plus mr-0.5"></i> Add Perizinan
                            </button>
                        @endif
                    </div>

                    @php $perizinanKronologis = $kronologis->where('stage_code', 'perizinan'); @endphp

                    @if($perizinanKronologis->isEmpty())
                        <p class="text-xs text-slate-400 italic">Belum ada aktivitas Perizinan. Klik "Add Perizinan" untuk menambahkan.</p>
                    @else
                        <div class="space-y-2.5">
                            @foreach($perizinanKronologis as $k)
                                <div class="bg-white rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[9px] font-black uppercase tracking-wide text-[#1565D8] bg-blue-50 px-1.5 py-0.5 rounded">
                                            {{ $k->permitCategory?->name ?? 'Kategori tidak diketahui' }}
                                        </span>
                                        <p class="text-[10px] font-black text-slate-400 shrink-0">{{ optional($k->event_date)->format('d M Y') }}</p>
                                    </div>
                                    <p class="text-[11px] text-slate-600 mt-1.5 leading-relaxed">{{ $k->note }}</p>
                                    <p class="text-[9.5px] text-slate-400 mt-1">{{ $k->creator?->name ?? '-' }}</p>

                                    @if($k->evidences->isNotEmpty())
                                        <div class="mt-2">
                                            @include('waspang.partials.evidence-photo-grid', ['photos' => $k->evidences])
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($step['perizinan']['active'])
                    {{-- PERIZINAN SELESAI -- langsung aksi, tanpa upload tambahan lagi (eviden sudah dilampirkan per entri lewat Add Perizinan) --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <form method="POST" action="{{ route('waspang.perizinan.selesai', $project->id_project) }}" onsubmit="return confirm('Tandai Perizinan selesai & lanjut ke Material Delivery?')">
                            @csrf
                            <button type="submit" @if($perizinanKronologis->isEmpty()) disabled @endif
                                    class="h-11 w-full rounded-xl text-sm font-black transition shadow-sm {{ $perizinanKronologis->isEmpty() ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-[#1565D8] hover:bg-[#0F4FAF] text-white' }}">
                                Perizinan Selesai <i class="fa-solid fa-chevron-right ml-1 text-xs"></i>
                            </button>
                        </form>
                        @if($perizinanKronologis->isEmpty())
                            <p class="text-[10px] text-slate-400 mt-2 text-center">Tambahkan minimal 1x Add Perizinan terlebih dahulu.</p>
                        @endif
                    </div>

                    @include('waspang.partials.step-action-buttons', ['stageCode' => 'perizinan', 'stepLabel' => 'Perizinan', 'showKronologiButton' => false])
                @endif
            </div>
        </div>

        {{-- SUB-STEP 4: MATERIAL DELIVERY --}}
        <div x-data="{ open: {{ $step['material_delivery']['active'] ? 'true' : 'false' }} }" class="bg-white rounded-2xl border overflow-hidden shadow-xs {{ $step['material_delivery']['active'] ? 'border-[#1565D8]/40' : 'border-slate-200' }}">
            <button type="button" @click="open = !open" class="w-full p-4 flex items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0
                        {{ $step['material_delivery']['done'] ? 'bg-emerald-50 text-emerald-600' : ($step['material_delivery']['active'] ? 'bg-blue-50 text-[#1565D8]' : 'bg-slate-50 text-slate-400') }}">
                        @if($step['material_delivery']['done'])
                            <i class="fa-solid fa-check"></i>
                        @else
                            4
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">4. Material Delivery</h3>
                        <p class="text-[10.5px] font-medium text-slate-400 mt-0.5">{{ $materialDeliveryEvidences->count() }} Eviden Terlampir</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                        {{ $step['material_delivery']['done'] ? 'bg-emerald-100 text-emerald-700' : ($step['material_delivery']['active'] ? 'bg-blue-100 text-[#1565D8]' : 'bg-slate-100 text-slate-500') }}">
                        {{ $step['material_delivery']['done'] ? 'Selesai' : ($step['material_delivery']['active'] ? 'Aktif' : 'Menunggu') }}
                    </span>
                    <i class="fa-solid text-[10px] text-slate-400 transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </div>
            </button>

            <div x-show="open" x-transition x-cloak class="border-t border-slate-50 bg-slate-50/30 p-4 space-y-3">
                @include('waspang.partials.evidence-photo-grid', ['photos' => $materialDeliveryEvidences])

                @if($step['material_delivery']['active'])
                    <button type="button" onclick="openUploadModal('material_delivery', 'material_delivery', 'Eviden Material Delivery', false)"
                            class="h-9 w-full rounded-xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-xs font-bold transition shadow-xs">
                        <i class="fa-solid fa-camera mr-1"></i> Upload Eviden (Deskripsi Opsional)
                    </button>

                    <form method="POST" action="{{ route('waspang.material-delivery.finish', $project->id_project) }}" onsubmit="return confirm('Material Delivery selesai? Persiapan akan dianggap tuntas & lanjut ke Instalasi.')">
                        @csrf
                        <button type="submit" class="h-11 w-full rounded-xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-sm font-black transition shadow-sm">
                            Selesai Persiapan <i class="fa-solid fa-chevron-right ml-1 text-xs"></i>
                        </button>
                    </form>

                    @include('waspang.partials.step-action-buttons', ['stageCode' => 'material_delivery', 'stepLabel' => 'Material Delivery'])
                @endif
            </div>
        </div>
    </div>

    {{-- CTA FINAL Step 1 -- FIX: sebelumnya tombol ini langsung POST ke
         finishPersiapanInstalasi() (butuh eviden Barang Tiba & Perizinan yg
         BELUM tentu sudah diupload krn halamannya sendiri belum pernah
         dibuka) dgn label salah "Instalasi" -- padahal Persiapan Instalasi
         adalah STEP 2 TERSENDIRI (lihat waspang/steps/persiapan-instalasi.
         blade.php), Instalasi sebenarnya Step 3. Sekarang jadi LINK biasa
         ke halaman Step 2 tsb (evidence & tombol finish-nya ada DI SANA),
         label & penomoran disamakan ke seluruh step (1 Persiapan -> 2
         Persiapan Instalasi -> 3 Instalasi -> 4 Pengukuran -> 5 Finishing). --}}
    <div class="px-4 mt-4">
        @if($lop->status_progress === 'persiapan_instalasi')
            <a href="{{ route('waspang.projects.persiapan-instalasi', $project->id_project) }}" class="h-11 w-full rounded-xl bg-[#1565D8] text-white inline-flex items-center justify-center text-sm font-bold shadow-sm hover:bg-[#0F4FAF] transition">
                Lanjut Step 2 - Persiapan Instalasi <i class="fa-solid fa-chevron-right ml-2 text-xs"></i>
            </a>
        @elseif($seq !== null && $seq > 6)
            {{-- Sudah pernah lanjut sebelumnya (mis. waspang balik lagi ke halaman ini) --}}
            <a href="{{ route('waspang.projects.instalasi', $project->id_project) }}" class="h-11 w-full rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 inline-flex items-center justify-center text-sm font-bold transition">
                <i class="fa-solid fa-circle-check mr-2"></i> Persiapan Selesai -- Lihat Step 3 Instalasi
            </a>
        @else
            <button disabled class="h-11 w-full rounded-xl bg-slate-200 text-slate-400 inline-flex items-center justify-center text-sm font-bold cursor-not-allowed">
                Lengkapi 4 Sub-step Persiapan Terlebih Dahulu
            </button>
        @endif
    </div>

    {{-- MODAL UPLOAD OVERLAY (generik, dipakai semua sub-step) --}}
    <div id="uploadModal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
        <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            <div class="bg-[#1565D8] text-white px-5 py-4 flex items-start justify-between shrink-0">
                <div>
                    <h2 class="text-lg font-black tracking-tight">Upload Eviden</h2>
                    <p id="selectedBoqName" class="text-xs text-blue-100 mt-1 font-medium break-all line-clamp-1">Nama komponen item BOQ</p>
                </div>
                <button type="button" onclick="closeUploadModal()" class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 text-white font-black text-lg flex items-center justify-center transition">×</button>
            </div>

            <form id="uploadForm" method="POST" action="{{ route('waspang.evidence.upload', $project->id_project) }}" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
                @csrf
                <input type="hidden" name="stage" id="upload_stage" value="persiapan">
                <input type="hidden" name="evidence_type" id="upload_evidence_type">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                {{-- DROPZONE AREA --}}
                <div class="text-xs">
                    <label class="text-xs font-black text-slate-600 block mb-1.5">Pilih/Ambil Eviden</label>
                    <label class="flex flex-col items-center justify-center w-full min-h-[125px] border-2 border-dashed border-blue-300 rounded-2xl bg-blue-50/40 cursor-pointer hover:bg-blue-50 transition p-4">
                        <div class="text-center">
                            <div class="mx-auto w-11 h-11 rounded-xl bg-[#1565D8] text-white flex items-center justify-center text-xl font-black shadow-sm">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                            <p class="mt-2.5 text-xs font-black text-[#0F4FAF]">Pilih Eviden</p>
                            <p id="uploadAcceptHint" class="text-[10px] text-slate-400 mt-0.5">JPG, PNG, WEBP · Auto Compress</p>
                        </div>
                        <input type="file" id="photoInput" accept="image/*" multiple class="hidden">
                    </label>

                    {{-- PREVIEW MULTIPLE IMAGES CONTAINER --}}
                    <div id="previewWrapper" class="mt-3 hidden animate-fade-in">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Preview Eviden (<span id="photoCount">0</span>)</p>
                            <button type="button" id="clearAllPhotos" class="text-[11px] font-bold text-red-600 hover:text-red-700 transition">Hapus Semua</button>
                        </div>
                        <div id="previewContainer" class="grid grid-cols-3 gap-2"></div>
                    </div>
                </div>

                <div class="text-xs">
                    <label class="text-xs font-black text-slate-600 block">Catatan Tambahan / Deskripsi <span class="text-slate-400">(Opsional)</span></label>
                    <textarea name="description" rows="3" placeholder="Tulis keterangan tambahan jika ada..." class="mt-1.5 w-full rounded-2xl border border-slate-300 px-3 py-2 text-xs focus:ring-2 focus:ring-blue-100 focus:border-[#1565D8] outline-none transition resize-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2 shrink-0">
                    <button type="button" onclick="closeUploadModal()" class="h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-black transition">Batal</button>
                    <button type="submit" class="h-11 rounded-2xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-sm font-black shadow-md transition">Upload</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL KENDALA & KRONOLOGI (universal, dipakai semua sub-step) --}}
    @include('waspang.partials.kendala-modal')
    @include('waspang.partials.kronologi-modal')
    @include('waspang.partials.perizinan-modal')

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/togeojson@0.16.0"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .swal2-container { z-index: 10000 !important; }
</style>
<script>
let selectedFiles = [];
let uploadAcceptsPdf = false;

// TomSelect utk picker designator Survey (Mode Manual) -- lihat catatan
// layouts/waspang.blade.php, versi & sumber sama dgn admin/pm/sdi.
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('surveyAdditionalDesignatorPicker');
    if (picker && typeof TomSelect === 'function' && !picker.tomselect) {
        new TomSelect(picker, {
            create: false,
            placeholder: 'Cari designator tambahan...',
            maxOptions: 1000,
        });
    }
});

// Peta Survey menampilkan versi terbaru secara default. Versi lama tetap bisa
// dipilih sebagai histori, tetapi hanya versi terbaru yang dapat dikonfirmasi.
document.addEventListener('DOMContentLoaded', function () {
    const mapElement = document.getElementById('surveyWorkflowMap');
    const versions = @json($surveyMapVersions ?? collect());

    if (!mapElement || !versions.length || typeof L === 'undefined' || typeof toGeoJSON === 'undefined') {
        document.getElementById('surveyWorkflowMapLoading')?.classList.add('hidden');
        return;
    }

    const versionByKey = Object.fromEntries(versions.map(version => [version.key, version]));
    const latestVersion = versions[versions.length - 1];
    const picker = document.getElementById('surveyMapVersionPicker');
    const label = document.getElementById('surveyMapVersionLabel');
    const loading = document.getElementById('surveyWorkflowMapLoading');
    const confirmButton = document.getElementById('confirmSurveyMapButton');
    const map = L.map(mapElement, { zoomControl: false }).setView([-2.5489, 118.0149], 5);
    let activeLayer = null;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    function setConfirmAvailability(selectedKey) {
        if (!confirmButton) return;

        const viewingLatest = selectedKey === latestVersion.key;
        confirmButton.disabled = !viewingLatest;
        confirmButton.classList.toggle('opacity-40', !viewingLatest);
        confirmButton.classList.toggle('cursor-not-allowed', !viewingLatest);
        const text = confirmButton.querySelector('span');
        if (text && !viewingLatest) text.textContent = 'Hanya Riwayat';
        if (text && viewingLatest) text.textContent = @json($surveyMapConfirmed ? 'Sudah Sesuai' : 'Sesuai');
    }

    async function showVersion(key) {
        const version = versionByKey[key] || latestVersion;
        loading?.classList.remove('hidden');
        label.textContent = version.label;
        setConfirmAvailability(version.key);

        try {
            const response = await fetch(version.url, { headers: { Accept: 'application/vnd.google-earth.kml+xml' } });
            if (!response.ok) throw new Error('Peta tidak dapat dimuat');

            const kml = new DOMParser().parseFromString(await response.text(), 'text/xml');
            const geojson = toGeoJSON.kml(kml);

            if (activeLayer) map.removeLayer(activeLayer);
            activeLayer = L.geoJSON(geojson, {
                pointToLayer: (feature, latlng) => L.circleMarker(latlng, {
                    radius: 6,
                    color: '#ffffff',
                    weight: 2,
                    fillColor: version.source === 'redesign' ? '#059669' : '#2563eb',
                    fillOpacity: 1,
                }),
                style: {
                    color: version.source === 'redesign' ? '#059669' : '#2563eb',
                    weight: 4,
                    opacity: 0.9,
                },
            }).addTo(map);

            const bounds = activeLayer.getBounds();
            if (bounds.isValid()) map.fitBounds(bounds, { padding: [20, 20] });
        } catch (error) {
            loading.innerHTML = '<span class="px-4 text-center text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i> Peta gagal dimuat.</span>';
            return;
        }

        loading?.classList.add('hidden');
    }

    picker?.addEventListener('change', event => showVersion(event.target.value));
    window.addEventListener('survey-map-opened', () => setTimeout(() => map.invalidateSize(), 50));
    showVersion(latestVersion.key);
});

// === AUTO COMPRESS FOTO ===========================================
// Revisi: Step 1 Persiapan & sub-step-nya (Survey/Perizinan/Material
// Delivery) TIDAK LAGI mewajibkan metadata (EXIF) -- validasi EXIF yg
// sebelumnya ada (checkPhotoMetadata/alertNoMetadata) dihapus dari halaman
// ini (khusus Step 1, halaman step lain tidak disentuh). Sebagai gantinya,
// SETIAP foto (bukan PDF) otomatis dikompres (resize max width 1280px,
// JPEG quality 0.75) sebelum diupload, supaya ukuran file tetap kecil
// walau tanpa validasi kamera-asli.
function compressImage(file, maxWidth = 1280, quality = 0.75) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width, height = img.height;
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
                canvas.width = width; canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    if (!blob) { resolve(file); return; }
                    resolve(new File([blob], file.name.replace(/\.[^/.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            };
            img.onerror = () => resolve(file);
            img.src = event.target.result;
        };
        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
}

// Helper generik: kompres semua foto pada sebuah <input type="file" multiple>
// biasa (native submit, tanpa fetch/AJAX) lalu ganti FileList input tsb dgn
// versi terkompres -- dipakai form "Perizinan Selesai" (upload eviden BA KP).
async function compressFileInputPhotos(input) {
    const files = Array.from(input.files).filter(f => f.type.startsWith('image/'));
    if (files.length === 0) return;

    const dt = new DataTransfer();
    for (const file of files) {
        dt.items.add(await compressImage(file));
    }
    input.files = dt.files;
}

// openUploadModal digeneralisasi (Stage 4d) supaya bisa dipakai semua
// sub-step baru: stage & evidence_type sekarang parameter, bukan hardcode
// 'persiapan'. acceptPdf=true mengizinkan file .pdf selain foto.
function openUploadModal(stage, type, title, acceptPdf) {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    document.getElementById('upload_stage').value = stage;
    document.getElementById('upload_evidence_type').value = type;
    document.getElementById('selectedBoqName').innerText = title;

    uploadAcceptsPdf = !!acceptPdf;
    const input = document.getElementById('photoInput');
    const hint = document.getElementById('uploadAcceptHint');
    if (uploadAcceptsPdf) {
        input.setAttribute('accept', 'image/*,application/pdf');
        hint.innerText = 'JPG, PNG, WEBP, PDF';
    } else {
        input.setAttribute('accept', 'image/*');
        hint.innerText = 'JPG, PNG, WEBP · Auto Compress';
    }

    clearAllPhotosAction();

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
        }, function(err) {
            console.warn("GPS lock bypass: ", err.message);
        }, { enableHighAccuracy: true });
    }
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.getElementById('uploadModal').classList.remove('flex');
}

document.getElementById('photoInput').addEventListener('change', async function(e) {
    const files = Array.from(e.target.files);
    for (const file of files) {
        if (file.type.startsWith('image/')) {
            // Tidak ada lagi validasi metadata (EXIF) -- foto langsung
            // dikompres supaya ukuran upload tetap kecil.
            const compressed = await compressImage(file);
            selectedFiles.push({
                file: compressed,
                url: URL.createObjectURL(compressed)
            });
        } else if (uploadAcceptsPdf && file.type === 'application/pdf') {
            selectedFiles.push({ file: file, url: null });
        }
        // Tipe file lain (bukan image/pdf) dilewati.
    }
    renderEvidencePreview();
    document.getElementById('photoInput').value = '';
});

function renderEvidencePreview() {
    const container = document.getElementById('previewContainer');
    const wrapper = document.getElementById('previewWrapper');
    const countLabel = document.getElementById('photoCount');
    container.innerHTML = '';

    if (selectedFiles.length === 0) {
        wrapper.classList.add('hidden');
        return;
    }

    wrapper.classList.remove('hidden');
    countLabel.innerText = selectedFiles.length;

    selectedFiles.forEach((item, index) => {
        const card = document.createElement('div');
        card.className = 'relative aspect-square rounded-xl overflow-hidden bg-slate-50 border border-slate-200';
        if (item.url) {
            card.innerHTML = `
                <img src="${item.url}" class="w-full h-full object-cover">
                <button type="button" onclick="removeEvidencePhoto(${index})" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition">×</button>
                <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] px-1.5 py-0.5 truncate font-medium">${formatFileSize(item.file.size)}</div>
            `;
        } else {
            card.innerHTML = `
                <div class="w-full h-full flex flex-col items-center justify-center gap-1 p-1 text-center">
                    <i class="fa-solid fa-file-pdf text-red-500 text-xl"></i>
                    <p class="text-[8px] font-bold text-slate-500 truncate w-full">${item.file.name}</p>
                </div>
                <button type="button" onclick="removeEvidencePhoto(${index})" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition">×</button>
            `;
        }
        container.appendChild(card);
    });
}

function removeEvidencePhoto(index) {
    if (selectedFiles[index] && selectedFiles[index].url) URL.revokeObjectURL(selectedFiles[index].url);
    selectedFiles.splice(index, 1);
    renderEvidencePreview();
}

function clearAllPhotosAction() {
    selectedFiles.forEach(item => { if (item.url) URL.revokeObjectURL(item.url); });
    selectedFiles = [];
    renderEvidencePreview();
}

document.getElementById('clearAllPhotos').addEventListener('click', clearAllPhotosAction);

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (selectedFiles.length === 0) {
        Swal.fire({ title: 'Pilih Eviden!', text: 'Mohon lampirkan minimal 1 foto/file eviden.', icon: 'warning', confirmButtonColor: '#1565D8', customClass: { popup: 'rounded-3xl' } });
        return;
    }

    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('stage', document.getElementById('upload_stage').value);
    formData.append('evidence_type', document.getElementById('upload_evidence_type').value);
    formData.append('latitude', document.getElementById('latitude').value);
    formData.append('longitude', document.getElementById('longitude').value);
    formData.append('description', document.getElementsByName('description')[0].value);

    selectedFiles.forEach(item => formData.append('photos[]', item.file));

    fetch(e.target.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (response.ok) {
            closeUploadModal();
            Swal.fire({ title: 'Berhasil Disimpan!', text: 'Eviden berhasil diperbarui.', icon: 'success', showConfirmButton: false, timer: 1500, timerProgressBar: true, customClass: { popup: 'rounded-3xl' } })
            .then(() => window.location.reload());
        } else {
            Swal.fire({ title: 'Gagal Memproses!', text: 'Terjadi kegagalan validasi atau gangguan sistem.', icon: 'error', confirmButtonColor: '#1565D8', customClass: { popup: 'rounded-3xl' } });
        }
    })
    .catch(() => {
        Swal.fire({ title: 'Gangguan Jaringan!', text: 'Gagal menghubungi server. Periksa kembali internet Anda.', icon: 'warning', confirmButtonColor: '#1565D8', customClass: { popup: 'rounded-3xl' } });
    });
});
</script>
@endsection
