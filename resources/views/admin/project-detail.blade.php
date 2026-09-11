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
    @php
        // Section AF: label & warna "Status Progress" disamakan dgn
        // index/project-card/tracking (effectiveStageLabel + project_stages.color
        // via stageColorClasses()) -- sebelumnya baca $lop->stage?->name langsung
        // (data mentah project_stages.name, TIDAK hold/drop-safe, TIDAK ada warna
        // sama sekali) tanpa lewat progressSummary()'s "effective" keys.
        $detailSummary = $project->progressSummary();
        $detailStageLabel = $detailSummary['effectiveStageLabel'] ?? $detailSummary['stageLabel'] ?? '-';
        $detailStageColors = \App\Models\Project::stageColorClasses(($detailSummary['isHold'] ?? false) ? 'orange' : (($detailSummary['isDrop'] ?? false) ? 'red' : ($detailSummary['effectiveStageColor'] ?? null)));
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <div class="bg-gray-50 rounded-2xl p-6">
            <p class="text-gray-500">Status Progress</p>
            <h2 class="text-2xl font-bold mt-2">
                <span class="inline-block px-3 py-1 rounded-full text-base {{ $detailStageColors['badge'] }}">
                    {{ $detailStageLabel }}
                </span>
            </h2>
        </div>

        <div class="bg-gray-50 rounded-2xl p-6">
            <p class="text-gray-500">Jenis Eksekusi</p>
            <h2 class="text-2xl font-bold mt-2">
                {{ strtoupper($project->execution_type ?? $project->jenis_eksekusi ?? '-') }}
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

    {{-- Riwayat BOQ Survey (Re Survey) --}}
    @if($project->lop && $project->lop->surveyRounds->isNotEmpty())
        <div class="mt-10">

            <h2 class="text-3xl font-bold mb-2">
                Riwayat BOQ Survey
            </h2>

            <p class="text-gray-500 mb-6">
                Referensi BOQ Plan di atas, dibandingkan dengan tiap ronde BOQ Survey (round 1 = Survey awal, round berikutnya = hasil Re Survey). Ronde lama tidak dihapus saat Re Survey dilakukan.
            </p>

            <div class="space-y-4">

                @foreach($project->lop->surveyRounds as $round)

                    <div class="border border-gray-200 rounded-2xl p-6">

                        <div class="flex flex-wrap items-center justify-between gap-3">

                            <div>
                                <h3 class="text-xl font-bold">
                                    Round {{ $round->round_number }}{{ $round->round_number === 1 ? ' — Survey Awal' : ' — Re Survey' }}
                                </h3>
                                <p class="text-gray-500 text-sm mt-1">
                                    @if($round->status === 'completed')
                                        Selesai · {{ optional($round->finished_at)->format('d M Y H:i') }}
                                        @if($round->finisher)
                                            · oleh {{ $round->finisher->name ?? $round->finisher->username ?? '-' }}
                                        @endif
                                    @else
                                        Sedang berjalan
                                    @endif
                                </p>
                            </div>

                            @if($round->deviation_percent !== null)
                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $round->redesign_required ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    Deviasi {{ rtrim(rtrim(number_format($round->deviation_percent, 2, '.', ''), '0'), '.') }}%
                                    {{ $round->redesign_required ? '(Approval Redesign)' : '' }}
                                </span>
                            @endif

                        </div>

                        @if($round->items->isNotEmpty())
                            <div class="mt-4 overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-400 border-b border-gray-100">
                                            <th class="py-2 pr-4 font-semibold">Designator</th>
                                            <th class="py-2 pr-4 font-semibold">Item</th>
                                            <th class="py-2 pr-4 font-semibold text-right">Plan</th>
                                            <th class="py-2 pr-4 font-semibold text-right">Survey (Round {{ $round->round_number }})</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($round->items as $item)
                                            <tr class="border-b border-gray-50 last:border-0">
                                                <td class="py-2 pr-4 font-bold">{{ $item->designator }}</td>
                                                <td class="py-2 pr-4 text-gray-500">{{ $item->item_name }}</td>
                                                <td class="py-2 pr-4 text-right">{{ $item->quantity_plan ?? '—' }} {{ $item->unit }}</td>
                                                <td class="py-2 pr-4 text-right font-bold">{{ $item->quantity_survey ?? '—' }} {{ $item->unit }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mt-3 text-sm text-gray-400">Ronde ini belum memiliki snapshot item (masih berjalan).</p>
                        @endif

                    </div>

                @endforeach

            </div>

        </div>
    @endif

</div>

@endsection
