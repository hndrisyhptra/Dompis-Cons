@extends('layouts.sdi')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Section AF: daftar LOP REGULER (model Lop, BUKAN Pt2Lop) yang sudah
    sampai status_progress 'fi_ogp_golive' -- submission dokumen dari Admin
    sudah lengkap, tinggal menunggu SDI upload capture UIM utk resmi Golive. --}}
    <div class="mb-6 bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">Verifikasi Golive (LOP Reguler)</h1>
            <p class="text-sm text-gray-500 mt-1">Daftar LOP yang sudah selesai tahap FI-OGP Golive, menunggu verifikasi capture UIM</p>
        </div>
        <form method="GET" action="{{ route('sdi.golive.index') }}" class="w-full md:w-auto relative">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari LOP, IHLD, STO..."
                   class="w-full md:w-80 h-11 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 pl-10 pr-4 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</div>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 border-b border-gray-100 dark:border-gray-800">
                        <th class="py-3 px-5 font-semibold">LOP</th>
                        <th class="py-3 px-5 font-semibold">STO</th>
                        <th class="py-3 px-5 font-semibold">Dokumen FI-OGP</th>
                        <th class="py-3 px-5 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lops as $lop)
                        @php
                            $complete = $lop->goliveSubmission?->isComplete() ?? false;
                        @endphp
                        <tr class="border-b border-gray-50 dark:border-gray-800/60 last:border-0">
                            <td class="py-3 px-5">
                                <p class="font-bold text-gray-800 dark:text-gray-200">{{ $lop->lop_name }}</p>
                                <p class="text-xs text-gray-400">{{ $lop->id_ihld }}</p>
                            </td>
                            <td class="py-3 px-5 text-gray-600 dark:text-gray-300">{{ $lop->sto }}</td>
                            <td class="py-3 px-5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $complete ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $complete ? 'Lengkap' : 'Belum Lengkap' }}
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <a href="{{ route('sdi.golive.show', $lop->id_lop) }}" class="inline-flex items-center h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition">
                                    Verifikasi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-sm text-gray-400">Belum ada LOP yang menunggu verifikasi Golive.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lops->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                {{ $lops->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
