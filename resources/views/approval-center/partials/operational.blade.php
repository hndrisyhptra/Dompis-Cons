@php
    $operationalQuery = fn (array $overrides = []) => array_merge(
        request()->except('page'),
        ['tab' => 'operational'],
        $overrides
    );
@endphp

<section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Monitoring Operational</h2>
            <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500">
                Aging dihitung sejak LOP masuk tahap aktif. Bottleneck mengikuti batas
                <b class="text-slate-800 dark:text-slate-200">{{ $threshold_days }} hari kalender</b>.
                Aktivitas terakhir tetap ditampilkan terpisah agar PIC dapat membedakan proses lama dengan LOP yang masih bergerak.
            </p>
        </div>

        <form method="GET" action="{{ route('approval-center.index') }}" class="flex items-end gap-2">
            <input type="hidden" name="tab" value="operational">
            <label>
                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Batas bottleneck</span>
                <select name="threshold" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-semibold dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @foreach([1, 3, 7, 14, 30] as $days)
                        <option value="{{ $days }}" @selected($threshold_days === $days)>{{ $days }} hari</option>
                    @endforeach
                </select>
            </label>
            <button class="h-10 rounded-lg bg-slate-900 px-4 text-xs font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">Terapkan</button>
        </form>
    </div>
</section>

<section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
    <a href="{{ route('approval-center.index', $operationalQuery(['focus' => 'active'])) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-600">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">LOP Aktif</p>
        <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['active_lop_count']) }}</p>
        <p class="mt-1 text-[11px] text-slate-400">PT2 dan PT3 belum final</p>
    </a>
    <a href="{{ route('approval-center.index', $operationalQuery(['focus' => 'bottleneck'])) }}" class="rounded-lg border border-slate-300 bg-white p-4 shadow-sm transition hover:border-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-500">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Bottleneck ≥ {{ $threshold_days }} Hari</p>
        <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['bottleneck_count']) }}</p>
        <p class="mt-1 text-[11px] text-slate-400">Terlalu lama pada tahap aktif</p>
    </a>
    <a href="{{ route('approval-center.index', $operationalQuery(['focus' => 'unassigned'])) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-600">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Belum Assign Pelaksana</p>
        <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['unassigned_count']) }}</p>
        <p class="mt-1 text-[11px] text-slate-400">Sudah ada Admin, belum ada Waspang/Teknisi</p>
    </a>
    <a href="{{ route('approval-center.index', $operationalQuery(['focus' => 'pending'])) }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-600">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Transaksi Pending Approval</p>
        <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['pending_count']) }}</p>
        <p class="mt-1 text-[11px] text-slate-400">Tersebar pada {{ number_format($summary['pending_admin_count']) }} Admin</p>
    </a>
</section>

<section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Filter Drill-down</h2>
            <p class="mt-0.5 text-[10px] text-slate-400">Klik angka pada summary atau gunakan filter berikut.</p>
        </div>
        @if(request()->hasAny(['search', 'source', 'branch', 'stage', 'admin', 'focus']))
            <a href="{{ route('approval-center.index', ['tab' => 'operational', 'threshold' => $threshold_days]) }}" class="text-[11px] font-bold text-slate-500 underline decoration-slate-300 underline-offset-4 hover:text-slate-900 dark:hover:text-white">Reset filter</a>
        @endif
    </div>

    <form method="GET" action="{{ route('approval-center.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-7">
        <input type="hidden" name="tab" value="operational">
        <input type="hidden" name="threshold" value="{{ $threshold_days }}">
        <label class="relative sm:col-span-2">
            <span class="sr-only">Cari LOP</span>
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" /></svg>
            <input name="search" value="{{ request('search') }}" placeholder="Cari LOP, PID, IHLD, STO, Admin..." class="h-10 w-full rounded-lg border-slate-200 bg-white pl-10 pr-3 text-xs font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-white">
        </label>
        <select name="source" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            <option value="all">Semua Project</option>
            <option value="pt3" @selected(request('source') === 'pt3')>PT3 / Reguler</option>
            <option value="pt2" @selected(request('source') === 'pt2')>PT2</option>
        </select>
        <select name="branch" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            <option value="">Semua Branch</option>
            @foreach($available_branches as $branch)
                <option value="{{ $branch }}" @selected(request('branch') === $branch)>{{ $branch }}</option>
            @endforeach
        </select>
        <select name="admin" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            <option value="">Semua Admin</option>
            @foreach($available_admins as $admin)
                <option value="{{ $admin['key'] }}" @selected(request('admin') === $admin['key'])>{{ $admin['name'] }}</option>
            @endforeach
        </select>
        <select name="stage" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            <option value="">Semua Tahap</option>
            @foreach($available_stages as $stage)
                <option value="{{ $stage['code'] }}" @selected(request('stage') === $stage['code'])>{{ $stage['label'] }}</option>
            @endforeach
        </select>
        <select name="focus" class="h-10 rounded-lg border-slate-200 bg-white text-xs font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            <option value="all">Semua Kondisi</option>
            <option value="active" @selected($operationalFocus === 'active')>LOP Aktif</option>
            <option value="bottleneck" @selected($operationalFocus === 'bottleneck')>Bottleneck</option>
            <option value="unassigned" @selected($operationalFocus === 'unassigned')>Belum Assign</option>
            <option value="pending" @selected($operationalFocus === 'pending')>Pending Approval</option>
        </select>
        <div class="sm:col-span-2 xl:col-span-7 xl:text-right">
            <button class="h-10 w-full rounded-lg bg-slate-900 px-5 text-xs font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 sm:w-auto">Terapkan Filter</button>
        </div>
    </form>
