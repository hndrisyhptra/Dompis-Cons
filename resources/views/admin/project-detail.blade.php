@extends('layouts.admin')

@section('content')

<div class="bg-white rounded-3xl border border-gray-200 p-8">

    <div class="mb-8">

        <h1 class="text-4xl font-bold">
            {{ $project->project_name }}
        </h1>

        <p class="text-gray-500 text-xl mt-2">

            {{ $project->sto }}
            ·
            {{ $project->branch }}
            ·
            {{ $project->mitra_name }}

        </p>

    </div>

    {{-- Info --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <div class="bg-gray-50 rounded-2xl p-6">
            <p class="text-gray-500">Status</p>
            <h2 class="text-2xl font-bold mt-2">
                {{ ucfirst($project->status) }}
            </h2>
        </div>

        <div class="bg-gray-50 rounded-2xl p-6">
            <p class="text-gray-500">Jenis Eksekusi</p>
            <h2 class="text-2xl font-bold mt-2">
                {{ strtoupper($project->jenis_eksekusi) }}
            </h2>
        </div>

        <div class="bg-gray-50 rounded-2xl p-6">
            <p class="text-gray-500">Total Eviden</p>
            <h2 class="text-2xl font-bold mt-2">
                {{ $project->evidences->count() }}
            </h2>
        </div>

    </div>

    {{-- BOQ --}}
    <div>

        <h2 class="text-3xl font-bold mb-6">
            BOQ Item
        </h2>

        <div class="space-y-4">

            @foreach($project->boqItems as $boq)

                <div class="border border-gray-200 rounded-2xl p-6">

                    <div class="flex justify-between">

                        <div>

                            <h3 class="text-2xl font-bold">
                                {{ $boq->item_name }}
                            </h3>

                            <p class="text-gray-500 mt-2">

                                Plan:
                                {{ $boq->quantity_plan }}
                                {{ $boq->unit }}

                            </p>

                        </div>

                        <div class="text-right">

                            <p class="text-gray-500">
                                Actual
                            </p>

                            <h3 class="text-3xl font-bold mt-2">

                                {{ $boq->quantity_actual }}
                                {{ $boq->unit }}

                            </h3>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </div>

</div>

@endsection