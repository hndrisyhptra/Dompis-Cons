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

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama Waspang</th>
                        <th class="px-4 py-3 text-left">Jumlah Project</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Project Aktif</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($waspangs as $waspang)

                        @php
                            $assignments = $waspang->assignments->unique('project_id');
                            $projectCount = $assignments->count();
                            $isOverload = $projectCount >= 3;
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

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                Belum ada data waspang.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

    </div>

</div>

@endsection