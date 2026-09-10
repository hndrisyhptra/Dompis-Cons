{{--
    Stage 4d: modal "Lapor Kendala" UNIVERSAL -- dipakai di setiap
    step/sub-step (bukan cuma Persiapan). Include SEKALI per halaman,
    lalu panggil openKendalaModal('<stage_code>', '<Label Step>') dari
    tombol manapun yang butuh laporan kendala kontekstual.

    Variabel yang WAJIB tersedia di view yang meng-include partial ini:
    - $project        (App\Models\Project)
    - $kendalaCategories (Collection<App\Models\KendalaCategory>)
--}}
<div id="kendalaModalGlobal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
    <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

        <div class="bg-amber-500 text-white px-5 py-4 flex items-start justify-between shrink-0">
            <div>
                <h2 class="text-base font-black tracking-tight">Lapor Kendala</h2>
                <p id="kendalaModalStepLabel" class="text-xs text-amber-50 mt-0.5 font-medium">Step</p>
            </div>
            <button type="button" onclick="closeKendalaModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white font-black text-sm flex items-center justify-center transition">×</button>
        </div>

        <form id="kendalaModalForm" method="POST" action="{{ route('waspang.projects.issues.store', $project->id_project) }}" enctype="multipart/form-data" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
            @csrf
            <input type="hidden" name="stage_code" id="kendalaModalStageCode" value="">

            <div>
                <label class="text-xs font-black text-slate-600">Kategori Kendala</label>
                <select name="kendala_category_id" required class="mt-1.5 w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold outline-none focus:ring-2 focus:ring-amber-100 focus:border-amber-500 transition">
                    <option value="">Pilih kategori kendala</option>
                    @foreach($kendalaCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-black text-slate-600">Keterangan / Kronologi Kendala</label>
                <textarea name="description" required rows="3" placeholder="Deskripsikan hambatan lapangan secara detail..." class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium outline-none focus:ring-2 focus:ring-amber-100 focus:border-amber-500 transition resize-none"></textarea>
            </div>

            <div>
                <label class="text-xs font-black text-slate-600">Eviden Foto <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <label class="mt-1.5 flex flex-col items-center justify-center w-full min-h-[90px] border-2 border-dashed border-amber-200 rounded-2xl bg-amber-50/40 cursor-pointer hover:bg-amber-50 transition p-3">
                    <div class="text-center">
                        <i class="fa-solid fa-camera text-amber-500 text-lg"></i>
                        <p class="text-xs font-black text-amber-700 mt-1">Ambil / Pilih Foto</p>
                    </div>
                    <input type="file" name="photos[]" id="kendalaModalPhotoInput" accept="image/*" multiple class="hidden">
                </label>
                <div id="kendalaModalPreview" class="mt-2 grid grid-cols-4 gap-1.5"></div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2 shrink-0">
                <button type="button" onclick="closeKendalaModal()" class="h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-black transition">Batal</button>
                <button type="submit" class="h-11 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-black shadow-md transition">Kirim</button>
            </div>
        </form>
    </div>
</div>

<script>
function openKendalaModal(stageCode, stepLabel) {
    document.getElementById('kendalaModalStageCode').value = stageCode || '';
    document.getElementById('kendalaModalStepLabel').innerText = stepLabel || 'Step';
    document.getElementById('kendalaModalPhotoInput').value = '';
    document.getElementById('kendalaModalPreview').innerHTML = '';
    document.getElementById('kendalaModalGlobal').classList.remove('hidden');
    document.getElementById('kendalaModalGlobal').classList.add('flex');
}

function closeKendalaModal() {
    document.getElementById('kendalaModalGlobal').classList.add('hidden');
    document.getElementById('kendalaModalGlobal').classList.remove('flex');
}

// Tidak ada validasi metadata (EXIF) -- foto langsung dikompres (kalau
// compressImage tersedia di halaman, lihat waspang/show.blade.php) supaya
// ukuran upload kecil, lalu FileList input ini DIGANTI dgn versi terkompres
// (form tetap native <form> submit, tidak perlu diubah jadi fetch).
document.getElementById('kendalaModalPhotoInput')?.addEventListener('change', async function (e) {
    const preview = document.getElementById('kendalaModalPreview');
    preview.innerHTML = '';

    const input = e.target;
    const files = Array.from(input.files).filter(f => f.type.startsWith('image/'));
    const dt = new DataTransfer();

    for (const file of files) {
        const compressed = (typeof compressImage === 'function') ? await compressImage(file) : file;
        dt.items.add(compressed);

        const url = URL.createObjectURL(compressed);
        const div = document.createElement('div');
        div.className = 'aspect-square rounded-lg overflow-hidden bg-slate-100 border border-slate-200';
        div.innerHTML = `<img src="${url}" class="w-full h-full object-cover">`;
        preview.appendChild(div);
    }

    input.files = dt.files;
});

document.getElementById('kendalaModalForm')?.addEventListener('submit', function () {
    const btn = this.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.innerText = 'Mengirim...'; }
});
</script>
