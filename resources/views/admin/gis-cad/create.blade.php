@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.gis-cad.index') }}"
           class="w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Export Baru</h1>
            <p class="text-sm text-gray-500">Pilih sumber data untuk digenerate menjadi DXF AutoCAD</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">

        {{-- Upload KML/KMZ --}}
        <div class="lg:col-span-2 rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                </div>
                <p class="text-sm font-black text-gray-900 dark:text-white">Upload File KML / KMZ</p>
            </div>
            <p class="text-xs text-gray-500 mb-4">Hasil export dari Google Earth, GPS lapangan, atau tool GIS lain. Maks. 20&nbsp;MB.</p>

            <form method="POST" action="{{ route('admin.gis-cad.upload') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf

                <label class="block">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 mb-1 block">File KML / KMZ</span>
                    <input type="file" name="file" accept=".kml,.kmz" required
                           class="w-full text-xs font-medium text-slate-600 border border-gray-300 dark:border-gray-700 rounded-xl p-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:text-white file:text-xs file:font-bold">
                </label>

                <label class="block">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 mb-1 block">Kaitkan ke Project (Opsional)</span>
                    <select name="project_id" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 text-sm">
                        <option value="">-- Tidak dikaitkan --</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id_project }}">{{ $project->project_name }} - {{ $project->pid }}</option>
                        @endforeach
                    </select>
                </label>

                <button type="submit"
                        class="w-full h-11 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black shadow-lg shadow-indigo-600/20 transition">
                    Upload &amp; Baca File
                </button>
            </form>
        </div>

        {{-- Pakai data survey existing --}}
        <div class="lg:col-span-3 rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                </div>
                <p class="text-sm font-black text-gray-900 dark:text-white">Gunakan Data Survey Existing</p>
            </div>
            <p class="text-xs text-gray-500 mb-4">Titik &amp; rute yang sudah ditagging lewat fitur Survey Lapangan.</p>

            <div class="overflow-x-auto -mx-1">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider rounded-l-lg">Survey / Project</th>
                            <th class="px-3 py-2.5 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Titik</th>
                            <th class="px-3 py-2.5 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Status</th>
                            <th class="px-3 py-2.5 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider rounded-r-lg">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($surveys as $survey)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-3 py-2.5">
                                    <p class="font-bold text-gray-900 dark:text-white text-xs">{{ $survey->displayTitle() }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $survey->project_name ?? ($survey->project?->project_name ?? '-') }}</p>
                                </td>
                                <td class="px-3 py-2.5 text-center font-bold text-indigo-600 text-xs">
                                    {{ $survey->points_count ?? '-' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    @if($survey->status === 'completed')
                                        <span class="px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-700 text-[10px] font-bold">Selesai</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-lg bg-amber-100 text-amber-700 text-[10px] font-bold">On Progress</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <form method="POST" action="{{ route('admin.gis-cad.from-survey', $survey->id_site_surveys) }}">
                                        @csrf
                                        <button type="submit" class="h-8 px-3 rounded-lg bg-blue-50 text-blue-700 text-[11px] font-black border border-blue-100 hover:bg-blue-100 transition">
                                            Pakai Ini
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-10 text-center text-xs text-gray-400">Belum ada data survey yang bisa dipakai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <a href="{{ route('admin.gis-cad.index') }}"
       class="inline-flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="m11 6-6 6 6 6"/></svg>
        Lihat Riwayat Export
    </a>

</div>
@endsection
