@extends('layouts.teknisi')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    @include('teknisi.partials.stepper', ['title' => 'Step 5 - Final Submit'])

    <div class="px-5 mt-6">
        
        <div class="mb-5 bg-indigo-50 border border-indigo-100 p-4 rounded-2xl flex items-start gap-3 shadow-sm">
            <span class="text-xl">🏁</span>
            <p class="text-xs font-medium text-indigo-800 leading-relaxed">
                Tahap Terakhir! Pastikan semua data Core dan Label ODP/ODC sudah benar sebelum mengirim permohonan Approval ke Admin.
            </p>
        </div>

        <form id="step5Form" action="{{ route('teknisi.pt2.storeStep5', $project->id_project) }}" method="POST">
            @csrf

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-black text-sm text-slate-800">Data Mancore PT2</h3>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Wajib Diisi</span>
                </div>
                
                <div class="p-5 space-y-4">
                    
                    {{-- Input ODP --}}
                    <div>
                        <label class="block text-xs font-black text-slate-500 mb-1.5">ODP Label <span class="text-red-500">*</span></label>
                        <input type="text" name="odp_label" value="{{ $mancore->odp_label ?? '' }}" placeholder="Contoh: ODP-JKT-FAB/01" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition" required>
                    </div>

                    {{-- Input ODC --}}
                    <div>
                        <label class="block text-xs font-black text-slate-500 mb-1.5">ODC Label <span class="text-red-500">*</span></label>
                        <input type="text" name="odc_label" value="{{ $mancore->odc_label ?? '' }}" placeholder="Contoh: ODC-JKT-FA" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-2">
                        {{-- Input Distribusi Core --}}
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1.5">Distribusi Core <span class="text-red-500">*</span></label>
                            <input type="text" name="distribusi_core" value="{{ $mancore->distribusi_core ?? '' }}" placeholder="Contoh: 12" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition" required>
                        </div>

                        {{-- Input Feeder Core --}}
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1.5">Feeder Core <span class="text-red-500">*</span></label>
                            <input type="text" name="feeder_core" value="{{ $mancore->feeder_core ?? '' }}" placeholder="Contoh: 4" class="w-full h-11 rounded-xl border border-slate-200 px-4 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition" required>
                        </div>
                    </div>

                </div>
            </div>

            {{-- TOMBOL SUBMIT FINAL --}}
            <button type="submit" id="btnSubmit" disabled class="w-full h-14 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                </svg>
                <span class="text-base tracking-wide">Simpan & Request Approval</span>
            </button>
        </form>

    </div>
</div>

{{-- BOTTOM NAV --}}
@include('teknisi.partials.bottom-nav', ['active' => 'home'])

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById('step5Form');
        const btn = document.getElementById('btnSubmit');

        // Fungsi Validasi Form Otomatis
        function checkForm() {
            let odp = document.querySelector('input[name="odp_label"]').value.trim();
            let odc = document.querySelector('input[name="odc_label"]').value.trim();
            let dist = document.querySelector('input[name="distribusi_core"]').value.trim();
            let feeder = document.querySelector('input[name="feeder_core"]').value.trim();

            if (odp && odc && dist && feeder) {
                btn.disabled = false;
                btn.className = 'w-full h-14 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2 active:scale-95';
            } else {
                btn.disabled = true;
                btn.className = 'w-full h-14 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl shadow-sm transition-all flex items-center justify-center gap-2';
            }
        }

        form.addEventListener('input', checkForm);
        checkForm(); // Panggil saat load untuk cek jika data sudah ada (edit mode)
    });
</script>
@endsection