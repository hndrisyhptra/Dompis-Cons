{{--
    Stage 4d: grid foto eviden generik (dgn overlay reject/upload-ulang +
    tombol hapus) -- diekstrak dari pola kartu upload existing (barang_tiba/
    perizinan lama) supaya bisa dipakai berulang di semua sub-step BARU
    (Perizinan/Material Delivery) tanpa duplikasi markup.

    Variabel yang WAJIB tersedia:
    - $photos (Collection<App\Models\Evidence>)
--}}
@php
    $photosRejectedCount = $photos->where('status', 'rejected')->count();
@endphp

@if($photosRejectedCount > 0)
    <div class="rounded-xl border border-red-100 bg-red-50/50 p-3 text-xs text-red-700 leading-relaxed flex items-start gap-2">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <div>
            <p class="font-bold mb-0.5">Terdapat Eviden Ditolak</p>
            <p>Silakan ketuk (tap) pada foto yang bergaris merah di bawah untuk mengunggah ulang sesuai catatan Admin.</p>
        </div>
    </div>
@endif

@if($photos->count() > 0)
    <div class="grid grid-cols-3 gap-2">
        @foreach($photos as $photo)
            <div class="relative">
                <div class="relative aspect-square rounded-xl overflow-hidden bg-slate-100 group transition-all
                    {{ $photo->status == 'rejected' ? 'border-2 border-red-500 ring-2 ring-red-200' : 'border border-slate-200' }}">

                    <div class="absolute top-1 left-1 bg-black/60 text-white text-[9px] font-black px-1.5 py-0.5 rounded flex items-center gap-1 z-10 backdrop-blur-sm">
                        @if($photo->status == 'rejected')
                            <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                        @elseif($photo->status == 'approved')
                            <i class="fa-solid fa-check-circle text-green-400"></i>
                        @endif
                        ID-{{ $photo->id_evidence }}
                    </div>

                    @if(str_ends_with(strtolower($photo->file_path ?? ''), '.pdf'))
                        <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank" class="w-full h-full flex flex-col items-center justify-center gap-1 bg-slate-50">
                            <i class="fa-solid fa-file-pdf text-red-500 text-2xl"></i>
                            <span class="text-[9px] font-bold text-slate-500">Lihat PDF</span>
                        </a>
                    @else
                        <img src="{{ asset('storage/' . $photo->file_path) }}"
                             class="w-full h-full object-cover {{ $photo->status == 'rejected' ? 'opacity-80 grayscale-[20%]' : '' }}">
                    @endif

                    @if($photo->status == 'rejected')
                        @if(!empty($photo->review_note))
                            <div class="absolute bottom-0 left-0 right-0 bg-red-600/95 text-white text-[9px] p-1.5 text-center font-bold z-10 backdrop-blur-sm leading-tight border-t border-red-500 line-clamp-2" title="{{ $photo->review_note }}">
                                {{ $photo->review_note }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('waspang.evidence.replace', $photo->id_evidence) }}" enctype="multipart/form-data"
                              class="absolute inset-0 z-20 flex items-center justify-center bg-black/60 opacity-0 hover:opacity-100 transition-opacity duration-200">
                            @csrf
                            <label class="cursor-pointer bg-[#1565D8] text-white text-[10px] font-black px-3 py-2 rounded-xl shadow-lg hover:bg-[#1565D8] transition flex flex-col items-center gap-1">
                                <i class="fa-solid fa-camera-rotate text-sm"></i>
                                Upload Ulang
                                <input type="file" name="file" class="hidden" onchange="handleReplaceFileChange(this)" accept="image/*,.sor,application/pdf">
                            </label>
                        </form>
                    @endif
                </div>

                @if($photo->status != 'approved')
                    <form method="POST" action="{{ route('waspang.evidence.delete', $photo->id_evidence) }}" class="absolute -top-1.5 -right-1.5 z-50" onsubmit="return confirm('Hapus eviden ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" title="Hapus foto" class="w-7 h-7 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center text-sm font-black shadow-lg border-2 border-white transition active:scale-90">×</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@else
    <p class="text-xs text-slate-400 italic">Belum ada eviden terlampir.</p>
@endif

<script>
// handleReplaceFileChange dipakai overlay "Upload Ulang" di atas -- dideklarasikan
// SEKALI (guard) krn partial ini bisa di-include berkali-kali per halaman.
// Revisi Step 1: tidak lagi validasi metadata (EXIF) -- foto langsung
// dikompres (kalau fungsi compressImage tersedia di halaman, lihat
// waspang/show.blade.php) sebelum form upload-ulang di-submit.
if (typeof window.handleReplaceFileChange !== 'function') {
    window.handleReplaceFileChange = async function (input) {
        const file = input.files[0];
        if (!file) return;

        if (file.type.startsWith('image/') && typeof compressImage === 'function') {
            const compressed = await compressImage(file);
            const dt = new DataTransfer();
            dt.items.add(compressed);
            input.files = dt.files;
        }

        input.form.submit();
    };
}
</script>
