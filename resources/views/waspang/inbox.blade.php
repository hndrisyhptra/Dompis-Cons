@extends('layouts.waspang')

@section('content')
{{--
    Revisi (permintaan user):
    1. Persentase progress disamakan dgn Project::progressSummary() (posisi
       sequence 11-tahap yang SUDAH BENAR utk flow baru) -- SEBELUMNYA
       halaman ini pakai kalkulasi sendiri (persiapanDone/instalasiDone/dst
       dari eviden mentah) yang TIDAK punya fallback sequence-aware,
       sehingga LOP flow baru (yang sudah tidak lagi menulis eviden
       stage='persiapan') bisa macet keliatan 0%/33% padahal sebenarnya
       sudah lebih jauh. Lihat catatan lengkap di
       Project::progressSummary() & isProjectReadyUt().
    2. Badge stepper "○ Persiapan / ○ Instalasi / ○ Pengukuran / ○
       Finishing" DIHAPUS (permintaan user).
    3. Badge status pojok kanan atas SEKARANG pakai label status_progress
       LOP yang SEBENARNYA (lop.stage.label -- termasuk Hold/Drop apa
       adanya), warnanya dari Project::stageColorClasses() (1 sumber
       kebenaran warna tahap yang sudah dipakai di halaman admin/PM),
       BUKAN lagi cuma teks statis "Selesai"/"On Progress".
    4. Tombol "Laporkan Kendala" (utk LOP yang BELUM ada kendala aktif)
       dihapus -- pelaporan kendala baru sudah ada di setiap step/sub-step
       upload eviden. Tombol "Update Kendala"/"Resume Project" (utk LOP
       yang SUDAH ada kendala aktif) TETAP ada, krn itu mengelola kendala
       yang SUDAH terlanjur dilaporkan (bukan pelaporan baru).
    5. Toggle filter Active/Complete ditambah di atas search -- Inbox
       SEBELUMNYA selalu exclude LOP yang sudah "Ready UT" (cuma bisa
       dilihat lewat menu terpisah). Sekarang toggle di halaman yang sama,
       "Complete" pakai gate isProjectReadyUt() yang SAMA dgn menu Ready UT
       (1 sumber kebenaran "selesai").
    6. Versi dark mode -- toggle disimpan di <html> (layouts/waspang.blade.php,
       localStorage, pola sama dgn layouts/admin.blade.php) supaya persisten
       lintas halaman waspang.
--}}
<div class="min-h-screen max-w-md mx-auto bg-[#F8FAFC] dark:bg-slate-950 pb-24 font-sans transition-colors">

    {{-- ALERT NOTIFIKASI SYSTEM --}}
    @if(session('success'))
        <div class="mx-4 mt-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-xs font-bold shadow-xs">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mx-4 mt-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3 text-xs font-bold shadow-xs">
            {{ session('error') }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="bg-[#1565D8] dark:bg-blue-950 text-white px-5 pt-6 pb-5 rounded-b-[2rem] shadow-lg shadow-slate-900/10 transition-colors">
        <div class="flex items-center gap-3">
            <a href="{{ route('waspang.dashboard') }}" class="w-10 h-10 shrink-0 rounded-2xl bg-white/15 hover:bg-white/25 inline-flex items-center justify-center transition active:scale-90">
                <i class="fa-solid fa-chevron-left text-sm"></i>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-black tracking-tight">Inbox LOP</h1>
                <p class="text-xs text-blue-100 mt-0.5">{{ $projects->count() }} Order di Assign</p>
            </div>
            <button type="button" @click="darkMode = !darkMode"
                    class="w-10 h-10 shrink-0 rounded-2xl bg-white/15 hover:bg-white/25 inline-flex items-center justify-center transition active:scale-90">
                <i class="fa-solid" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
            </button>
        </div>
    </div>

    {{-- TOGGLE FILTER ACTIVE / COMPLETE (permintaan user, di atas search) --}}
    <div class="px-4 mt-4">
        <div class="flex w-full rounded-2xl bg-slate-100 dark:bg-slate-900 p-1 gap-1">
            <a href="{{ route('waspang.inbox', array_filter(['filter' => 'active', 'search' => $search])) }}"
               class="flex-1 h-9 rounded-xl text-xs font-black transition inline-flex items-center justify-center
               {{ $filter === 'active' ? 'bg-white dark:bg-slate-700 text-[#1565D8] dark:text-blue-300 shadow-xs' : 'text-slate-400 dark:text-slate-500' }}">
                Active
            </a>
            <a href="{{ route('waspang.inbox', array_filter(['filter' => 'complete', 'search' => $search])) }}"
               class="flex-1 h-9 rounded-xl text-xs font-black transition inline-flex items-center justify-center
               {{ $filter === 'complete' ? 'bg-white dark:bg-slate-700 text-[#1565D8] dark:text-blue-300 shadow-xs' : 'text-slate-400 dark:text-slate-500' }}">
                Complete
            </a>
        </div>
    </div>

    {{-- SEARCH BAR --}}
    <div class="px-4 mt-3">
        <form method="GET" action="{{ route('waspang.inbox') }}">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <div class="relative">
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari LOP, STO, branch, mitra..."
                    class="w-full h-11 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 pl-10 pr-4 text-xs font-bold shadow-xs focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900 focus:border-[#1565D8] outline-none transition">
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARDS PROJECT LOP --}}
    <div class="px-4 mt-4 space-y-4">
        @forelse($projects as $project)
            @php
                // Revisi (permintaan user): persentase & status stage SEKARANG
                // pakai Project::progressSummary() -- 1 sumber kebenaran yang
                // sudah sequence-aware (benar utk LOP flow baru), dipakai jg
                // oleh Dashboard/Timeline/dsb. Kalkulasi lama (persiapanDone/
                // instalasiDone/dst dari eviden mentah tanpa fallback
                // sequence) DIHAPUS dari sini.
                $evidences = $project->evidences ?? collect();
                $summary = $project->progressSummary();
                $progress = $summary['progress'];
                $allDone = $summary['finishingDone'];

                // Status pojok kanan atas: label status_progress LOP YANG
                // SEBENARNYA (termasuk Hold/Drop apa adanya, BUKAN "Selesai"/
                // "On Progress" statis) -- warnanya dari
                // Project::stageColorClasses() (1 sumber warna tahap yang
                // sudah dipakai halaman admin/PM).
                $rawStage = $project->lop?->stage;
                $statusLabel = $rawStage?->label ?? 'Persiapan';
                $statusColors = \App\Models\Project::stageColorClasses($rawStage?->color);

                $lastUpdate = optional($evidences->sortByDesc('updated_at')->first())->updated_at ?? $project->updated_at;

                $kendalaIssue = $project->issues->where('status', 'kendala')->sortByDesc('created_at')->first();
            @endphp

            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 border-l-[4px] {{ $statusColors['border'] }} rounded-3xl p-4 shadow-xs transition-colors">

                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-slate-900 dark:text-slate-100 tracking-tight leading-tight">
                            {{ $project->project_name }}
                        </h2>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 font-bold mt-1">
                            {{ $project->lop?->branch }} · {{ $project->lop?->sto }} · {{ strtoupper($project->execution_type) }}
                        </p>
                    </div>

                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wide {{ $statusColors['badge'] }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                {{-- PROGRESS BAR --}}
                <div class="mt-4 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full {{ $statusColors['progress'] }} rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                </div>

                {{-- FOOTER INFO CARD --}}
                <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-50 dark:border-slate-800">
                    <div>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Total Progress</p>
                        <p class="text-sm font-black text-[#1565D8] dark:text-blue-400">{{ $progress }}%</p>
                    </div>

                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Update Terakhir</p>
                        <p class="text-[11px] font-black text-slate-700 dark:text-slate-300">{{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}</p>
                        <a href="{{ route('waspang.projects.show', $project->id_project) }}" class="inline-block text-[11px] font-black text-[#1565D8] dark:text-blue-400 mt-0.5 hover:underline">
                            Detail Stage →
                        </a>
                    </div>
                </div>

                {{-- ACTION FORMS & BUTTONS --}}
                <div class="mt-3.5 grid grid-cols-2 gap-2">
                    @if($allDone)
                        <a href="{{ route('waspang.projects.review_final', $project->id_project) }}"
                           class="h-10 col-span-2 inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition">
                            Review BOQ Final
                        </a>
                    @else
                        @if($kendalaIssue)
                            <button type="button" onclick="openKendalaModal('{{ $project->id_project }}')"
                                    class="h-10 inline-flex items-center justify-center rounded-xl bg-orange-600 text-white text-xs font-black transition">
                                Update Kendala
                            </button>

                            <form method="POST" action="{{ route('waspang.projects.issues.resume', $project->id_project) }}">
                                @csrf
                                <button type="submit" class="h-10 w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 text-white text-xs font-black transition">
                                    Resume Project
                                </button>
                            </form>
                        @else
                            {{-- Tombol "Laporkan Kendala" DIHAPUS (permintaan
                                 user) -- pelaporan kendala baru sudah ada di
                                 setiap step/sub-step upload eviden. "Upload
                                 Eviden" jadi full-width. --}}
                            <a href="{{ route('waspang.projects.show', $project->id_project) }}"
                               class="h-10 col-span-2 inline-flex items-center justify-center rounded-xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-xs font-black shadow-sm transition">
                                Upload Eviden
                            </a>
                        @endif
                    @endif
                </div>

                {{-- KOTAK INFORMASI JIKA ADA KENDALA AKTIF --}}
                @if($kendalaIssue)
                    <div class="mt-3 rounded-xl bg-orange-50/60 dark:bg-orange-950/30 border border-orange-100 dark:border-orange-900 p-3 flex gap-2 items-start animate-fade-in">
                        <i class="fa-solid fa-triangle-exclamation text-orange-600 dark:text-orange-400 text-xs mt-0.5"></i>
                        <div class="min-w-0">
                            <p class="text-[11px] font-black text-orange-800 dark:text-orange-300">Konstruksi Terhenti Lapangan:</p>
                            <p class="text-[11px] text-orange-700 dark:text-orange-400 mt-0.5 break-words line-clamp-2">{{ $kendalaIssue->description }}</p>
                        </div>
                    </div>
                @endif

            </div>

            {{-- MODAL BOX POPUP KENDALA --}}
            <div id="kendalaModal-{{ $project->id_project }}" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs">
                <div class="bg-white dark:bg-slate-800 rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden animate-fade-in">

                    <div class="bg-orange-600 dark:bg-orange-700 text-white px-5 py-4 flex items-start justify-between">
                        <div>
                            <h2 class="text-base font-black">Laporkan Kendala</h2>
                            <p class="text-xs text-orange-100 mt-0.5 line-clamp-1 font-medium">{{ $project->project_name }}</p>
                        </div>
                        <button type="button" onclick="closeKendalaModal('{{ $project->id_project }}')" class="w-8 h-8 rounded-full bg-white/20 text-white font-black text-sm">×</button>
                    </div>

                    <form method="POST" action="{{ route('waspang.projects.issues.store', $project->id_project) }}" enctype="multipart/form-data" class="p-5 space-y-4" data-issue-uploader="{{ $project->id_project }}">
                        @csrf
                        <div>
                            <label class="text-xs font-black text-slate-600 dark:text-slate-300">Jenis Kendala</label>
                            <select name="issue_type" required class="mt-1 w-full h-10 rounded-xl border border-slate-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 px-3 text-xs font-bold outline-none focus:border-orange-600 transition">
                                <option value="">Pilih kendala</option>
                                <option value="perizinan">Perizinan</option>
                                <option value="material">Material</option>
                                <option value="akses_lokasi">Akses Lokasi</option>
                                <option value="cuaca">Cuaca</option>
                                <option value="teknis">Teknis Lapangan</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-black text-slate-600 dark:text-slate-300">Keterangan Kendala</label>
                            <textarea name="description" required rows="3" placeholder="Deskripsikan hambatan lapangan secara detail..." class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 px-3 py-2 text-xs font-medium outline-none focus:border-orange-600 transition resize-none"></textarea>
                        </div>

                        <div>
                            <label class="text-xs font-black text-slate-600 dark:text-slate-300">Eviden Foto Kendala <span class="text-slate-400 dark:text-slate-500 font-normal">(Opsional)</span></label>
                            <label class="mt-1.5 flex flex-col items-center justify-center w-full min-h-[100px] border-2 border-dashed border-orange-200 dark:border-orange-800 rounded-2xl bg-orange-50/30 dark:bg-orange-900/10 cursor-pointer hover:bg-orange-50 dark:hover:bg-orange-900/20 transition p-3">
                                <div class="text-center">
                                    <p class="text-xs font-black text-orange-700 dark:text-orange-400">Ambil / Pilih Gambar</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Auto Compress JPEG</p>
                                </div>
                                <input type="file" name="photos[]" accept="image/*" multiple class="hidden issue-photo-input" data-project-id="{{ $project->id_project }}">
                            </label>

                            <div class="mt-3 hidden issue-preview-wrapper" data-project-id="{{ $project->id_project }}">
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Preview Lapangan</p>
                                    <button type="button" class="text-[10px] font-black text-rose-600 dark:text-rose-400 issue-clear-all" data-project-id="{{ $project->id_project }}">Hapus Semua</button>
                                </div>
                                <div class="grid grid-cols-3 gap-2 issue-preview-grid" data-project-id="{{ $project->id_project }}"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button type="button" onclick="closeKendalaModal('{{ $project->id_project }}')" class="h-10 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-black transition">Batal</button>
                            <button type="submit" class="h-10 rounded-xl bg-orange-600 dark:bg-orange-700 text-white text-xs font-black transition shadow-md">Kirim Lap.</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-8 text-center text-xs text-slate-400 dark:text-slate-500 shadow-xs">
                Belum ada LOP yang ditugaskan kepada Anda saat ini.
            </div>
        @endforelse
    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])
</div>
@endsection

@section('scripts')
<script>
    // DEBOUNCE SEARCH SUBMISSION
    let searchTimeout = null;
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { this.form.submit(); }, 500);
        });
    }

    // MODAL INTERACTIVE CLOSURES
    const issueUploaders = {};
    function openKendalaModal(projectId) {
        const modal = document.getElementById('kendalaModal-' + projectId);
        if(modal) modal.classList.remove('hidden');
    }
    function closeKendalaModal(projectId) {
        const modal = document.getElementById('kendalaModal-' + projectId);
        if(modal) modal.classList.add('hidden');
    }

    function initIssueUploader(projectId) {
        if (!issueUploaders[projectId]) { issueUploaders[projectId] = { files: [] }; }
        return issueUploaders[projectId];
    }

    // CLIENT COMPRESSION ENGINE
    function compressImage(file, maxWidth = 1280, quality = 0.75) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let w = img.width, h = img.height;
                    if (w > maxWidth) { h = Math.round((h * maxWidth) / w); w = maxWidth; }
                    canvas.width = w; canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    canvas.toBlob((blob) => {
                        resolve(new File([blob], file.name.replace(/\.[^/.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                    }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function formatFileSize(b) {
        return b < 1048576 ? Math.round(b / 1024) + ' KB' : (b / 1048576).toFixed(1) + ' MB';
    }

    function syncIssueInput(projectId) {
        const uploader = initIssueUploader(projectId);
        const input = document.querySelector(`.issue-photo-input[data-project-id="${projectId}"]`);
        if (!input) return;
        const dt = new DataTransfer();
        uploader.files.forEach(item => dt.items.add(item.file));
        input.files = dt.files;
    }

    function renderIssuePreview(projectId) {
        const uploader = initIssueUploader(projectId);
        const wrapper = document.querySelector(`.issue-preview-wrapper[data-project-id="${projectId}"]`);
        const grid = document.querySelector(`.issue-preview-grid[data-project-id="${projectId}"]`);
        if (!wrapper || !grid) return;

        grid.innerHTML = '';
        if (uploader.files.length === 0) { wrapper.classList.add('hidden'); return; }
        wrapper.classList.remove('hidden');

        uploader.files.forEach((item, idx) => {
            const card = document.createElement('div');
            card.className = 'relative aspect-square rounded-xl overflow-hidden bg-slate-50 border border-slate-200';
            card.innerHTML = `
                <img src="${item.url}" class="w-full h-full object-cover">
                <button type="button" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/70 text-white text-[10px] font-black flex items-center justify-center" onclick="removeIssuePhoto('${projectId}', ${idx})">×</button>
                <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[8px] px-1 py-0.5 truncate">${formatFileSize(item.file.size)}</div>
            `;
            grid.appendChild(card);
        });
    }

    function removeIssuePhoto(projectId, idx) {
        const uploader = initIssueUploader(projectId);
        if (uploader.files[idx]) URL.revokeObjectURL(uploader.files[idx].url);
        uploader.files.splice(idx, 1);
        renderIssuePreview(projectId);
        syncIssueInput(projectId);
    }

    document.addEventListener('change', async function (e) {
        if (!e.target.classList.contains('issue-photo-input')) return;
        const pId = e.target.dataset.projectId;
        const uploader = initIssueUploader(pId);
        for (const file of Array.from(e.target.files)) {
            if (!file.type.startsWith('image/')) continue;
            const comp = await compressImage(file, 1280, 0.75);
            uploader.files.push({ file: comp, url: URL.createObjectURL(comp) });
        }
        renderIssuePreview(pId);
        syncIssueInput(pId);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('issue-clear-all')) return;
        const pId = e.target.dataset.projectId;
        const uploader = initIssueUploader(pId);
        uploader.files.forEach(item => URL.revokeObjectURL(item.url));
        uploader.files = [];
        renderIssuePreview(pId);
        syncIssueInput(pId);
    });
</script>
@endsection
