{{--
    Stage 4d: baris tombol "Lapor Kendala" + "Update Kronologi" UNIVERSAL --
    muncul di tiap sub-step (setiap aktivitas), memanggil modal global
    (kendala-modal.blade.php / kronologi-modal.blade.php) dgn stage_code
    sesuai sub-step yg sedang dibuka.

    Parameter WAJIB:
    - $stageCode  (string) -- code project_stages sub-step ini (survey/perizinan/material_delivery/dst)
    - $stepLabel  (string) -- label tampilan utk judul modal

    Parameter OPSIONAL:
    - $showKronologiButton (bool, default true) -- Stage 4f: di sub-step
      Perizinan tombol "Update Kronologi" ini SENGAJA tidak ditampilkan lagi
      (kronologi Perizinan sekarang dicatat lewat "Add Perizinan", lihat
      WaspangController::addPerizinan()), supaya sub-step ini cuma
      menyisakan "Lapor Kendala" + "Perizinan Selesai" (tombol terakhir ada
      di luar partial ini, lihat waspang/show.blade.php).
--}}
@php $showKronologiButton = $showKronologiButton ?? true; @endphp
<div class="grid {{ $showKronologiButton ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 pt-1">
    <button type="button" onclick="openKendalaModal('{{ $stageCode }}', '{{ $stepLabel }}')"
            class="h-9 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-700 text-[11px] font-black transition flex items-center justify-center gap-1.5">
        <i class="fa-solid fa-triangle-exclamation"></i> Lapor Kendala
    </button>
    @if($showKronologiButton)
        <button type="button" onclick="openKronologiModal('{{ $stageCode }}', '{{ $stepLabel }}')"
                class="h-9 rounded-xl border border-blue-200 bg-blue-50 hover:bg-blue-100 text-[#1565D8] text-[11px] font-black transition flex items-center justify-center gap-1.5">
            <i class="fa-solid fa-clock-rotate-left"></i> Update Kronologi
        </button>
    @endif
</div>
