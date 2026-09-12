@extends('layouts.sdi')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Revisi (permintaan user): desain halaman Approval Golive PT 3
    disamakan PERSIS dengan Approval Golive PT 2 (resources/views/sdi/index.blade.php)
    -- header, 3 kartu ringkasan, tab filter Semua/Waiting Approval/Sudah
    Go-Live, dan struktur tabel yang sama (ditambah kolom Tanggal FI &
    Tanggal Golive). Kata "Reguler" sengaja dihilangkan dari judul/subjudul
    sesuai permintaan. --}}
    <div class="mb-6 bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">Approval Golive PT 3</h1>
            <p class="text-sm text-gray-500 mt-1">Verifikasi Golive khusus Program PT 3 per LOP</p>
        </div>
        <form method="GET" action="{{ route('sdi.golive.index') }}" class="w-full md:w-auto relative">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari LOP, IHLD, STO..."
                   class="w-full md:w-80 h-11 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 pl-10 pr-4 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</div>
        </form>
    </div>

    {{-- Kartu ringkasan Total LOP / Waiting Approval / Jumlah LOP Golive --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Total LOP</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950 flex items-center justify-center text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7a2 2 0 0 1 2-2h6.5L21 8.5V17a2 2 0 0 1-2 2H11a2 2 0 0 1-2-2Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 13H7a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white">{{ $cards['total'] }}</p>
            <p class="text-xs text-gray-400 mt-1">LOP PT 3 dalam antrean Golive</p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-3xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Waiting Approval</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950 flex items-center justify-center text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-amber-600">{{ $cards['waiting'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Menunggu approval Golive</p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-3xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">LOP Golive</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-emerald-600">{{ $cards['golive'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Sudah resmi Golive</p>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tab filter Semua / Waiting Approval / Sudah Go-Live (disamakan dgn PT 2) --}}
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('sdi.golive.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ !request('status_filter') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }}">Semua</a>
        <a href="{{ route('sdi.golive.index', ['status_filter' => 'pending']) }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }}">Waiting Approval</a>
        <a href="{{ route('sdi.golive.index', ['status_filter' => 'approved']) }}" class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'approved' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }}">Sudah Go-Live</a>
    </div>

    {{-- Tabel Utama --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden mb-12">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-black text-gray-900 dark:text-white">Antrean GOLIVE LOP PT 3</h2>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="font-medium">Tampilkan</span>
                    <select onchange="window.location.href=this.value" class="bg-transparent border-none text-gray-900 dark:text-white text-xs font-bold focus:ring-0 cursor-pointer p-0 pr-5">
                        @foreach([10, 20, 50, 100] as $val)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $val, 'page' => 1]) }}" {{ request('per_page', 10) == $val ? 'selected' : '' }}>{{ $val }}</option>
                        @endforeach
                    </select>
                    <span class="font-medium">Baris</span>
                </div>
                <span class="px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold whitespace-nowrap">Total: {{ $lops->total() }}</span>
            </div>
        </div>

        <div class="overflow-x-auto relative">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Nama LOP</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Lokasi</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Tanggal FI</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Tanggal Golive</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Status</th>
                        <th class="px-5 py-3 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($lops as $lop)
                        @php
                            $project = $lop->project;
                            $isGoLive = ($lop->status_progress === 'golive' || $lop->is_golive == 1);
                            $submission = $lop->goliveSubmission;
                            $docs = [
                                'Capture Valins' => $submission?->captureValinsFiles() ?? [],
                                'PDF ABD & Valid4' => $submission?->abdValid4Files() ?? [],
                                'File KML' => $submission?->kmlFiles() ?? [],
                                'Mancore' => $submission?->mancoreFiles() ?? [],
                            ];
                            $docsComplete = $submission?->isComplete() ?? false;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                            <td class="px-5 py-4 min-w-[220px]">
                                <p class="font-black text-gray-900 dark:text-white leading-snug">{{ $lop->lop_name }}</p>
                                <p class="text-xs font-mono text-gray-500 mt-1">
                                    PID: {{ $project->pid ?? '-' }} · IHLD: {{ $lop->id_ihld ?? '-' }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold text-gray-800 dark:text-gray-100">{{ $lop->branch ?? '-' }}</p>
                                <p class="text-xs text-gray-500 mt-1">STO {{ $lop->sto ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @if($submission?->fi_completed_at)
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($submission->fi_completed_at)->format('d M Y') }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($submission->fi_completed_at)->format('H:i') }} WIB</p>
                                @else
                                    <p class="text-sm text-gray-400">-</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($lop->golive_at)
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($lop->golive_at)->format('d M Y') }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($lop->golive_at)->format('H:i') }} WIB</p>
                                @else
                                    <p class="text-sm text-gray-400">-</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($isGoLive)
                                    <span class="px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center gap-1.5 w-max border border-emerald-200">
                                        <span>✅</span> GOLIVE
                                    </span>
                                @else
                                    <span class="px-3 py-1.5 rounded-xl bg-amber-100 text-amber-700 text-xs font-bold flex items-center gap-1.5 w-max border border-amber-200">
                                        <span class="animate-pulse">⏳</span> Waiting Approval
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if(!$isGoLive)
                                    {{-- Revisi (permintaan user): tombol Verifikasi sekarang buka
                                    MODAL (bukan pindah halaman), mirip pola "Proses Go-Live" di
                                    Approval Golive PT 2 -- lengkap dgn toggle konfirmasi & preview
                                    Dokumen FI-OGP yang sudah diupload Admin. --}}
                                    <button type="button"
                                            onclick="openVerifyModal('{{ route('sdi.golive.verify', $lop->id_lop) }}', '{{ $lop->lop_name }}', '{{ $project->pid ?? '-' }}', 'docs-tpl-{{ $lop->id_lop }}', {{ $docsComplete ? 'true' : 'false' }})"
                                            class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
                                        Verifikasi 🚀
                                    </button>
                                    {{-- Template tersembunyi berisi daftar Dokumen FI-OGP LOP ini --
                                    di-clone ke dalam modal via JS saat tombol di atas diklik,
                                    supaya 1 modal bisa dipakai bergantian utk semua baris. --}}
                                    <template id="docs-tpl-{{ $lop->id_lop }}">
                                        <ul class="space-y-2">
                                            @foreach($docs as $label => $paths)
                                                <li class="text-xs border-b border-gray-100 dark:border-gray-800 pb-2 last:border-0 last:pb-0">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-gray-600 dark:text-gray-300 font-medium">{{ $label }}</span>
                                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ count($paths) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400' }}">
                                                            {{ count($paths) }} file
                                                        </span>
                                                    </div>
                                                    @if(count($paths) > 0)
                                                        <div class="mt-1.5 flex flex-wrap gap-2">
                                                            @foreach($paths as $i => $path)
                                                                <a href="{{ Storage::url($path) }}" target="_blank" class="text-[11px] font-bold text-blue-600 hover:underline">File {{ $i + 1 }} ↗</a>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-[11px] text-gray-400">Belum ada</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </template>
                                @else
                                    <a href="{{ $lop->goliveVerification?->capture_uim_path ? Storage::url($lop->goliveVerification->capture_uim_path) : route('sdi.golive.show', $lop->id_lop) }}" target="_blank"
                                    class="h-9 px-4 rounded-xl bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
                                        Lihat Eviden 🖼️
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="text-4xl mb-3 opacity-30">📭</div>
                                <p class="font-black text-gray-900 text-lg">Antrean Kosong</p>
                                <p class="text-gray-500 text-sm mt-1">Belum ada LOP PT 3 yang menunggu Golive.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lops->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                {{ $lops->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL VERIFIKASI GOLIVE (permintaan user: "buatkan modal dan toggle
mirip approval PT 2 dan tampilkan untuk Dokumen FI-OGP yang di upload
oleh admin") -- struktur & animasi disamakan dgn #goLiveModal di
resources/views/sdi/index.blade.php (PT 2), ditambah panel Dokumen
FI-OGP dari Admin di bagian atas form. --}}
<div id="verifyGoliveModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-900 w-full max-w-md m-auto rounded-3xl overflow-hidden flex flex-col shadow-2xl transform scale-95 transition-transform duration-300 max-h-[90vh]" id="verifyModalContent">

        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50">
            <div>
                <h2 class="text-lg font-black text-gray-900 dark:text-white">Verifikasi Golive LOP</h2>
                <p id="verifyProjectName" class="text-xs font-bold text-blue-600 mt-1 truncate max-w-[250px]"></p>
            </div>
            <button type="button" onclick="closeVerifyModal()" class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center hover:bg-red-100 hover:text-red-600 transition font-black">✕</button>
        </div>

        <div class="overflow-y-auto">
            <div class="p-6 pb-0">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-bold text-gray-700 dark:text-gray-300">Dokumen FI-OGP Golive (dari Admin)</label>
                    <span id="verifyDocsBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full"></span>
                </div>
                <div id="verifyDocsBody" class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-800"></div>
            </div>

            <form id="verifyForm" method="POST" enctype="multipart/form-data" class="flex flex-col">
                @csrf
                <div class="p-6 space-y-6">

                    {{-- 1. UPLOAD CAPTURE UIM --}}
                    <div>
                        <label class="flex items-center justify-between text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <span>1. Upload Capture UIM</span>
                            <span class="text-red-500 text-[10px] uppercase tracking-wider bg-red-50 px-2 py-0.5 rounded">* Wajib</span>
                        </label>
                        <label class="relative flex flex-col items-center justify-center w-full h-32 px-4 transition bg-gray-50 border-2 border-gray-300 border-dashed rounded-2xl cursor-pointer hover:bg-gray-100 hover:border-blue-400">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <span id="verifyUploadIcon" class="text-2xl mb-2">📸</span>
                                <p id="verifyUploadText" class="text-xs font-bold text-gray-500 text-center">Klik untuk memilih file bukti<br><span class="font-medium text-[10px]">(JPG, PNG, max 5MB)</span></p>
                            </div>
                            <input type="file" id="verifyFileUim" name="capture_uim" accept="image/*" class="hidden" onchange="validateVerifyForm()" required>
                        </label>
                    </div>

                    <hr class="border-gray-100">

                    {{-- 2. TOGGLE GO LIVE --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">2. Konfirmasi Status</label>

                        <label class="flex items-center justify-between cursor-pointer p-4 rounded-2xl border border-gray-200 bg-white hover:bg-gray-50 transition shadow-sm">
                            <div>
                                <p class="text-sm font-black text-gray-900">Ubah Status menjadi GO-LIVE</p>
                                <p class="text-[10px] font-medium text-gray-500 mt-0.5">Dengan ini, data UIM LOP ini dinyatakan sinkron.</p>
                            </div>
                            <div class="relative">
                                <input type="checkbox" id="verifyGoliveToggle" class="sr-only peer" onchange="validateVerifyForm()" required>
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </div>
                        </label>
                    </div>

                </div>

                <div class="px-6 py-5 bg-gray-50 dark:bg-gray-800 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 rounded-b-3xl">
                    <button type="button" onclick="closeVerifyModal()" class="h-11 px-5 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" id="btnSubmitVerify" disabled class="h-11 px-6 rounded-xl text-sm font-bold text-white bg-gray-400 cursor-not-allowed transition-all shadow-sm flex items-center gap-2">
                        Submit & Selesaikan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if(session('success'))
        Swal.fire({
            title: 'Berhasil Go-Live!',
            text: "{{ session('success') }}",
            icon: 'success',
            confirmButtonColor: '#10b981',
            customClass: { popup: 'rounded-3xl' }
        });
    @endif

    function openVerifyModal(actionUrl, lopName, pid, docsTplId, docsComplete) {
        document.getElementById('verifyProjectName').innerText = lopName + ' (PID: ' + (pid || '-') + ')';
        document.getElementById('verifyForm').action = actionUrl;

        var tpl = document.getElementById(docsTplId);
        document.getElementById('verifyDocsBody').innerHTML = tpl ? tpl.innerHTML : '<p class="text-xs text-gray-400">Belum ada dokumen.</p>';

        var badge = document.getElementById('verifyDocsBadge');
        if (docsComplete) {
            badge.textContent = 'Lengkap';
            badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700';
        } else {
            badge.textContent = 'Belum Lengkap';
            badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700';
        }

        let modal = document.getElementById('verifyGoliveModal');
        let modalContent = document.getElementById('verifyModalContent');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeVerifyModal() {
        let modal = document.getElementById('verifyGoliveModal');
        let modalContent = document.getElementById('verifyModalContent');

        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.getElementById('verifyForm').reset();
            resetVerifyUploadUI();
            validateVerifyForm();
        }, 300);
    }

    document.getElementById('verifyFileUim').addEventListener('change', function (e) {
        let fileName = e.target.files[0]?.name;
        if (fileName) {
            document.getElementById('verifyUploadIcon').innerText = '✅';
            document.getElementById('verifyUploadText').innerHTML = `<span class="text-emerald-600 font-bold">${fileName}</span><br><span class="text-[10px] text-gray-400">Siap diupload</span>`;
        } else {
            resetVerifyUploadUI();
        }
    });

    function resetVerifyUploadUI() {
        document.getElementById('verifyUploadIcon').innerText = '📸';
        document.getElementById('verifyUploadText').innerHTML = `Klik untuk memilih file bukti<br><span class="font-medium text-[10px]">(JPG, PNG, max 5MB)</span>`;
    }

    function validateVerifyForm() {
        let isToggled = document.getElementById('verifyGoliveToggle').checked;
        let hasFile = document.getElementById('verifyFileUim').files.length > 0;
        let btn = document.getElementById('btnSubmitVerify');

        if (isToggled && hasFile) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-gray-400', 'cursor-not-allowed');
            btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
        }
    }
</script>
@endsection
