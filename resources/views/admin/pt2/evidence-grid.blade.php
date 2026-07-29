@if($photos->count() > 0)
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($photos as $photo)
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm group">
            
            {{-- Foto Area --}}
            <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank" class="block relative aspect-square bg-slate-100 overflow-hidden">
                <img src="{{ asset('storage/' . $photo->file_path) }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                
                {{-- Status Badge --}}
                <div class="absolute top-2 left-2 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider shadow-sm
                    {{ $photo->status == 'approved' ? 'bg-emerald-500 text-white' : '' }}
                    {{ $photo->status == 'rejected' ? 'bg-rose-500 text-white' : '' }}
                    {{ $photo->status == 'pending' ? 'bg-amber-400 text-amber-900' : '' }}">
                    {{ $photo->status }}
                </div>
            </a>

            {{-- Detail & Action Area --}}
            <div class="p-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase truncate" title="{{ $photo->boqItem?->designator ?? $photo->evidence_type }}">
                    {{ $photo->boqItem?->designator ?? str_replace('_', ' ', $photo->evidence_type) }}
                </p>
                <p class="text-xs font-semibold text-slate-700 mt-0.5 line-clamp-2 leading-tight min-h-[34px]">
                    {{ $photo->description ?? 'Tidak ada catatan.' }}
                </p>

                {{-- Action Buttons (Hanya muncul jika masih pending/rejected) --}}
                @if($photo->status !== 'approved')
                <div class="grid grid-cols-2 gap-2 mt-3">
                    {{-- Ganti action form ini sesuai dengan route Approve Eviden Admin Anda --}}
                    <form method="POST" action="#">
                        @csrf
                        <button type="button" class="w-full h-8 rounded-lg bg-emerald-50 hover:bg-emerald-500 hover:text-white text-emerald-600 border border-emerald-200 hover:border-emerald-500 text-[10px] font-black transition-all">
                            Approve
                        </button>
                    </form>
                    
                    <button type="button" onclick="rejectEvidence('{{ $photo->id_evidence }}')" class="w-full h-8 rounded-lg bg-rose-50 hover:bg-rose-500 hover:text-white text-rose-600 border border-rose-200 hover:border-rose-500 text-[10px] font-black transition-all">
                        Reject
                    </button>
                </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
@else
<div class="p-8 rounded-3xl bg-slate-50 border border-dashed border-slate-200 text-center flex flex-col items-center justify-center">
    <div class="w-12 h-12 rounded-full bg-slate-200/50 flex items-center justify-center text-slate-400 mb-3">
        <i class="fa-regular fa-image text-xl"></i>
    </div>
    <p class="text-sm font-bold text-slate-500">Belum ada Eviden</p>
    <p class="text-xs text-slate-400 mt-1">Teknisi belum mengunggah foto pada tahapan ini.</p>
</div>
@endif