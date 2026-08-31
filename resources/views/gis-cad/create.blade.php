@extends('layouts.surveyor')

@section('title', 'Export AutoCAD')

@section('content')

    {{-- Top Bar --}}
    <div class="sticky top-0 z-30 bg-indigo-700 rounded-b-[1.5rem] shadow-md px-4 py-3 flex items-center gap-3 safe-top">
        <a href="{{ route('gis-cad.index') }}" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center shrink-0">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-black text-white truncate">Export AutoCAD (DXF)</p>
            <p class="text-[11px] text-indigo-100">GIS to CAD Generator</p>
        </div>
    </div>

    <div class="px-4 pt-4 space-y-4">

        {{-- Upload KML/KMZ --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-indigo-600/10 text-indigo-700 flex items-center justify-center">
                    <i class="fa-solid fa-file-arrow-up text-sm"></i>
                </div>
                <p class="text-sm font-black text-slate-900">Upload File KML / KMZ</p>
            </div>
            <p class="text-[11px] text-slate-500 mb-3">Hasil survey dari Google Earth, GPS lapangan, atau tool lain. Maks. 20 MB.</p>

            <form method="POST" action="{{ route('gis-cad.upload') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="file" accept=".kml,.kmz" required
                       class="w-full text-xs font-medium text-slate-600 border border-slate-200 rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:text-white file:text-xs file:font-bold">

                @if(isset($projects))
                    <select name="project_id" class="w-full h-11 rounded-xl border-slate-200 text-sm">
                        <option value="">(Opsional) Kaitkan ke Project...</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id_project }}">{{ $project->project_name }} - {{ $project->pid }}</option>
                        @endforeach
                    </select>
                @endif

                <button type="submit"
                        class="w-full h-11 rounded-xl bg-indigo-600 text-white text-sm font-black shadow-lg shadow-indigo-600/30">
                    <i class="fa-solid fa-upload mr-1"></i> Upload & Baca File
                </button>
            </form>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex-1 h-px bg-slate-200"></div>
            <span class="text-[10px] font-bold text-slate-400 uppercase">Atau</span>
            <div class="flex-1 h-px bg-slate-200"></div>
        </div>

        {{-- Pakai data survey existing --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-blue-600/10 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-database text-sm"></i>
                </div>
                <p class="text-sm font-black text-slate-900">Gunakan Data Survey Existing</p>
            </div>
            <p class="text-[11px] text-slate-500 mb-3">Titik &amp; rute yang sudah ditagging lewat Survey Lapangan Dompis Cons.</p>

            <div class="space-y-2">
                @forelse($surveys as $survey)
                    <div class="flex items-center justify-between gap-3 border border-slate-100 rounded-xl p-3">
                        <div class="min-w-0">
                            <p class="text-xs font-black text-slate-800 truncate">{{ $survey->displayTitle() }}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                {{ $survey->status === 'completed' ? '✅ Selesai' : '⏳ On Progress' }}
                                @if($survey->project_name) &middot; {{ $survey->project_name }} @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('gis-cad.from-survey', $survey->id_site_surveys) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="h-9 px-3 rounded-lg bg-blue-50 text-blue-700 text-[11px] font-black border border-blue-100">
                                Pakai Ini
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-center text-xs text-slate-400 py-6">Belum ada data survey yang bisa dipakai.</p>
                @endforelse
            </div>
        </div>

        {{-- Riwayat --}}
        <a href="{{ route('gis-cad.index') }}"
           class="flex items-center justify-center gap-2 h-11 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold">
            <i class="fa-solid fa-clock-rotate-left"></i> Lihat Riwayat Export
        </a>
    </div>

@endsection
