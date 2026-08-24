@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto space-y-4 px-4 py-6">

    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Hasil Generate BAUT</h1>
            <p class="text-sm text-gray-500">Riwayat dokumen Berita Acara Uji Terima yang sudah/sedang dibuat per LOP.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-bold">{{ session('success') }}</div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[220px]">
            <label class="text-xs font-black text-slate-500">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama LOP / PID / IHLD..." class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
        </div>
        <div>
            <label class="text-xs font-black text-slate-500">Status</label>
            <select name="status" class="mt-1 h-11 rounded-xl border-slate-300 text-sm">
                <option value="">Semua</option>
                <option value="final" {{ request('status') === 'final' ? 'selected' : '' }}>Final</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
        </div>
        <button class="h-11 px-5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-black">Cari</button>
        <a href="{{ route('admin.pt2.baut.index') }}" class="h-11 px-5 rounded-xl border border-slate-300 inline-flex items-center text-sm font-black text-slate-600">Reset</a>
    </form>

    {{-- Table List --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/50 text-[10px] uppercase font-bold text-gray-500">
                    <tr>
                        <th class="p-3">LOP</th>
                        <th class="p-3">PID</th>
                        <th class="p-3">IHLD</th>
                        <th class="p-3">STO / Branch</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Info</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($records as $record)
                        @php
                            $isFinal = $record->status === 'final';
                            $lop = $record->lop;
                            $project = $lop->project ?? null;
                        @endphp
                        <tr class="align-top">
                            <td class="p-3 font-black text-gray-900 dark:text-white">{{ $lop->lop_name ?? '-' }}</td>
                            <td class="p-3 text-gray-700 dark:text-gray-300 font-bold">{{ $project->pid ?? '-' }}</td>
                            <td class="p-3 font-mono text-cyan-600 dark:text-cyan-400">{{ $lop->id_ihld ?? '-' }}</td>
                            <td class="p-3 text-gray-500">{{ $lop->sto ?? '-' }} · {{ $lop->branch ?? '-' }}</td>
                            <td class="p-3">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase {{ $isFinal ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $isFinal ? 'Final' : 'Draft' }}
                                </span>
                            </td>
                            <td class="p-3 text-[11px] text-gray-400">
                                @if($isFinal)
                                    Digenerate {{ optional($record->generated_at)->format('d M Y H:i') }}
                                    @if($record->generatedBy) oleh {{ $record->generatedBy->name ?? '-' }} @endif
                                @else
                                    Terakhir diubah {{ $record->updated_at->format('d M Y H:i') }}
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($isFinal)
                                        <a href="{{ route('admin.pt2.baut.show', $record->id_baut_generate) }}" class="h-9 px-3 rounded-lg border border-slate-300 inline-flex items-center justify-center text-xs font-black text-slate-600 hover:bg-slate-50 whitespace-nowrap">
                                            Preview
                                        </a>
                                        <a href="{{ route('admin.pt2.baut.download', $record->id_baut_generate) }}" class="h-9 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center justify-center text-xs font-black whitespace-nowrap">
                                            Download
                                        </a>
                                        <a href="{{ route('admin.pt2.lact.editor', $record->pt2_lop_id) }}" class="h-9 px-3 rounded-lg bg-violet-600 hover:bg-violet-700 text-white inline-flex items-center justify-center text-xs font-black whitespace-nowrap">
                                            Generate LACT
                                        </a>
                                    @else
                                        <a href="{{ route('admin.pt2.baut.editor', $record->pt2_lop_id) }}" class="h-9 px-3 rounded-lg bg-amber-500 hover:bg-amber-600 text-white inline-flex items-center justify-center text-xs font-black whitespace-nowrap">
                                            Lanjutkan Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center">
                                <p class="text-sm font-bold text-gray-500">Belum ada dokumen BAUT yang digenerate.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $records->links() }}
    </div>

</div>
@endsection
