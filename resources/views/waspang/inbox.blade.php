@extends('layouts.waspang')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">

    {{-- ALERT NOTIFIKASI SYSTEM --}}
    @if(session('success'))
        <div class="mx-4 mt-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-xs font-bold shadow-xs">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mx-4 mt-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-xs font-bold shadow-xs">
            {{ session('error') }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem] shadow-md">
        <div class="flex items-center gap-3">
            <a href="{{ route('waspang.dashboard') }}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
                ‹
            </a>
            <div>
                <h1 class="text-xl font-black tracking-tight">Inbox LOP</h1>
                <p class="text-xs text-blue-100 mt-0.5">{{ $projects->count() }} Order di Assign</p>
            </div>
        </div>
    </div>

    {{-- SEARCH BAR --}}
    <div class="px-4 mt-4">
        <form method="GET" action="{{ route('waspang.inbox') }}">
            <div class="relative">
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari LOP, STO, branch, mitra..."
                    class="w-full h-11 rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-xs font-bold shadow-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition">
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                    🔍
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARDS PROJECT LOP --}}
    <div class="px-4 mt-4 space-y-4">
        @forelse($projects as $project)
            @php
                $evidences = $project->evidences ?? collect();
                $boqItems = $project->boqItems ?? collect();

                // 1. STAGE PERSIAPAN (Wajib Approved)
                $persiapanDone = $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba')->where('status', 'approved')->count() > 0 
                    && $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan')->where('status', 'approved')->count() > 0;

                // 2. STAGE INSTALASI (Wajib Approved Semua Item Material)
                $materialBoqItems = $boqItems->filter(function ($boq) {
                    return str_starts_with($boq->designator, 'M-') || optional($boq->designatorData)->type === 'material';
                });
                $boqTotal = $materialBoqItems->count();
                $boqDone = $materialBoqItems->filter(function ($boq) use ($evidences) {
                    return $evidences->where('stage', 'instalasi')->where('evidence_type', 'progress_boq')->where('boq_item_id', $boq->id_boq)->where('status', 'approved')->count() > 0;
                })->count();
                $instalasiDone = $boqTotal > 0 && $boqDone == $boqTotal;

                // 3. STAGE PENGUKURAN (Opsional & Fleksibel)
                $hasOtdr = $evidences->where('stage', 'pengukuran')->where('evidence_type', 'otdr')->count() > 0;
                $hasOpm = $evidences->where('stage', 'pengukuran')->where('evidence_type', 'opm')->count() > 0;
                $hasDalam = $evidences->where('stage', 'pengukuran')->where('evidence_type', 'kedalaman')->count() > 0;
                
                $pengukuranApproved = $evidences->where('stage', 'pengukuran')->where('status', 'approved')->count() == $evidences->where('stage', 'pengukuran')->count();
                $pengukuranDone = ($hasOtdr || $hasOpm || $hasDalam) ? $pengukuranApproved : true;

                // 4. STAGE FINISHING (Wajib Approved Akhir)
                $finishingDone = $evidences->where('stage', 'finishing')->where('status', 'approved')->count() > 0;

                // ATURAN SINKRONISASI 100% PROGRESS
                $doneStep = 0;
                if ($persiapanDone) $doneStep++;
                if ($instalasiDone) $doneStep++;
                if ($finishingDone) $doneStep++;

                // Pengukuran bersifat penambah visual jika ada, tapi tidak menahan laju 100% jika dilewati
                $progress = ($persiapanDone && $instalasiDone && $finishingDone) ? 100 : round(($doneStep / 3) * 100);
                $allStepDone = ($progress === 100);

                // DYNAMIC TEMPLATE DESIGN STYLES
                $borderColor = $allStepDone ? 'border-l-emerald-600' : 'border-l-blue-600';
                $progressColor = $allStepDone ? 'bg-emerald-500' : 'bg-blue-600';

                $lastUpdate = optional($evidences->sortByDesc('updated_at')->first())->updated_at ?? $project->updated_at;
            @endphp

            <div class="bg-white border border-slate-100 border-l-[4px] {{ $borderColor }} rounded-3xl p-4 shadow-xs">
                
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-slate-800 tracking-tight leading-tight">
                            {{ $project->project_name }}
                        </h2>
                        <p class="text-[11px] text-slate-400 font-bold mt-1">
                            {{ $project->lop?->branch }} · {{ $project->lop?->sto }} · {{ strtoupper($project->execution_type) }}
                        </p>
                    </div>

                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wide
                        {{ $allStepDone ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $allStepDone ? 'Selesai' : 'On Progress' }}
                    </span>
                </div>

                {{-- STEPPER BADGES --}}
                <div class="flex flex-wrap gap-1 mt-3.5 text-[9px] font-extrabold">
                    <span class="px-2 py-0.5 rounded-md border {{ $persiapanDone ? 'bg-red-50 border-red-200 text-red-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $persiapanDone ? '✓ Persiapan' : '○ Persiapan' }}
                    </span>

                    <span class="px-2 py-0.5 rounded-md border {{ $instalasiDone ? 'bg-blue-50 border-blue-200 text-blue-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $instalasiDone ? '✓ Instalasi' : '○ Instalasi' }}
                    </span>

                    {{-- Pengukuran Otomatis Checked Jika Dilewati Tanpa Data, atau Jika Diisi Wajib Approved --}}
                    <span class="px-2 py-0.5 rounded-md border {{ $pengukuranDone ? 'bg-amber-50 border-amber-200 text-amber-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        ✓ Pengukuran
                    </span>

                    <span class="px-2 py-0.5 rounded-md border {{ $finishingDone ? 'bg-emerald-50 border-emerald-200 text-emerald-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $finishingDone ? '✓ Finishing' : '○ Finishing' }}
                    </span>
                </div>

                {{-- PROGRESS BAR --}}
                <div class="mt-4 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $progressColor }} rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                </div>

                {{-- FOOTER INFO CARD --}}
                <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-50">
                    <div>
                        <p class="text-[10px] text-slate-400 font-medium">Total Progress</p>
                        <p class="text-sm font-black text-blue-700">{{ $progress }}%</p>
                    </div>

                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 font-medium">Update Terakhir</p>
                        <p class="text-[11px] font-black text-slate-700">{{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}</p>
                        <a href="{{ route('waspang.projects.show', $project->id_project) }}" class="inline-block text-[11px] font-black text-blue-600 mt-0.5 hover:underline">
                            Detail Stage →
                        </a>
                    </div>
                </div>

                {{-- ACTION FORMS & BUTTONS --}}
                @php
                    $kendalaIssue = $project->issues->where('status', 'kendala')->sortByDesc('created_at')->first();
                    $resumeIssue = $project->issues->where('status', 'open')->sortByDesc('updated_at')->first();
                @endphp

                <div class="mt-3.5 grid grid-cols-2 gap-2">
                    @if($allStepDone)
                        <a href="{{ route('waspang.projects.review_final', $project->id_project) }}"
                           class="h-10 col-span-2 inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition">
                            Review BOQ Final & UT
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
                            <button type="button" onclick="openKendalaModal('{{ $project->id_project }}')"
                                    class="h-10 inline-flex items-center justify-center rounded-xl bg-orange-500/10 border border-orange-200 text-orange-700 text-xs font-black transition hover:bg-orange-50">
                                Laporkan Kendala
                            </button>

                            <a href="{{ route('waspang.projects.show', $project->id_project) }}"
                               class="h-10 inline-flex items-center justify-center rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-black shadow-sm transition">
                                Upload Eviden
                            </a>
                        @endif
                    @endif
                </div>

                {{-- KOTAK INFORMASI JIKA ADA KENDALA AKTIF --}}
                @if($kendalaIssue)
                    <div class="mt-3 rounded-xl bg-orange-50/60 border border-orange-100 p-3 flex gap-2 items-start animate-fade-in">
                        <span class="text-orange-600 font-bold text-xs">⚠️</span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-black text-orange-800">Konstruksi Terhenti Lapangan:</p>
                            <p class="text-[11px] text-orange-700 mt-0.5 break-words line-clamp-2">{{ $kendalaIssue->description }}</p>
                        </div>
                    </div>
                @endif

            </div>

            {{-- MODAL BOX POPUP KENDALA --}}
            <div id="kendalaModal-{{ $project->id_project }}" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs">
                <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden animate-fade-in">
                    
                    <div class="bg-orange-600 text-white px-5 py-4 flex items-start justify-between">
                        <div>
                            <h2 class="text-base font-black">Laporkan Kendala</h2>
                            <p class="text-xs text-orange-100 mt-0.5 line-clamp-1 font-medium">{{ $project->project_name }}</p>
                        </div>
                        <button type="button" onclick="closeKendalaModal('{{ $project->id_project }}')" class="w-8 h-8 rounded-full bg-white/20 text-white font-black text-sm">×</button>
                    </div>

                    <form method="POST" action="{{ route('waspang.projects.issues.store', $project->id_project) }}" enctype="multipart/form-data" class="p-5 space-y-4" data-issue-uploader="{{ $project->id_project }}">
                        @csrf
                        <div>
                            <label class="text-xs font-black text-slate-600">Jenis Kendala</label>
                            <select name="issue_type" required class="mt-1 w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold outline-none focus:border-orange-600 transition">
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
                            <label class="text-xs font-black text-slate-600">Keterangan Kendala</label>
                            <textarea name="description" required rows="3" placeholder="Deskripsikan hambatan lapangan secara detail..." class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium outline-none focus:border-orange-600 transition resize-none"></textarea>
                        </div>

                        <div>
                            <label class="text-xs font-black text-slate-600">Eviden Foto Kendala <span class="text-slate-400 font-normal">(Opsional)</span></label>
                            <label class="mt-1.5 flex flex-col items-center justify-center w-full min-h-[100px] border-2 border-dashed border-orange-200 rounded-2xl bg-orange-50/30 cursor-pointer hover:bg-orange-50 transition p-3">
                                <div class="text-center">
                                    <p class="text-xs font-black text-orange-700">Ambil / Pilih Gambar</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Auto Compress JPEG</p>
                                </div>
                                <input type="file" name="photos[]" accept="image/*" multiple class="hidden issue-photo-input" data-project-id="{{ $project->id_project }}">
                            </label>

                            <div class="mt-3 hidden issue-preview-wrapper" data-project-id="{{ $project->id_project }}">
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Preview Lapangan</p>
                                    <button type="button" class="text-[10px] font-black text-rose-600 issue-clear-all" data-project-id="{{ $project->id_project }}">Hapus Semua</button>
                                </div>
                                <div class="grid grid-cols-3 gap-2 issue-preview-grid" data-project-id="{{ $project->id_project }}"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button type="button" onclick="closeKendalaModal('{{ $project->id_project }}')" class="h-10 rounded-xl bg-slate-100 text-slate-700 text-xs font-black transition">Batal</button>
                            <button type="submit" class="h-10 rounded-xl bg-orange-600 text-white text-xs font-black transition shadow-md">Kirim Lap.</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white border border-slate-100 rounded-3xl p-8 text-center text-xs text-slate-400 shadow-xs">
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