{{-- Alur baru Persiapan > Survey. Visual mengikuti kartu/accordion mobile yang sudah ada. --}}
<div x-data="{ open: {{ $step['survey']['active'] ? 'true' : 'false' }}, finalizeOpen: {{ old('volumes') ? 'true' : 'false' }} }"
     class="bg-white rounded-2xl border overflow-hidden shadow-xs {{ $step['survey']['active'] ? 'border-[#1565D8]/40' : 'border-slate-200' }}">
    <button type="button"
            @click="open = !open; setTimeout(() => window.dispatchEvent(new Event('survey-map-opened')), 250)"
            class="w-full p-4 flex items-center justify-between gap-3 text-left">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0
                {{ $step['survey']['done'] ? 'bg-emerald-50 text-emerald-600' : ($step['survey']['active'] ? 'bg-blue-50 text-[#1565D8]' : 'bg-slate-50 text-slate-400') }}">
                @if($step['survey']['done'])
                    <i class="fa-solid fa-check"></i>
                @else
                    2
                @endif
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-slate-900 tracking-tight">2. Survey</h3>
                <p class="text-[10.5px] font-medium text-slate-400 mt-0.5">
                    {{ $currentSurveyMap['label'] ?? 'KML Admin belum tersedia' }} · {{ $surveyBoqGroups->count() }} Item BOQ
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                {{ $step['survey']['done'] ? 'bg-emerald-100 text-emerald-700' : ($step['survey']['active'] ? 'bg-blue-100 text-[#1565D8]' : 'bg-slate-100 text-slate-500') }}">
                {{ $step['survey']['done'] ? 'Selesai' : ($step['survey']['active'] ? 'Aktif' : 'Menunggu') }}
            </span>
            <i class="fa-solid text-[10px] text-slate-400 transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </div>
    </button>

    <div x-show="open" x-transition x-cloak class="border-t border-slate-50 bg-slate-50/30 p-4 space-y-3">
        @if($currentSurveyMap)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-3 py-2.5">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Peta desain aktif</p>
                        <p id="surveyMapVersionLabel" class="truncate text-xs font-black text-slate-800">{{ $currentSurveyMap['label'] }}</p>
                    </div>
                    @if($surveyMapVersions->count() > 1)
                        <select id="surveyMapVersionPicker" class="h-8 max-w-[150px] rounded-lg border-slate-200 bg-slate-50 px-2 text-[10px] font-bold text-slate-600">
                            @foreach($surveyMapVersions as $mapVersion)
                                <option value="{{ $mapVersion['key'] }}" @selected($mapVersion['key'] === $currentSurveyMap['key'])>
                                    {{ $mapVersion['label'] }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="relative">
                    <div id="surveyWorkflowMap" class="h-72 w-full bg-slate-100"></div>
                    <div id="surveyWorkflowMapLoading" class="absolute inset-0 flex items-center justify-center bg-white/80 text-xs font-bold text-slate-500">
                        <i class="fa-solid fa-spinner fa-spin mr-2 text-[#1565D8]"></i> Memuat peta…
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-[11px] font-black text-slate-800">Riwayat desain tersimpan</p>
                        <p class="mt-0.5 text-[10px] text-slate-400">{{ $surveyMapVersions->count() }} versi · versi lama tidak dihapus</p>
                    </div>
                    @if($surveyMapConfirmed)
                        <span class="rounded-lg bg-emerald-100 px-2 py-1 text-[9px] font-black uppercase text-emerald-700">Sesuai</span>
                    @endif
                </div>
            </div>

            @if($step['survey']['active'])
                <div class="grid grid-cols-2 gap-2">
                    <form method="POST" action="{{ route('waspang.survey.map.confirm', $project->id_project) }}">
                        @csrf
                        <button id="confirmSurveyMapButton" type="submit" data-current-map-key="{{ $currentSurveyMap['key'] }}"
                                class="h-11 w-full rounded-xl {{ $surveyMapConfirmed ? 'bg-emerald-600' : 'bg-[#1565D8] hover:bg-[#0F4FAF]' }} text-xs font-black text-white shadow-xs transition">
                            <i class="fa-solid fa-check mr-1"></i> <span>{{ $surveyMapConfirmed ? 'Sudah Sesuai' : 'Sesuai' }}</span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('waspang.survey.redesign', $project->id_project) }}">
                        @csrf
                        <button type="submit" class="h-11 w-full rounded-xl border border-amber-200 bg-amber-50 text-xs font-black text-amber-700 transition hover:bg-amber-100">
                            <i class="fa-solid fa-pen-ruler mr-1"></i> Redesign
                        </button>
                    </form>
                </div>
            @endif
        @else
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                KML desain awal belum diunggah Admin. Proses Survey belum dapat dilanjutkan.
            </div>
        @endif

        @if($step['survey']['active'] && $lop->survey_redesign_required)
            <div class="space-y-3 rounded-2xl border border-amber-300 bg-amber-50 p-3">
                <div>
                    <p class="text-xs font-black text-amber-800"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Approval Redesign Diperlukan</p>
                    <p class="mt-1 text-[10px] leading-relaxed text-amber-700">
                        Nilai BOQ Survey menyimpang <span class="font-black">{{ rtrim(rtrim(number_format($lop->survey_deviation_percent, 2, '.', ''), '0'), '.') }}%</span> dari BOQ Plan (di atas ambang 10%). Upload bukti foto/capture yang menyatakan sudah disetujui untuk melanjutkan ke Perizinan.
                    </p>
                </div>
                <form id="surveyRedesignApprovalForm" method="POST" action="{{ route('waspang.survey.redesign-approval.store', $project->id_project) }}" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <label class="flex flex-col items-center justify-center w-full min-h-[90px] border-2 border-dashed border-amber-300 rounded-2xl bg-white cursor-pointer hover:bg-amber-50/60 transition p-3">
                        <div class="text-center">
                            <i class="fa-solid fa-camera text-amber-500 text-lg"></i>
                            <p class="text-xs font-black text-amber-700 mt-1">Ambil / Pilih Foto Bukti Persetujuan</p>
                        </div>
                        <input type="file" name="photos[]" id="surveyRedesignApprovalPhotoInput" accept="image/*" multiple class="hidden">
                    </label>
                    <div id="surveyRedesignApprovalPreview" class="grid grid-cols-4 gap-1.5"></div>
                    @error('photos')
                        <p class="text-[9px] font-bold text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="h-11 w-full rounded-xl bg-amber-600 text-xs font-black text-white shadow-sm">
                        <i class="fa-solid fa-check mr-1"></i> Konfirmasi Disetujui & Lanjut ke Perizinan
                    </button>
                </form>
            </div>
        @elseif($step['survey']['active'] && $surveyMapConfirmed)
            <div class="border-t border-slate-100 pt-3">
                <button type="button" @click="finalizeOpen = !finalizeOpen"
                        class="h-10 w-full rounded-xl bg-[#1565D8] text-[11px] font-black text-white">
                    <i class="fa-solid fa-clipboard-check mr-1"></i> Finalisasi Survey
                </button>
            </div>

            <div x-show="finalizeOpen" x-transition x-cloak class="space-y-3 rounded-2xl border border-blue-100 bg-blue-50/40 p-3">
                <div>
                    <p class="text-xs font-black text-slate-900">BOQ Plan & Volume Survey</p>
                    <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                        Volume Plan terkunci. Pasangan designator M/J dengan pair code sama ditampilkan satu kali.
                    </p>
                </div>

                <div class="space-y-2">
                    @forelse($surveyBoqGroups as $group)
                        @php
                            $field = (string) $group['representative_id'];
                            $draftValue = old("volumes.{$field}");
                            if ($draftValue === null && $surveyDraftVolumes->has($field)) {
                                $draftValue = $surveyDraftVolumes->get($field);
                            }
                            if ($draftValue === null && $group['is_additional']) {
                                $draftValue = $group['quantity_survey'];
                            }
                        @endphp
                        <div class="rounded-xl border {{ $group['is_additional'] ? 'border-amber-200' : 'border-slate-200' }} bg-white p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="text-[11px] font-black text-slate-800">{{ $group['designator'] }}</p>
                                        @if($group['is_additional'])
                                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[8px] font-black uppercase text-amber-700">Tambahan</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 line-clamp-2 text-[9.5px] text-slate-400">{{ $group['item_name'] }}</p>
                                </div>
                                @if($group['is_additional'])
                                    <form method="POST" action="{{ route('waspang.survey.boq.additional.delete', [$project->id_project, $group['representative_id']]) }}" onsubmit="return confirm('Hapus designator tambahan ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600" title="Hapus tambahan">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Volume Plan</label>
                                    <div class="mt-1 flex h-9 items-center rounded-lg border border-slate-200 bg-slate-100 px-2.5 text-[11px] font-black text-slate-500">
                                        {{ $group['quantity_plan'] === null ? '—' : rtrim(rtrim(number_format($group['quantity_plan'], 2, '.', ''), '0'), '.') }} {{ $group['unit'] }}
                                    </div>
                                </div>
                                <div>
                                    <label for="survey-volume-{{ $field }}" class="text-[9px] font-bold uppercase tracking-wide text-slate-500">Volume Survey <span class="text-red-500">*</span></label>
                                    <div class="relative mt-1">
                                        <input id="survey-volume-{{ $field }}" form="surveyFinalizeForm" type="number" name="volumes[{{ $field }}]"
                                               min="0" step="1" value="{{ $draftValue }}" placeholder="0"
                                               class="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 pr-9 text-[11px] font-black text-[#1565D8] outline-none focus:border-[#1565D8] focus:ring-2 focus:ring-blue-100">
                                        <span class="pointer-events-none absolute right-2 top-2.5 text-[8px] font-bold text-slate-400">{{ $group['unit'] }}</span>
                                    </div>
                                    @error("volumes.{$field}")
                                        <p class="mt-1 text-[9px] font-bold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-[10px] text-amber-800">BOQ Plan untuk LOP ini belum tersedia.</div>
                    @endforelse
                </div>

                @if($additionalDesignatorOptions->isNotEmpty())
                    <div class="rounded-xl border border-dashed border-blue-200 bg-white p-3">
                        <p class="text-[11px] font-black text-slate-800">Tambah Designator</p>
                        <p class="mt-0.5 text-[9.5px] text-slate-400">Volume Plan otomatis kosong karena item berasal dari hasil Survey.</p>
                        <form method="POST" action="{{ route('waspang.survey.boq.additional.store', $project->id_project) }}" class="mt-2 space-y-2">
                            @csrf
                            <select id="surveyAdditionalDesignatorPicker" name="designator_id" required class="w-full rounded-xl border border-slate-300 text-xs">
                                <option value="">Cari designator tambahan…</option>
                                @foreach($additionalDesignatorOptions as $option)
                                    <option value="{{ $option['designator_id'] }}">{{ $option['designator'] }} — {{ $option['item_name'] }} ({{ $option['unit'] }})</option>
                                @endforeach
                            </select>
                            <div class="grid grid-cols-[1fr_auto] gap-2">
                                <input type="number" name="volume_survey" min="0" step="1" required placeholder="Volume Survey"
                                       class="h-10 w-full rounded-xl border border-slate-300 px-3 text-xs font-bold outline-none focus:border-[#1565D8] focus:ring-2 focus:ring-blue-100">
                                <button type="submit" class="h-10 rounded-xl bg-slate-900 px-4 text-[11px] font-black text-white">
                                    <i class="fa-solid fa-plus mr-1"></i> Tambah
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <form id="surveyFinalizeForm" method="POST" action="{{ route('waspang.survey.boq.draft', $project->id_project) }}">
                    @csrf
                </form>
                <div class="grid grid-cols-2 gap-2">
                    <button type="submit" form="surveyFinalizeForm" formnovalidate
                            class="h-11 rounded-xl border border-[#1565D8] bg-white text-xs font-black text-[#1565D8]">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Draf
                    </button>
                    <button type="submit" form="surveyFinalizeForm" formaction="{{ route('waspang.survey.finish', $project->id_project) }}"
                            onclick="return confirm('Volume Survey sudah final dan lanjut ke Perizinan?')"
                            class="h-11 rounded-xl bg-emerald-600 text-xs font-black text-white shadow-sm">
                        Selesai Survey <i class="fa-solid fa-chevron-right ml-1 text-[10px]"></i>
                    </button>
                </div>
            </div>

            @include('waspang.partials.step-action-buttons', ['stageCode' => 'survey', 'stepLabel' => 'Survey'])
        @endif

        @if($surveyRounds->isNotEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-3 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] font-black text-slate-800">Riwayat BOQ Survey</p>
                    <span class="text-[9px] font-bold text-slate-400">{{ $surveyRounds->count() }} ronde</span>
                </div>
                <div class="space-y-1.5">
                    @foreach($surveyRounds as $round)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-2">
                            <div class="min-w-0">
                                <p class="text-[10.5px] font-black text-slate-700">Ronde {{ $round->round_number }}{{ $round->round_number === 1 ? ' (Survey Awal)' : '' }}</p>
                                <p class="text-[9px] text-slate-400">
                                    {{ $round->status === 'completed' ? 'Selesai · '.optional($round->finished_at)->format('d M Y H:i') : 'Sedang berjalan' }}
                                </p>
                            </div>
                            @if($round->deviation_percent !== null)
                                <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[9px] font-black {{ $round->redesign_required ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ rtrim(rtrim(number_format($round->deviation_percent, 2, '.', ''), '0'), '.') }}%
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($canStartReSurvey)
            <form method="POST" action="{{ route('waspang.survey.re-survey.start', $project->id_project) }}"
                  onsubmit="return confirm('Mulai Re Survey? Volume Survey akan diisi ulang dari awal untuk LOP ini, tetapi histori ronde sebelumnya tetap tersimpan.')">
                @csrf
                <button type="submit" class="h-11 w-full rounded-xl border border-[#1565D8] bg-white text-xs font-black text-[#1565D8]">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Re Survey
                </button>
            </form>
        @endif
    </div>
