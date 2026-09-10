{{--
    Stage 4d: modal "Update Kronologi" UNIVERSAL -- dipakai di setiap
    step/sub-step. Include SEKALI per halaman, panggil
    openKronologiModal('<stage_code>', '<Label Step>') dari tombol manapun.

    Variabel yang WAJIB tersedia di view yang meng-include partial ini:
    - $project (App\Models\Project)
--}}
<div id="kronologiModalGlobal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
    <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

        <div class="bg-[#1565D8] text-white px-5 py-4 flex items-start justify-between shrink-0">
            <div>
                <h2 class="text-base font-black tracking-tight">Update Kronologi</h2>
                <p id="kronologiModalStepLabel" class="text-xs text-blue-100 mt-0.5 font-medium">Step</p>
            </div>
            <button type="button" onclick="closeKronologiModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white font-black text-sm flex items-center justify-center transition">×</button>
        </div>

        <form id="kronologiModalForm" method="POST" action="{{ route('waspang.kronologi.store', $project->id_project) }}" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
            @csrf
            <input type="hidden" name="stage_code" id="kronologiModalStageCode" value="">

            <div>
                <label class="text-xs font-black text-slate-600">Tanggal Kejadian</label>
                <input type="date" name="event_date" required value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                    class="mt-1.5 w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-100 focus:border-[#1565D8] transition">
            </div>

            <div>
                <label class="text-xs font-black text-slate-600">Catatan Kronologi</label>
                <textarea name="note" required rows="4" placeholder="Tulis perkembangan/kronologi aktivitas hari ini..." class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium outline-none focus:ring-2 focus:ring-blue-100 focus:border-[#1565D8] transition resize-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2 shrink-0">
                <button type="button" onclick="closeKronologiModal()" class="h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-black transition">Batal</button>
                <button type="submit" class="h-11 rounded-2xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-sm font-black shadow-md transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openKronologiModal(stageCode, stepLabel) {
    document.getElementById('kronologiModalStageCode').value = stageCode || '';
    document.getElementById('kronologiModalStepLabel').innerText = stepLabel || 'Step';
    document.getElementById('kronologiModalGlobal').classList.remove('hidden');
    document.getElementById('kronologiModalGlobal').classList.add('flex');
}

function closeKronologiModal() {
    document.getElementById('kronologiModalGlobal').classList.add('hidden');
    document.getElementById('kronologiModalGlobal').classList.remove('flex');
}

document.getElementById('kronologiModalForm')?.addEventListener('submit', function () {
    const btn = this.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.innerText = 'Menyimpan...'; }
});
</script>
