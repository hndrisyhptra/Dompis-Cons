@extends('layouts.admin')

@section('content')

    {{-- Statistik --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
        <p class="text-sm text-gray-500">Total LOP</p>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
            {{ $totalLop }}
        </h2>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
        <p class="text-sm text-gray-500">Sudah Assign</p>
        <h2 class="text-2xl font-bold text-blue-700 mt-2">
            {{ $assignedLop }}
        </h2>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
        <p class="text-sm text-gray-500">Menunggu Review</p>
        <h2 class="text-2xl font-bold text-yellow-600 mt-2">
            {{ $waitingApproval }}
        </h2>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">
        <p class="text-sm text-gray-500">Complete Approval</p>
        <h2 class="text-2xl font-bold text-green-700 mt-2">
            {{ $completedApproval }}
        </h2>
    </div>

</div>

   @php
    $sections = [
        'Statistik Batch' => $statsByBatch,
        'Statistik Branch' => $statsByBranch,
        'Statistik Program' => $statsByProgram,
    ];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    @foreach($sections as $title => $items)

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $title }}
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-2 text-left">Nama</th>
                            <th class="px-3 py-2 text-center">Total</th>
                            <th class="px-3 py-2 text-center">Assign</th>
                            <th class="px-3 py-2 text-center">Review</th>
                            <th class="px-3 py-2 text-center">Complete</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($items as $item)
                            <tr>
                                <td class="px-3 py-2 font-bold">
                                    {{ $item['label'] }}
                                </td>
                                <td class="px-3 py-2 text-center">{{ $item['total'] }}</td>
                                <td class="px-3 py-2 text-center text-blue-700 font-bold">{{ $item['assigned'] }}</td>
                                <td class="px-3 py-2 text-center text-yellow-700 font-bold">{{ $item['waiting'] }}</td>
                                <td class="px-3 py-2 text-center text-green-700 font-bold">{{ $item['completed'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                                    Belum ada data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    @endforeach

</div>

   
@endsection