</section>

<section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
    <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Summary per Admin</h2>
            <p class="mt-0.5 text-[10px] text-slate-400">Menunjukkan beban aktif, assignment, dan transaksi pending setiap Admin.</p>
        </div>
        <div class="max-h-[360px] overflow-auto">
            <table class="w-full min-w-[620px] text-left text-xs">
                <thead class="sticky top-0 bg-slate-50 text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950">
                    <tr><th class="px-4 py-3">Admin</th><th class="px-3 py-3 text-center">LOP</th><th class="px-3 py-3 text-center">Bottleneck</th><th class="px-3 py-3 text-center">Belum Assign</th><th class="px-3 py-3 text-center">Approval Pending</th><th class="px-3 py-3 text-center">Tertua</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($admin_summary as $admin)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200"><a class="hover:underline" href="{{ route('approval-center.index', $operationalQuery(['admin' => $admin['key'], 'focus' => 'all'])) }}">{{ $admin['admin_name'] }}</a></td>
                            <td class="px-3 py-3 text-center"><a class="font-bold hover:underline" href="{{ route('approval-center.index', $operationalQuery(['admin' => $admin['key'], 'focus' => 'active'])) }}">{{ $admin['lop_count'] }}</a></td>
                            <td class="px-3 py-3 text-center"><a class="font-bold hover:underline" href="{{ route('approval-center.index', $operationalQuery(['admin' => $admin['key'], 'focus' => 'bottleneck'])) }}">{{ $admin['bottleneck_count'] }}</a></td>
                            <td class="px-3 py-3 text-center"><a class="font-bold hover:underline" href="{{ route('approval-center.index', $operationalQuery(['admin' => $admin['key'], 'focus' => 'unassigned'])) }}">{{ $admin['unassigned_count'] }}</a></td>
                            <td class="px-3 py-3 text-center"><a class="font-bold hover:underline" href="{{ route('approval-center.index', $operationalQuery(['admin' => $admin['key'], 'focus' => 'pending'])) }}">{{ $admin['pending_count'] }}</a></td>
                            <td class="px-3 py-3 text-center font-semibold text-slate-600 dark:text-slate-300">{{ $admin['oldest_days'] }} hari</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada data Admin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Summary per Branch</h2>
            <p class="mt-0.5 text-[10px] text-slate-400">Klik angka untuk membuka LOP penyebab aging pada Branch terkait.</p>
        </div>
        <div class="max-h-[360px] overflow-auto">
            <table class="w-full min-w-[620px] text-left text-xs">
                <thead class="sticky top-0 bg-slate-50 text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950">
                    <tr><th class="px-4 py-3">Branch</th><th class="px-3 py-3 text-center">LOP</th><th class="px-3 py-3 text-center">Bottleneck</th><th class="px-3 py-3 text-center">Belum Assign</th><th class="px-3 py-3 text-center">Approval Pending</th><th class="px-3 py-3 text-center">Tertua</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($branch_summary as $branch)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200"><a class="hover:underline" href="{{ route('approval-center.index', $operationalQuery(['branch' => $branch['branch'], 'focus' => 'all'])) }}">{{ $branch['branch'] }}</a></td>
                            <td class="px-3 py-3 text-center"><a class="font-bold hover:underline" href="{{ route('approval-center.index', $operationalQuery(['branch' => $branch['branch'], 'focus' => 'active'])) }}">{{ $branch['lop_count'] }}</a></td>
                            <td class="px-3 py-3 text-center"><a class="font-bold hover:underline" href="{{ route('approval-center.index', $operationalQuery(['branch' => $branch['branch'], 'focus' => 'bottleneck'])) }}">{{ $branch['bottleneck_count'] }}</a></td>
                            <td class="px-3 py-3 text-center"><a class="font-bold hover:underline" href="{{ route('approval-center.index', $operationalQuery(['branch' => $branch['branch'], 'focus' => 'unassigned'])) }}">{{ $branch['unassigned_count'] }}</a></td>
                            <td class="px-3 py-3 text-center"><a class="font-bold hover:underline" href="{{ route('approval-center.index', $operationalQuery(['branch' => $branch['branch'], 'focus' => 'pending'])) }}">{{ $branch['pending_count'] }}</a></td>
                            <td class="px-3 py-3 text-center font-semibold text-slate-600 dark:text-slate-300">{{ $branch['oldest_days'] }} hari</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada data Branch.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Detail LOP & Transaksi Penyebab Aging</h2>
            <p class="mt-0.5 text-[10px] text-slate-400">Urutan prioritas dimulai dari umur tahap paling lama.</p>
        </div>
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ number_format($operationalItems->total()) }} LOP</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1460px] border-collapse text-left">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-950/60">
                    <th class="px-4 py-3">LOP / Project</th><th class="px-4 py-3">Branch / STO</th><th class="px-4 py-3">Admin</th><th class="px-4 py-3">Pelaksana</th><th class="px-4 py-3">Tahap</th><th class="px-4 py-3 text-center">Umur Tahap</th><th class="px-4 py-3 text-center">Tidak Bergerak</th><th class="px-4 py-3 text-center">Approval</th><th class="px-4 py-3">Update Terakhir</th><th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($operationalItems as $item)
                    @php $canReview = ($item['source'] === 'pt3' && $canReviewPt3) || ($item['source'] === 'pt2' && $canReviewPt2); @endphp
                    <tr class="align-top hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3.5">
                            <div class="max-w-[260px]">
                                <div class="mb-1 flex items-center gap-2"><span class="rounded-lg border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $item['source_label'] }}</span><span class="truncate font-mono text-[9px] text-slate-400">{{ $item['pid'] }}</span></div>
                                <p class="truncate text-xs font-bold text-slate-900 dark:text-white" title="{{ $item['lop_name'] }}">{{ $item['lop_name'] }}</p>
                                <p class="mt-1 truncate text-[10px] text-slate-400">IHLD {{ $item['id_ihld'] }} · {{ $item['program'] }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3.5"><p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $item['branch'] }}</p><p class="mt-1 text-[10px] text-slate-400">STO {{ $item['sto'] }}</p></td>
                        <td class="px-4 py-3.5"><p class="max-w-[150px] truncate text-xs font-semibold text-slate-700 dark:text-slate-200" title="{{ $item['admin_name'] }}">{{ $item['admin_name'] }}</p></td>
                        <td class="px-4 py-3.5"><p class="max-w-[170px] truncate text-xs font-medium {{ $item['is_unassigned'] ? 'text-slate-950 underline decoration-slate-400 underline-offset-4 dark:text-white' : 'text-slate-700 dark:text-slate-200' }}" title="{{ $item['assignee_name'] }}">{{ $item['assignee_name'] }}</p><p class="mt-1 max-w-[170px] truncate text-[9px] text-slate-400" title="{{ $item['mitra'] }}">Mitra: {{ $item['mitra'] }}</p></td>
                        <td class="px-4 py-3.5"><span class="inline-flex rounded-lg border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $item['stage_label'] }}</span></td>
                        <td class="px-4 py-3.5 text-center"><p class="text-xs font-bold {{ $item['is_bottleneck'] ? 'text-slate-950 underline decoration-slate-400 underline-offset-4 dark:text-white' : 'text-slate-600 dark:text-slate-300' }}">{{ $item['stage_age_days'] }} hari</p><p class="mt-1 text-[9px] text-slate-400">sejak {{ $item['stage_entered_at']->format('d M Y') }}</p></td>
                        <td class="px-4 py-3.5 text-center"><p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ $item['idle_days'] }} hari</p></td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex min-w-8 justify-center rounded-lg border border-slate-300 bg-slate-50 px-2 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ $item['pending_count'] }}</span>
                            @if($item['pending_types'])
                                <p class="mx-auto mt-1 max-w-[150px] truncate text-[9px] text-slate-400" title="{{ implode(', ', $item['pending_types']) }}">{{ collect($item['pending_types'])->map(fn ($type) => str_replace('_', ' ', $type))->implode(', ') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3.5"><p class="text-[10px] font-medium text-slate-600 dark:text-slate-300">{{ $item['last_movement_at']->format('d M Y H:i') }}</p></td>
                        <td class="px-4 py-3.5 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ $item['detail_url'] }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-[10px] font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Timeline</a>
                                @if($item['pending_count'] > 0)
                                    <a href="{{ route('approval-center.index', ['tab' => 'approval', 'scope' => 'all', 'search' => $item['lop_name']]) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-[10px] font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">Detail Approval</a>
                                @endif
                                @if($canReview && $item['pending_count'] > 0 && $item['review_url'])
                                    <a href="{{ $item['review_url'] }}" class="inline-flex h-9 items-center rounded-lg bg-slate-900 px-3 text-[10px] font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">Review</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-6 py-14 text-center"><h3 class="text-sm font-bold text-slate-900 dark:text-white">Tidak ada LOP yang cocok</h3><p class="mt-1 text-xs text-slate-400">Ubah filter atau batas bottleneck untuk melihat data lainnya.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($operationalItems->hasPages())
        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $operationalItems->links() }}</div>
    @endif
</section>
