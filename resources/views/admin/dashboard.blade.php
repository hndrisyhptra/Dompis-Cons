@extends('layouts.admin')

@section('content')

    {{-- Statistik --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-white rounded-2xl border border-gray-200 p-4">

            <p class="text-sm text-gray-500">
                Total LOP
            </p>

            <h2 class="text-2xl font-bold mt-2">
                {{ $totalProject }}
            </h2>

        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-4">

            <p class="text-sm text-gray-500">
                Project Active
            </p>

            <h2 class="text-2xl font-bold text-blue-700 mt-2">
                {{ $activeProject }}
            </h2>

        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-4">

            <p class="text-sm text-gray-500">
                Waiting UT
            </p>

            <h2 class="text-2xl font-bold text-yellow-600 mt-2">
                {{ $waitingUt }}
            </h2>

        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-4">

            <p class="text-sm text-gray-500">
                Completed
            </p>

            <h2 class="text-2xl font-bold text-green-700 mt-2">
                {{ $completedProject }}
            </h2>

        </div>

    </div>

@endsection