@extends('layouts.admin')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="bg-white rounded-3xl border border-gray-200 p-6">

        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-black text-gray-900">
                    Inbox
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Project yang pernah Anda assign ke Waspang.
                </p>
            </div>

            <div class="text-right">
                <p class="text-xs uppercase tracking-wider text-gray-400">
                    Total Project
                </p>

                <p class="text-3xl font-black text-blue-700">
                    {{ $assignments->count() }}
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
                           placeholder="Cari PID, Nama LOP, Waspang..."
                           class="w-full pl-12 pr-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

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

        {{-- LIST --}}
        <div>

            @forelse($assignments as $item)

                @php
                    $project = $item->project;
                    $lop = $project?->lop;
                    $waspang = $project?->assignments?->first()?->waspang;
                @endphp

                <div class="group border-b border-gray-100 hover:bg-blue-50/50 transition">

                    <div class="px-6 py-4 flex items-center justify-between">

                        <div class="flex items-start gap-4">

                            <div class="mt-2">
                                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                            </div>

                            <div>

                                <div class="flex items-center gap-3">

                                    <span class="font-black text-gray-900">
                                        {{ $project->pid_sap ?? '-' }}
                                    </span>

                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                        {{ $lop->program_sap ?? '-' }}
                                    </span>

                                </div>

                                <p class="mt-1 font-semibold text-gray-800">
                                    {{ $lop->lop_name ?? $project->project_name }}
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
                                        Waspang :
                                        <b>{{ $waspang->name ?? '-' }}</b>
                                    </span>

                                </div>

                            </div>

                        </div>

                        <div class="text-right">

                            <p class="text-xs text-gray-400">
                                {{ optional($item->created_at)->diffForHumans() }}
                            </p>

                            <a href="{{ route('admin.projects.tracking',$project->id_project) }}"
                               class="inline-flex items-center mt-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700">

                                Open

                            </a>

                        </div>

                    </div>

                </div>

            @empty

                <div class="py-20 text-center">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-16 h-16 mx-auto text-gray-300"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.5"
                              d="M3 8l7.89 4.26a2 2 0 0 0 2.22 0L21 8"/>

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.5"
                              d="M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                    </svg>

                    <h3 class="mt-4 text-lg font-bold text-gray-700">
                        Inbox Kosong
                    </h3>

                    <p class="text-sm text-gray-500">
                        Belum ada project yang Anda assign.
                    </p>

                </div>

            @endforelse

        </div>

        

    </div>

</div>

@endsection