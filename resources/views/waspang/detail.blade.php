@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#f8f7f3] pb-24">

    <div class="bg-blue-700 text-white px-6 pt-10 pb-8">
        <a href="{{ route('waspang.inbox') }}" class="text-white text-xl">‹</a>
        <h1 class="text-2xl font-bold mt-2">{{ $project->project_name }}</h1>
        <p>{{ $project->sto }} · {{ $project->mitra_name }}</p>
    </div>

    <div class="px-5 mt-5 space-y-4">
        @foreach($project->boqItems as $boq)
            @php
                $isDone = $boq->quantity_actual >= $boq->quantity_plan && $boq->quantity_plan > 0;
                $percent = $boq->quantity_plan > 0 ? min(100, round(($boq->quantity_actual / $boq->quantity_plan) * 100)) : 0;
            @endphp

            <div class="bg-white rounded-2xl border shadow-sm p-5">
                <div class="flex justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-lg">{{ $boq->designator }}</h3>
                        <p class="text-gray-600">{{ $boq->item_name }}</p>
                    </div>

                    <span class="h-fit px-3 py-1 rounded-full text-sm font-semibold
                        {{ $isDone ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $isDone ? 'Selesai' : 'Progress' }}
                    </span>
                </div>

                <div class="mt-4 text-sm text-gray-600">
                    Plan: {{ $boq->quantity_plan }} {{ $boq->unit }} |
                    Actual: {{ $boq->quantity_actual ?? 0 }} {{ $boq->unit }}
                </div>

                <div class="w-full bg-gray-100 rounded-full h-2 mt-3">
                    <div class="bg-blue-700 h-2 rounded-full" style="width: {{ $percent }}%"></div>
                </div>

                <form action="{{ route('waspang.boq.updateActual', $boq->id_boq) }}" method="POST" class="mt-4">
                    @csrf
                    <label class="text-sm font-semibold">Input Quantity Actual</label>
                    <div class="flex gap-2 mt-2">
                        <input type="number" step="0.01" name="quantity_actual"
                               value="{{ $boq->quantity_actual }}"
                               class="w-full border rounded-xl px-4 py-3"
                               placeholder="Qty actual">

                        <button class="bg-blue-700 text-white rounded-xl px-4 font-semibold">
                            Simpan
                        </button>
                    </div>
                </form>

                <form action="{{ route('waspang.boq.uploadEvidence', $boq->id_boq) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="mt-4 space-y-3">
                    @csrf

                    <input type="file" name="photo" accept="image/*" capture="environment"
                           class="w-full border rounded-xl px-4 py-3 bg-white">

                    <textarea name="caption"
                              class="w-full border rounded-xl px-4 py-3"
                              placeholder="Catatan eviden"></textarea>

                    <input type="hidden" name="latitude" class="latitude">
                    <input type="hidden" name="longitude" class="longitude">

                    <button class="w-full bg-blue-700 text-white rounded-xl py-3 font-semibold">
                        Upload Eviden Foto
                    </button>
                </form>

                @if($boq->evidences->count())
                    <div class="grid grid-cols-3 gap-2 mt-4">
                        @foreach($boq->evidences as $evidence)
                            <img src="{{ asset('storage/' . $evidence->file_path) }}"
                                 class="h-24 w-full object-cover rounded-xl border">
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @include('waspang.partials.bottom-nav')
</div>

<script>
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.querySelectorAll('.latitude').forEach(el => {
                el.value = position.coords.latitude;
            });

            document.querySelectorAll('.longitude').forEach(el => {
                el.value = position.coords.longitude;
            });
        });
    }
</script>
@endsection