</div>

<script>
// Stage 4e: bukti persetujuan Redesign -- foto disimpan dulu di array JS
// (bukan langsung di <input>) supaya waspang bisa BATALKAN/hapus satu-satu
// SEBELUM submit. Begitu form berhasil terkirim ke server, evidence-nya
// sudah tersimpan permanen -- SENGAJA tidak ada tombol hapus sesudahnya
// (tidak ada endpoint utk itu), beda dgn tahap memilih foto yang masih
// bisa diedit bebas.
let surveyRedesignApprovalFiles = [];

document.getElementById('surveyRedesignApprovalPhotoInput')?.addEventListener('change', async function (e) {
    const input = e.target;
    const files = Array.from(input.files).filter(f => f.type.startsWith('image/'));

    for (const file of files) {
        const compressed = (typeof compressImage === 'function') ? await compressImage(file) : file;
        surveyRedesignApprovalFiles.push({ file: compressed, url: URL.createObjectURL(compressed) });
    }

    input.value = ''; // supaya pilih ulang file yang sama tetap trigger 'change'
    renderSurveyRedesignApprovalPreview();
});

function removeSurveyRedesignApprovalPhoto(index) {
    const item = surveyRedesignApprovalFiles[index];
    if (item) URL.revokeObjectURL(item.url);
    surveyRedesignApprovalFiles.splice(index, 1);
    renderSurveyRedesignApprovalPreview();
}

