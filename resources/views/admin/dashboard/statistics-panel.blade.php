@props([
    'sections' => []
])

<div
    x-data="statisticsPanel()"
    class="grid grid-cols-1 xl:grid-cols-3 gap-6"
>

    @foreach($sections as $title => $items)

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

            {{-- ================= HEADER ================= --}}

            <div
                class="sticky top-0 z-20 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 p-5"
            >

                <div class="flex items-start justify-between">

                    <div>

                        <h2 class="text-base font-black text-slate-900 dark:text-white">
                            {{ $title }}
                        </h2>

                        <p class="text-xs text-slate-500 mt-1">
                            Monitoring progress project
                        </p>

                    </div>

                    <span
                        class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300"
                    >
                        {{ count($items) }}
                    </span>

                </div>

                {{-- SEARCH --}}

                <div class="mt-5">

                    <div class="relative">

                        <svg
                            class="absolute left-3 top-3 h-4 w-4 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-4.3-4.3m1.3-5.2a6.5 6.5 0 11-13 0a6.5 6.5 0 0113 0z"
                            />
                        </svg>

                        <input

                            x-model="search"

                            type="text"

                            placeholder="Cari..."

                            class="w-full rounded-xl border border-slate-200 dark:border-slate-700
                                   bg-white dark:bg-slate-800
                                   pl-10 pr-4 py-2.5
                                   text-sm
                                   focus:ring-2
                                   focus:ring-indigo-500
                                   focus:border-indigo-500"

                        >

                    </div>

                </div>

                {{-- SORT --}}

                <div class="grid grid-cols-2 gap-3 mt-3">

                    <select

                        x-model="sort"

                        class="rounded-xl border border-slate-200 dark:border-slate-700
                               bg-white dark:bg-slate-800
                               text-sm"

                    >

                        <option value="progress_desc">
                            Progress ↓
                        </option>

                        <option value="progress_asc">
                            Progress ↑
                        </option>

                        <option value="total_desc">
                            Total ↓
                        </option>

                        <option value="assign_desc">
                            Assign ↓
                        </option>

                        <option value="review_desc">
                            Review ↓
                        </option>

                        <option value="done_desc">
                            Done ↓
                        </option>

                    </select>

                    <button

                        @click="toggleCompact()"

                        class="rounded-xl border border-slate-200 dark:border-slate-700
                               bg-white dark:bg-slate-800
                               text-sm font-semibold
                               hover:bg-slate-50"

                    >

                        Compact Mode

                    </button>

                </div>

            </div>

            {{-- ================= BODY ================= --}}

            <div class="max-h-[620px] overflow-y-auto">

                @foreach($items as $item)

                    @php

                        $percent = $item['percent'] ?? 0;

                        if($percent >= 80){

                            $bar='bg-emerald-500';

                        }elseif($percent>=50){

                            $bar='bg-amber-500';

                        }else{

                            $bar='bg-red-500';

                        }

                    @endphp

                    <div

                        x-show="'{{ strtolower($item['label']) }}'
                                .includes(search.toLowerCase())"

                        class="border-b border-slate-100 dark:border-slate-800
                               hover:bg-slate-50/80
                               dark:hover:bg-slate-800/40
                               transition"

                    >

                        <div class="p-5">

                            {{-- ROW HEADER --}}

                            <div class="flex items-center justify-between">

                                <div>

                                    <h3
                                        class="font-bold text-slate-900 dark:text-white"
                                    >
                                        {{ $item['label'] }}
                                    </h3>

                                </div>

                                <div
                                    class="text-right"
                                >

                                    <div
                                        class="text-xl font-black
                                        {{ $percent<50?'text-red-600':($percent<80?'text-amber-600':'text-emerald-600') }}"
                                    >

                                        {{ $percent }}%

                                    </div>

                                    <div
                                        class="text-xs text-slate-500"
                                    >
                                        Completion
                                    </div>

                                </div>

                            </div>

                            {{-- PROGRESS BAR --}}

                            <div
                                class="mt-4"
                            >

                                <div
                                    class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden"
                                >

                                    <div

                                        class="h-full rounded-full transition-all duration-700 {{ $bar }}"

                                        style="width:{{ min($percent,100) }}%"

                                    ></div>

                                </div>

                            </div>

                            {{-- KPI --}}

                            <div
                                class="grid grid-cols-4 gap-3 mt-5"
                            >

                                <div class="text-center">

                                    <div
                                        class="text-lg font-black text-slate-900 dark:text-white"
                                    >
                                        {{ $item['total'] }}
                                    </div>

                                    <div
                                        class="text-[11px] uppercase tracking-wider text-slate-500"
                                    >
                                        Total
                                    </div>

                                </div>

                                <div class="text-center">

                                    <div
                                        class="text-lg font-black text-blue-600"
                                    >
                                        {{ $item['assigned'] }}
                                    </div>

                                    <div
                                        class="text-[11px] uppercase tracking-wider text-slate-500"
                                    >
                                        Assign
                                    </div>

                                </div>

                                <div class="text-center">

                                    <div
                                        class="text-lg font-black text-amber-600"
                                    >
                                        {{ $item['waiting'] }}
                                    </div>

                                    <div
                                        class="text-[11px] uppercase tracking-wider text-slate-500"
                                    >
                                        Review
                                    </div>

                                </div>

                                <div class="text-center">

                                    <div
                                        class="text-lg font-black text-emerald-600"
                                    >
                                        {{ $item['completed'] }}
                                    </div>

                                    <div
                                        class="text-[11px] uppercase tracking-wider text-slate-500"
                                    >
                                        Done
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>