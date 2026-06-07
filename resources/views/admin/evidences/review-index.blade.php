@extends('layouts.admin')

@section('content')

<div class="max-w-5xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold">
            Approval Eviden
        </h1>

        <p class="text-sm text-gray-500">
            Pilih proyek untuk mulai review step by step
        </p>
    </div>

    <div class="space-y-4">

        @foreach($projects as $project)

            @php
                $waspang = optional($project->assignment)->user;
            @endphp

            <div class="bg-white rounded-3xl border border-gray-200 overflow-hidden">

                <div class="h-1 bg-blue-600"></div>

                <div class="p-6">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <h2 class="text-2xl font-bold">
                                {{ $project->project_name }}
                            </h2>

                            <p class="text-lg text-gray-600 mt-1">
                                {{ $project->lop?->sto }}
                                ·
                                {{ $project->lop?->branch }}
                                ·
                                Waspang:
                                <span class="font-bold text-gray-900">
                                    {{ $waspang->name ?? '-' }}
                                </span>
                            </p>

                        </div>

                        <div class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-800 font-bold">
                            4 step pending
                        </div>

                    </div>

                    <div class="flex flex-wrap gap-3 mt-5">

                        <span class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-800 font-bold">
                            ⏱ Persiapan
                        </span>

                        <span class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-800 font-bold">
                            ⏱ Instalasi
                        </span>

                        <span class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-800 font-bold">
                            ⏱ Pengukuran
                        </span>

                        <span class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-800 font-bold">
                            ⏱ Finishing
                        </span>

                    </div>

                    <div class="h-2 rounded-full bg-gray-100 mt-6"></div>

                    <div class="flex items-center justify-between mt-6">

                        <p class="text-gray-500 text-xl">
                            0/4 step disetujui
                        </p>

                        <a href="{{ route('admin.evidences.review.project', $project->id_project) }}"
                           class="h-14 px-8 rounded-2xl border border-gray-300 text-2xl font-bold inline-flex items-center gap-3">
                            →
                            Mulai Review
                        </a>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

</div>

@endsection