function renderSurveyRedesignApprovalPreview() {
    const preview = document.getElementById('surveyRedesignApprovalPreview');
    preview.innerHTML = '';

    surveyRedesignApprovalFiles.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'relative aspect-square rounded-lg overflow-hidden bg-slate-100 border border-slate-200';
        div.innerHTML = `
            <img src="${item.url}" class="w-full h-full object-cover">
            <button type="button" onclick="removeSurveyRedesignApprovalPhoto(${index})"
                    class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center">×</button>
        `;
        preview.appendChild(div);
    });
}

document.getElementById('surveyRedesignApprovalForm')?.addEventListener('submit', function (e) {
    if (surveyRedesignApprovalFiles.length === 0) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Pilih Foto!', text: 'Mohon lampirkan minimal 1 foto/capture bukti persetujuan.', icon: 'warning', confirmButtonColor: '#1565D8', customClass: { popup: 'rounded-3xl' } });
        } else {
            alert('Mohon lampirkan minimal 1 foto/capture bukti persetujuan.');
        }
        return;
    }

    // Susun ulang FileList input tepat sebelum submit, dari array yang
    // sudah difilter waspang (hasil hapus manual sudah tidak ikut).
    const dt = new DataTransfer();
    surveyRedesignApprovalFiles.forEach(item => dt.items.add(item.file));
    document.getElementById('surveyRedesignApprovalPhotoInput').files = dt.files;

    const btn = this.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.innerText = 'Mengirim...'; }
    // Native submit tetap lanjut (tidak di-preventDefault di jalur ini).
});
</script>
