@extends('layouts.admin')

@section('content')

<div class="space-y-6">

{{-- HEADER --}}
<div class="bg-white rounded-3xl border border-gray-200 p-6">

    <div class="flex items-center justify-between">

        <div>
            <h1 class="text-2xl font-black text-gray-900">
                History Project
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Daftar project yang sudah selesai (100%) dan pernah Anda assign.
            </p>
        </div>

        <div class="text-right">
            <p class="text-xs uppercase tracking-wider text-gray-400">
                Completed Project
            </p>

            <p class="text-3xl font-black text-emerald-600">
                {{ $assignments->total() }}
            </p>
        </div>

    </div>

</div>

{{-- SEARCH --}}
<div class="bg-white rounded-3xl border border-gray-200 overflow-hidden">

    <div class="p-4 border-b border-gray-100">

        <form method="GET">

            <div class="relative">

                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari PID SAP, Nama LOP, STO, Branch..."
                       class="w-full pl-12 pr-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">

                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-5 h-5 absolute left-4 top-3.5 text-gray-400"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M21 21l-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14z"/>
                </svg>

            </div>

        </form>

    </div>

    {{-- LIST PROJECT --}}
    <div>

        @forelse($assignments as $assignment)

            @php
                $project = $assignment->project;
                $lop = $project?->lop;
                $waspang = $assignment->waspang;

                $summary = $project?->progressSummary();

                $progress = $summary['progress'] ?? 0;
            @endphp

            <div class="group border-b border-gray-100 hover:bg-emerald-50 transition">

                <div class="px-6 py-4 flex items-center justify-between">

                    <div class="flex items-start gap-4">

                        <div class="mt-2">

                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>

                        </div>

                        <div>

                            <div class="flex items-center gap-2 flex-wrap">

                                <span class="font-black text-gray-900">
                                    {{ $project->pid_sap ?? '-' }}
                                </span>

                                <span class="px-2 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                    COMPLETE
                                </span>

                                <span class="px-2 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                    {{ $progress }}%
                                </span>

                            </div>

                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                {{ $lop->lop_name ?? $project->project_name ?? '-' }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-500">

                                <span>
                                    STO :
                                    <b>{{ $lop->sto ?? '-' }}</b>
                                </span>

                                <span>
                                    Branch :
                                    <b>{{ $lop->branch ?? '-' }}</b>
                                </span>

                                <span>
                                    Program :
                                    <b>{{ $project->program ?? '-' }}</b>
                                </span>

                                <span>
                                    Waspang :
                                    <b>{{ $waspang->name ?? '-' }}</b>
                                </span>

                            </div>

                        </div>

                    </div>

                    <div class="text-right">

                        <p class="text-xs text-gray-400">
                            {{ optional($assignment->created_at)->diffForHumans() }}
                        </p>

                        <div class="mt-2 flex justify-end gap-2">

                            <a href="{{ route('admin.projects.tracking', $project->id_project) }}"
                               class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700">

                                Tracking

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        @empty

            <div class="py-24 text-center">

                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-16 h-16 mx-auto text-gray-300"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="1.5"
                          d="M9 12l2 2l4-4"/>

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="1.5"
                          d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9s4.03-9 9-9s9 4.03 9 9z"/>
                </svg>

                <h3 class="mt-4 text-lg font-bold text-gray-700">
                    Belum Ada Project Selesai
                </h3>

                <p class="text-sm text-gray-500">
                    Project yang selesai akan muncul pada halaman ini.
                </p>

            </div>

        @endforelse

    </div>

    {{-- PAGINATION --}}
    <div class="p-4 border-t border-gray-100">
        {{ $assignments->links() }}
    </div>

</div>
```

</div>

@endsection
