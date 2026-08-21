@extends('layouts.surveyor')

@section('title', 'Survey Baru')

@section('content')

    <div class="bg-blue-700 px-5 pt-6 pb-10 rounded-b-[2rem] relative overflow-hidden">

        <div class="relative flex items-center gap-3">
            <a href="{{ route('surveyor.index') }}" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center border border-white/10">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <p class="text-blue-200 text-xs font-semibold tracking-wide uppercase">Survey Lapangan</p>
                <h1 class="text-white text-lg font-black">Mulai Survey Baru</h1>
            </div>
        </div>
    </div>

    <div class="px-5 -mt-5 relative">
        <form method="POST" action="{{ route('surveyor.store') }}" class="bg-white rounded-2xl shadow-lg shadow-slate-900/5 border border-slate-100 p-5 space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Judul Survey <span class="text-red-500">*</span></label>
                <input type="text" name="title" required value="{{ old('title') }}"
                       placeholder="Contoh: Survey Rute Kabel STO Cempaka"
                       class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
                @error('title')<p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kaitkan ke Project (opsional)</label>
                <select name="project_id" class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
                    <option value="">-- Tidak dikaitkan / project belum ada di sistem --</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id_project }}" {{ old('project_id') == $project->id_project ? 'selected' : '' }}>
                            {{ $project->project_name }} @if($project->pid) (PID: {{ $project->pid }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('project_id')<p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Atau Nama Lokasi / Project Manual</label>
                <input type="text" name="project_name" value="{{ old('project_name') }}"
                       placeholder="Diisi jika project belum terdaftar di sistem"
                       class="w-full h-11 rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Awal (opsional)</label>
                <textarea name="notes" rows="3" placeholder="Catatan kondisi lapangan, target area, dsb."
                          class="w-full rounded-xl border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">{{ old('notes') }}</textarea>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-start gap-2.5">
                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                <p class="text-[11px] text-blue-700 leading-relaxed">
                    Setelah survey dibuat, kamu akan diarahkan ke halaman peta untuk mulai tagging titik tiang eksisting, catuan (ODC/ODP/JC), menggambar rute kabel, dan menentukan titik ending site.
                </p>
            </div>

            <button type="submit"
                    class="w-full h-12 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-700 text-white text-sm font-black shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2">
                <i class="fa-solid fa-map-location-dot"></i>
                Mulai Survey &amp; Buka Peta
            </button>
        </form>
    </div>

@endsection

@section('bottom-nav')
    @include('surveyor.partials.bottom-nav', ['active' => ''])
@endsection
