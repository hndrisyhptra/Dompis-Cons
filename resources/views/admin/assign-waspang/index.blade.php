@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            Assign Waspang
        </h1>
        <p class="text-sm text-gray-500">
            Monitoring waspang dan project yang sedang di handle
        </p>
    </div>

    <form method="GET" action="{{ route('assign-waspang.index') }}"
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <div class="flex flex-col sm:flex-row gap-3">

            <input type="text"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Cari Nama atau NIK waspang..."
                class="flex-1 h-11 rounded-xl border-gray-300 text-sm">

            <div class="flex gap-2">
                <button class="h-11 px-5 rounded-xl bg-gray-900 text-white text-sm font-bold">
                    Cari
                </button>

                @if (!empty($search))
                    <a href="{{ route('assign-waspang.index') }}"
                    class="h-11 px-5 rounded-xl border border-gray-300 text-sm font-bold flex items-center">
                        Reset
                    </a>
                @endif
            </div>

        </div>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama Waspang</th>
                        <th class="px-4 py-3 text-left">Jumlah Project</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Project Aktif</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($waspangs as $waspang)

                        @php
                            $assignments = $waspang->active_assignments ?? collect();
                            $projectCount = $waspang->active_project_count ?? 0;
                            $isOverload = $projectCount > 3;
                            $isOnProgress = $projectCount > 0 && $projectCount <= 3;
                        @endphp

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">

                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                {{ $waspang->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $projectCount }} project
                            </td>

                            <td class="px-4 py-3">
                                @if($isOverload)
                                    <span class="px-2.5 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-bold">
                                        Overload
                                    </span>
                                @elseif($isOnProgress)
                                    <span class="px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-xs font-bold">
                                        On Progress
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-bold">
                                        Idle
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @if($assignments->count() > 0)
                                    <div class="space-y-1">
                                        @foreach($assignments as $assignment)
                                            <div class="text-xs text-gray-700 dark:text-gray-300">
                                                • {{ $assignment->project->project_name ?? '-' }}
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">
                                        Belum ada project
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.assign-waspang.history', $waspang->id_user) }}"
                                class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-blue-600 text-white text-xs font-bold hover:bg-blue-700">
                                   View History
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                Belum ada data waspang.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        @if ($waspangs->hasPages())
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-800">

            <div class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                <span class="font-semibold">{{ $waspangs->firstItem() }}</span>
                -
                <span class="font-semibold">{{ $waspangs->lastItem() }}</span>
                dari
                <span class="font-semibold">{{ $waspangs->total() }}</span>
                data
            </div>

            <div class="flex items-center gap-1">

                {{-- Previous --}}
                @if ($waspangs->onFirstPage())
                    <span class="px-3 py-2 rounded-lg border text-gray-400 cursor-not-allowed">
                        ←
                    </span>
                @else
                    <a href="{{ $waspangs->previousPageUrl() }}"
                    class="px-3 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                        ←
                    </a>
                @endif

                {{-- Page Numbers --}}
                @foreach ($waspangs->getUrlRange(
                    max(1, $waspangs->currentPage() - 1),
                    min($waspangs->lastPage(), $waspangs->currentPage() + 1)
                ) as $page => $url)

                    @if ($page == $waspangs->currentPage())
                        <span class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                        class="px-4 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                            {{ $page }}
                        </a>
                    @endif

                @endforeach

                {{-- Next --}}
                @if ($waspangs->hasMorePages())
                    <a href="{{ $waspangs->nextPageUrl() }}"
                    class="px-3 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                        →
                    </a>
                @else
                    <span class="px-3 py-2 rounded-lg border text-gray-400 cursor-not-allowed">
                        →
                    </span>
                @endif

            </div>
        </div>
    @endif

    </div>

</div>

@endsection