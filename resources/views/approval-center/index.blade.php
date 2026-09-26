@extends($layout)

@section('title', 'Pusat Approval')

@section('content')
@php
    $isMobileLayout = in_array($layout, ['layouts.waspang', 'layouts.teknisi', 'layouts.surveyor'], true);
    $agingLabels = [
        'fresh' => '< 24 jam',
        'warning' => '24–48 jam',
        'overdue' => '> 48 jam',
    ];
@endphp

<div class="{{ $isMobileLayout ? 'min-h-screen pb-28' : 'mx-auto max-w-7xl' }} space-y-4 bg-slate-50 p-4 text-slate-800 dark:bg-slate-950 dark:text-slate-200 sm:p-5">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-slate-700 dark:bg-slate-300"></span>
                    Monitoring Approval
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">Pusat Approval</h1>
                <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500 sm:text-sm">
                    Daftar terpusat LOP PT2 dan PT3 yang mempunyai eviden menunggu persetujuan Admin.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if($isAdmin)
                    <a href="{{ route('approval-center.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'mine'])) }}"
                       class="inline-flex h-10 items-center justify-center rounded-lg border px-4 text-xs font-bold transition
                       {{ $scope === 'mine' ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                        Inbox Saya
                    </a>
                    <a href="{{ route('approval-center.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'all'])) }}"
                       class="inline-flex h-10 items-center justify-center rounded-lg border px-4 text-xs font-bold transition
                       {{ $scope === 'all' ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                        Semua Antrean
                    </a>
                @else
                    <span class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-4 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">Mode Monitoring</span>
                @endif
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">LOP Menunggu</p><p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['lop_count']) }}</p></div>
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" /></svg>
            </div>
            <p class="mt-1 text-[11px] text-slate-400">Total LOP perlu ditinjau</p>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Eviden Pending</p><p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['evidence_count']) }}</p></div>
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3h4.5M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            </div>
            <p class="mt-1 text-[11px] text-slate-400">Total file belum diputuskan</p>
        </article>

        <article class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Prioritas</p><p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['overdue_count']) }}</p></div>
                <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </div>
            <p class="mt-1 text-[11px] font-medium text-slate-500">Menunggu lebih dari 48 jam</p>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Branch</p><p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['branch_count']) }}</p></div>
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m7.843 4.582A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918" /></svg>
            </div>
            <p class="mt-1 text-[11px] text-slate-400">Sebaran antrean aktif</p>
        </article>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div><h2 class="text-sm font-bold text-slate-900 dark:text-white">Filter Antrean</h2><p class="mt-0.5 text-[10px] text-slate-400">Gunakan filter untuk mempersempit daftar LOP.</p></div>
            @if(request()->hasAny(['search', 'source', 'branch', 'stage', 'aging']))
                <a href="{{ route('approval-center.index', $isAdmin ? ['scope' => $scope] : []) }}" class="text-[11px] font-bold text-slate-500 underline decoration-slate-300 underline-offset-4 hover:text-slate-900 dark:hover:text-white">Reset filter</a>
            @endif
        </div>

        <form method="GET" action="{{ route('approval-center.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            @if($isAdmin)<input type="hidden" name="scope" value="{{ $scope }}">@endif
            <label class="relative sm:col-span-2">
                <span class="sr-only">Cari LOP</span>
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" /></svg>
                <input name="search" value="{{ request('search') }}" placeholder="Cari LOP, PID, STO, atau Admin" class="h-10 w-full rounded-lg border-slate-200 bg-white pl-10 pr-3 text-xs font-medium focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800">
            </label>
            <select name="source" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800">
                <option value="all">Semua Project</option>
                <option value="pt3" @selected(request('source') === 'pt3')>PT3 / Reguler</option>
                <option value="pt2" @selected(request('source') === 'pt2')>PT2</option>
            </select>
            <select name="branch" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800">
                <option value="">Semua Branch</option>
                @foreach($availableBranches as $branch)<option value="{{ $branch }}" @selected(request('branch') === $branch)>{{ $branch }}</option>@endforeach
            </select>
            <select name="stage" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800">
                <option value="">Semua Tahap</option>
                @foreach($availableStages as $stage)<option value="{{ $stage['code'] }}" @selected(request('stage') === $stage['code'])>{{ $stage['label'] }}</option>@endforeach
            </select>
            <select name="aging" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800">
                <option value="all">Semua Umur</option>
                <option value="fresh" @selected(request('aging') === 'fresh')>&lt; 24 jam</option>
                <option value="warning" @selected(request('aging') === 'warning')>24–48 jam</option>
                <option value="overdue" @selected(request('aging') === 'overdue')>&gt; 48 jam</option>
            </select>
            <div class="sm:col-span-2 lg:col-span-6 lg:text-right">
                <button class="h-10 w-full rounded-lg bg-slate-900 px-5 text-xs font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 sm:w-auto">Terapkan Filter</button>
            </div>
        </form>
    </section>

    @if($stageSummary->isNotEmpty())
        <section class="flex gap-2 overflow-x-auto pb-1">
            @foreach($stageSummary as $stage)
                <span class="inline-flex shrink-0 items-center gap-2 rounded border border-slate-200 bg-white px-3 py-1.5 text-[10px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    {{ $stage['label'] }} <span class="font-bold text-slate-950 dark:text-white">{{ $stage['count'] }}</span>
                </span>
            @endforeach
        </section>
    @endif

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
            <div><h2 class="text-sm font-bold text-slate-900 dark:text-white">LOP Menunggu Approval</h2><p class="mt-0.5 text-[10px] text-slate-400">Diurutkan dari antrean paling lama.</p></div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $items->total() }} LOP</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-950/60">
                        <th class="px-4 py-3">LOP / Project</th><th class="px-4 py-3">Lokasi</th><th class="px-4 py-3">Tahap Menunggu</th><th class="px-4 py-3 text-center">Eviden</th><th class="px-4 py-3">Umur Antrean</th><th class="px-4 py-3">Uploader</th><th class="px-4 py-3">Admin Approval</th><th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($items as $item)
                        @php $canReview = ($item['source'] === 'pt3' && $canReviewPt3) || ($item['source'] === 'pt2' && $canReviewPt2); @endphp
                        <tr class="align-top transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3.5">
                                <div class="max-w-[250px]">
                                    <div class="mb-1 flex items-center gap-2"><span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $item['source_label'] }}</span><span class="truncate font-mono text-[9px] text-slate-400">{{ $item['pid'] }}</span></div>
                                    <p class="truncate text-xs font-bold text-slate-900 dark:text-white" title="{{ $item['lop_name'] }}">{{ $item['lop_name'] }}</p>
                                    <p class="mt-1 truncate text-[10px] text-slate-400" title="{{ $item['project_name'] }}">{{ $item['project_name'] }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3.5"><p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $item['branch'] }}</p><p class="mt-1 text-[10px] text-slate-400">STO {{ $item['sto'] }} &middot; {{ $item['program'] }}</p></td>
                            <td class="px-4 py-3.5">
                                <div class="flex max-w-[240px] flex-wrap gap-1">
                                    @foreach($item['stages'] as $stage)<span class="rounded border border-slate-200 bg-white px-2 py-1 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $stage['label'] }} <b class="text-slate-950 dark:text-white">{{ $stage['count'] }}</b></span>@endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center"><span class="inline-flex min-w-8 justify-center rounded border border-slate-300 bg-slate-50 px-2 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ $item['pending_count'] }}</span></td>
                            <td class="px-4 py-3.5"><p class="text-xs font-semibold {{ $item['aging_bucket'] === 'overdue' ? 'text-slate-950 underline decoration-slate-400 underline-offset-4 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}">{{ $item['age_label'] }}</p><p class="mt-1 text-[9px] font-medium uppercase tracking-wider text-slate-400">{{ $agingLabels[$item['aging_bucket']] }}</p></td>
                            <td class="px-4 py-3.5"><p class="max-w-[150px] truncate text-xs font-medium text-slate-700 dark:text-slate-200" title="{{ $item['uploaders']->implode(', ') }}">{{ $item['uploaders']->implode(', ') ?: '-' }}</p><p class="mt-1 text-[9px] text-slate-400">Update {{ optional($item['latest_at'])->format('d M Y H:i') }}</p></td>
                            <td class="px-4 py-3.5"><p class="max-w-[150px] truncate text-xs font-medium text-slate-700 dark:text-slate-200" title="{{ $item['admin_name'] }}">{{ $item['admin_name'] }}</p></td>
                            <td class="px-4 py-3.5 text-right">
                                @if($canReview && $item['review_url'])
                                    <a href="{{ $item['review_url'] }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-3 text-[10px] font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">Review <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg></a>
                                @else
                                    <span class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-[9px] font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-800">Monitoring saja</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-14 text-center"><svg class="mx-auto h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg><h3 class="mt-3 text-sm font-bold text-slate-900 dark:text-white">Tidak ada antrean yang cocok</h3><p class="mt-1 text-xs text-slate-400">Semua eviden sudah ditangani atau ubah filter pencarian.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())<div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $items->links() }}</div>@endif
    </section>
</div>

@if($layout === 'layouts.waspang')
    @include('waspang.partials.bottom-nav', ['active' => 'approval'])
@elseif($layout === 'layouts.teknisi')
    @include('teknisi.partials.bottom-nav', ['active' => 'approval'])
@elseif($layout === 'layouts.surveyor')
    @section('bottom-nav')
        @include('surveyor.partials.bottom-nav', ['active' => 'approval'])
    @endsection
@endif
@endsection
