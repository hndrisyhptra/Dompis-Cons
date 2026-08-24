@extends('layouts.surveyor')

@section('title', 'Survey Baru')

@section('content')

    <div class="bg-blue-700 px-5 pt-6 pb-10 rounded-b-[2rem] relative overflow-hidden">

        <div class="relative flex items-center gap-3">
            <a href="{{ route('surveyor.index') }}" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center border border-white/10">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <p class="text-blue-200 text-xs font-semibold tracking-wide uppercase">Survey Lapangan</p>
                <h1 class="text-white text-lg font-black">Mulai Survey Baru</h1>
            </div>
        </div>
    </div>

    <div class="px-5 -mt-5 relative">
        <form method="POST" action="{{ route('surveyor.store') }}" class="bg-white rounded-2xl shadow-lg shadow-slate-900/5 border border-slate-100 p-5 space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Judul Survey <span class="text-red-500">*</span></label>
                <input type="text" name="title" required value="{{ old('title') }}"
                       placeholder="Contoh: Survey Rute Kabel STO Cempaka"
                       class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
                @error('title')<p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            @php
                $oldProjectId = old('project_id');
                $oldProject = $oldProjectId ? $projects->firstWhere('id_project', (int) $oldProjectId) : null;
                $oldProjectLabel = $oldProject ? $oldProject->project_name . ($oldProject->pid ? ' (PID: '.$oldProject->pid.')' : '') : '';
            @endphp
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kaitkan ke Project (opsional)</label>
                <div class="relative">
                    <input type="text" id="projectSearchInput" autocomplete="off"
                           placeholder="Ketik nama project atau PID untuk mencari..."
                           value="{{ $oldProjectLabel }}"
                           class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition pr-8">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                    <input type="hidden" name="project_id" id="project_id" value="{{ $oldProjectId }}">

                    <div id="projectSearchResults" class="hidden absolute z-30 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg"></div>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika tidak dikaitkan / project belum ada di sistem.</p>
                @error('project_id')<p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Atau Nama Lokasi / Project Manual</label>
                <input type="text" name="project_name" value="{{ old('project_name') }}"
                       placeholder="Diisi jika project belum terdaftar di sistem"
                       class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Awal (opsional)</label>
                <textarea name="notes" rows="3" placeholder="Catatan kondisi lapangan, target area, dsb."
                          class="w-full rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">{{ old('notes') }}</textarea>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-start gap-2.5">
                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                <p class="text-[11px] text-blue-700 leading-relaxed">
                    Setelah survey dibuat, kamu akan diarahkan ke halaman peta untuk mulai tagging titik tiang eksisting, catuan (ODC/ODP/JC), menggambar rute kabel, dan menentukan titik ending site.
                </p>
            </div>

            <button type="submit"
                    class="w-full h-12 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-700 text-white text-sm font-black shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2">
                <i class="fa-solid fa-map-location-dot"></i>
                Mulai Survey &amp; Buka Peta
            </button>
        </form>
    </div>

@endsection

@section('bottom-nav')
    @include('surveyor.partials.bottom-nav', ['active' => ''])
@endsection

@push('scripts')
<script>
    // === SEARCH PROJECT (Kaitkan ke Project) ============================
    // List project sudah di-load sekaligus (maks 300 baris) supaya pencarian
    // bisa langsung filter di browser tanpa perlu request tambahan ke server.
    const projectSearchData = @json($projects->map(fn($p) => [
        'id' => $p->id_project,
        'label' => $p->project_name . ($p->pid ? ' (PID: '.$p->pid.')' : ''),
    ])->values());

    const projectSearchInput = document.getElementById('projectSearchInput');
    const projectSearchResults = document.getElementById('projectSearchResults');
    const projectIdInput = document.getElementById('project_id');

    function renderProjectSearchResults(list) {
        if (list.length === 0) {
            projectSearchResults.innerHTML = '<div class="px-3 py-2.5 text-xs text-slate-400">Project tidak ditemukan</div>';
        } else {
            projectSearchResults.innerHTML = list.slice(0, 50).map(function (p) {
                return '<button type="button" data-id="' + p.id + '" data-label="' + p.label.replace(/"/g, '&quot;') + '" ' +
                    'class="project-option w-full text-left px-3 py-2.5 text-xs font-medium text-slate-700 hover:bg-blue-50 border-b border-slate-50 last:border-0">' +
                    p.label + '</button>';
            }).join('');
        }
        projectSearchResults.classList.remove('hidden');
    }

    function filterProjectSearch(query) {
        const q = query.trim().toLowerCase();
        return q ? projectSearchData.filter(function (p) { return p.label.toLowerCase().includes(q); }) : projectSearchData;
    }

    projectSearchInput.addEventListener('focus', function () {
        renderProjectSearchResults(filterProjectSearch(this.value));
    });

    projectSearchInput.addEventListener('input', function () {
        projectIdInput.value = ''; // reset pilihan lama kalau user mengetik ulang
        renderProjectSearchResults(filterProjectSearch(this.value));
    });

    projectSearchResults.addEventListener('click', function (e) {
        const btn = e.target.closest('.project-option');
        if (!btn) return;
        projectIdInput.value = btn.dataset.id;
        projectSearchInput.value = btn.dataset.label;
        projectSearchResults.classList.add('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!projectSearchInput.contains(e.target) && !projectSearchResults.contains(e.target)) {
            projectSearchResults.classList.add('hidden');
        }
    });
</script>
@endpush
