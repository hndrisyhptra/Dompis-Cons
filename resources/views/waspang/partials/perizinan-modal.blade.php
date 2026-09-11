{{--
    Stage 4f: modal "Add Perizinan" -- pilih kategori perizinan + catatan
    kronologi (wajib) + eviden foto/PDF opsional (boleh campur, boleh lebih
    dari 1 file), bisa disubmit berkali-kali selama LOP di tahap Perizinan.
    Lihat WaspangController::addPerizinan() & ANALISA_REFACTOR_PERSIAPAN.md
    bag. Z utk spec asal.

    Variabel yang WAJIB tersedia di view yang meng-include partial ini:
    - $project (App\Models\Project)
    - $permitCategories (Collection<App\Models\PermitCategory>)
    - $lop (App\Models\Lop) -- utk default kategori terpilih
--}}
<div id="perizinanModal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
    <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

        <div class="bg-[#1565D8] text-white px-5 py-4 flex items-start justify-between shrink-0">
            <div>
                <h2 class="text-base font-black tracking-tight">Add Perizinan</h2>
                <p class="text-xs text-blue-100 mt-0.5 font-medium">Kategori, kronologi &amp; eviden (opsional)</p>
            </div>
            <button type="button" onclick="closePerizinanModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white font-black text-sm flex items-center justify-center transition">×</button>
        </div>

        <form id="perizinanModalForm" method="POST" action="{{ route('waspang.perizinan.add', $project->id_project) }}" enctype="multipart/form-data" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
            @csrf

            <div>
                <label class="text-xs font-black text-slate-600">Kategori Perizinan</label>
                <select name="permit_category_id" required class="mt-1.5 w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-100 focus:border-[#1565D8] transition">
                    <option value="">Pilih kategori perizinan...</option>
                    @foreach($permitCategories as $cat)
                        <option value="{{ $cat->id }}" @selected($lop->permit_category_id == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('permit_category_id')
                    <p class="mt-1 text-[10px] font-bold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-xs font-black text-slate-600">Tanggal Kejadian</label>
                <input type="date" name="event_date" required value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                    class="mt-1.5 w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-100 focus:border-[#1565D8] transition">
            </div>

            <div>
                <label class="text-xs font-black text-slate-600">Catatan Kronologi</label>
                <textarea name="note" required rows="4" placeholder="Tulis perkembangan/kronologi aktivitas Perizinan..." class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium outline-none focus:ring-2 focus:ring-blue-100 focus:border-[#1565D8] transition resize-none"></textarea>
                @error('note')
                    <p class="mt-1 text-[10px] font-bold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-xs font-black text-slate-600">Eviden Foto/File BA KP (Opsional)</label>
                <input type="file" name="files[]" multiple accept="image/*,application/pdf"
                       class="mt-1.5 w-full text-xs" onchange="compressMixedFileInput(this)">
                <p class="mt-1 text-[9.5px] text-slate-400">Boleh pilih lebih dari 1 file, campur foto &amp; PDF.</p>
                @error('files.*')
                    <p class="mt-1 text-[10px] font-bold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2 shrink-0">
                <button type="button" onclick="closePerizinanModal()" class="h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-black transition">Batal</button>
                <button type="submit" class="h-11 rounded-2xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-sm font-black shadow-md transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddPerizinanModal() {
    document.getElementById('perizinanModal').classList.remove('hidden');
    document.getElementById('perizinanModal').classList.add('flex');
}

function closePerizinanModal() {
    document.getElementById('perizinanModal').classList.add('hidden');
    document.getElementById('perizinanModal').classList.remove('flex');
}

// Kompres tiap FILE GAMBAR pada input (mis. utk "Add Perizinan", yg boleh
// campur foto & PDF dalam 1 input) tanpa membuang file PDF-nya -- beda dgn
// compressFileInputPhotos() yg cuma dipakai utk input foto-only & MEMBUANG
// file non-gambar dari FileList.
async function compressMixedFileInput(input) {
    const files = Array.from(input.files);
    if (files.length === 0) return;

    const dt = new DataTransfer();
    for (const file of files) {
        if (file.type.startsWith('image/') && typeof compressImage === 'function') {
            dt.items.add(await compressImage(file));
        } else {
            dt.items.add(file);
        }
    }
    input.files = dt.files;
}

document.getElementById('perizinanModalForm')?.addEventListener('submit', function () {
    const btn = this.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.innerText = 'Menyimpan...'; }
});
</script>
