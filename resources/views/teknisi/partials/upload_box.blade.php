<label class="relative flex flex-col items-center justify-center w-full h-16 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-white hover:bg-slate-50 transition">
    <span id="label-btn-{{ $key }}" class="text-xs font-bold {{ (isset($existingEvidences[$key]) && $existingEvidences[$key]->count() > 0) ? 'text-green-600' : 'text-slate-500' }}">
        {{ (isset($existingEvidences[$key]) && $existingEvidences[$key]->count() > 0) ? '✅ '.$existingEvidences[$key]->count().' Foto Siap' : '+ Tambah '.$label }}
    </span>
    <input type="file" id="input-{{ $key }}" name="evidences[{{ $key }}][]" multiple accept="image/*" class="hidden" onchange="handleFileSelect(this, '{{ $key }}')">
</label>

<div id="loading-{{ $key }}" class="hidden mt-2 text-[10px] font-bold text-blue-600 animate-pulse">Memproses foto...</div>
<div id="preview-{{ $key }}" class="grid grid-cols-4 gap-2 mt-2 empty:hidden"></div>

@if(isset($existingEvidences[$key]) && $existingEvidences[$key]->count() > 0)
    <div class="grid grid-cols-4 gap-2 mt-2">
        @foreach($existingEvidences[$key] as $ev)
            <div class="relative rounded-xl overflow-hidden border border-slate-200 aspect-square shadow-sm">
                <img src="{{ asset('storage/' . $ev->file_path) }}" class="w-full h-full object-cover">
                <button type="button" onclick="event.preventDefault(); document.getElementById('form-delete-{{ $ev->id_evidence }}').submit();" class="absolute top-1 right-1 w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center text-[9px] font-black shadow-md">✕</button>
            </div>
        @endforeach
    </div>
@endif