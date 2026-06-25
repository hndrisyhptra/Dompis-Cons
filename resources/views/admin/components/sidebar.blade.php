<aside class="hidden lg:flex lg:flex-col w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 min-h-screen">

    <div class="h-16 px-5 flex items-center border-b border-gray-200 dark:border-gray-800">

        <div class="flex items-center gap-3">

            <div class="w-10 h-10 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold">
                D
            </div>

            <div>
                <h1 class="text-base font-bold text-gray-900 dark:text-white">
                    Dompis Cons
                </h1>
                <p class="text-xs text-gray-500">
                    Admin Project
                </p>
            </div>

        </div>

    </div>

    <nav class="flex-1 p-4 space-y-1">

        <p class="px-3 mb-2 text-xs font-bold uppercase text-gray-400">
            Main Menu
        </p>

        <a href="{{ route('dashboard') }}"
        class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
        {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('dashboard'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
            <!-- Lucide: Layout Dashboard -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard">
                    <rect width="7" height="9" x="3" y="3" rx="1"/>
                    <rect width="7" height="5" x="14" y="3" rx="1"/>
                    <rect width="7" height="9" x="14" y="12" rx="1"/>
                    <rect width="7" height="5" x="3" y="16" rx="1"/>
                </svg>
            </div>

            <span>Dashboard</span>
        </a>

        @php
            $inboxOpen = request()->routeIs('admin.inbox*')
                || request()->routeIs('admin.history*');
        @endphp

        {{-- INBOX --}}
        <div x-data="{ open: {{ $inboxOpen ? 'true' : 'false' }} }">

            <button type="button"
                    @click="open = !open"
                    class="w-full relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
                    {{ $inboxOpen ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                <div class="flex items-center gap-3">
                    @if($inboxOpen)
                        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                    @endif

                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/40 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M22 12h-6l-2 3h-4l-2-3H2"/>
                            <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                        </svg>
                    </div>

                    <span>Inbox</span>
                </div>

                <svg :class="open ? 'rotate-180' : ''"
                    class="w-4 h-4 transition-transform"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open"
                x-transition
                class="mt-1 ml-5 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-1">

                <a href="{{ route('admin.inbox') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.inbox*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Active Project
                    </span>
                </a>

                <a href="{{ route('admin.history') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.history*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        History
                    </span>
                </a>

            </div>
        </div>

        @php
            $projectOpen = request()->routeIs('projects.*');
            $activeProgram = request('program');
        @endphp

        {{-- PROJECT ID --}}
        <div x-data="{ open: {{ $projectOpen ? 'true' : 'false' }} }">

            <button type="button"
                    @click="open = !open"
                    class="w-full relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
                    {{ $projectOpen ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                <div class="flex items-center gap-3">
                    @if($projectOpen)
                        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                    @endif

                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M4 19.5V4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Z"/>
                            <path d="M8 7h8"/>
                            <path d="M8 11h8"/>
                            <path d="M8 15h5"/>
                        </svg>
                    </div>

                    <span>Project ID</span>
                </div>

                <svg :class="open ? 'rotate-180' : ''"
                    class="w-4 h-4 transition-transform"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open"
                x-transition
                class="mt-2 ml-5 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-1">

                <a href="{{ route('projects.index', ['program' => 'OSP']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ $activeProgram == 'OSP' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        OSP
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Fiber</span>
                </a>

                <a href="{{ route('projects.index', ['program' => 'NODE B']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ $activeProgram == 'NODE B' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        NODE B
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Site</span>
                </a>

                <a href="{{ route('projects.index', ['program' => 'HEM']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ $activeProgram == 'HEM' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        HEM
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">HEM</span>
                </a>

                <a href="{{ route('projects.index', ['program' => 'OLO']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ $activeProgram == 'OLO' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        OLO
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-green-100 text-green-700">Partner</span>
                </a>

                <a href="{{ route('projects.index', ['program' => 'Konstruksi Eksternal']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ $activeProgram == 'Konstruksi Eksternal' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Eksternal
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-100 text-red-700">Exbis</span>
                </a>

            </div>
        </div>

        @php
            $masterDesignatorOpen = request()->routeIs('designators.*')
                || request()->routeIs('packages.*')
                || request()->routeIs('designator-prices.*');

            $importDataOpen = request()->routeIs('admin.import.pid*')
                || request()->routeIs('admin.import.lop*')
                || request()->routeIs('admin.import.lop.mapping*')
                || request()->routeIs('admin.import.boq*');
        @endphp

        {{-- MASTER DESIGNATOR --}}
        <div x-data="{ open: {{ $masterDesignatorOpen ? 'true' : 'false' }} }">

            <button type="button"
                    @click="open = !open"
                    class="w-full relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
                    {{ $masterDesignatorOpen ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                <div class="flex items-center gap-3">
                    @if($masterDesignatorOpen)
                        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                    @endif

                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="22" height="22" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="9" ry="3"/>
                        <path d="M3 5V19A9 3 0 0 0 15 21.84"/>
                        <path d="M21 5V8"/>
                        <path d="M21 12L18 17H22L19 22"/>
                        <path d="M3 12A9 3 0 0 0 14.59 14.87"/>
                    </svg>
                </div>

                    <span>Master Designator</span>
                </div>

                <svg :class="open ? 'rotate-180' : ''"
                    class="w-4 h-4 transition-transform"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open"
                x-transition
                class="mt-1 ml-5 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-1">

                <a href="{{ route('designators.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('designators.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                   <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Designator
                    </span>
                </a>

                <a href="{{ route('packages.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('packages.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        Paket KHS
                    </span>
                </a>

                <a href="{{ route('designator-prices.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('designator-prices.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                   <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                        KHS
                    </span>
                </a>

            </div>
        </div>


        {{-- IMPORT DATA --}}
        <div x-data="{ open: {{ $importDataOpen ? 'true' : 'false' }} }">

            <button type="button"
                    @click="open = !open"
                    class="w-full relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
                    {{ $importDataOpen ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                <div class="flex items-center gap-3">
                    @if($importDataOpen)
                        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                    @endif
                    
                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3v12"/>
                            <path d="m7 10 5 5 5-5"/>
                            <path d="M5 21h14"/>
                        </svg>
                    </div>

                    <span>Bulk Import Data</span>
                </div>

                <svg :class="open ? 'rotate-180' : ''"
                    class="w-4 h-4 transition-transform"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open"
                x-transition
                class="mt-1 ml-5 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-1">

                <a href="{{ route('admin.import.pid') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.import.pid*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                   <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                       Bulk Import PID
                    </span>
                </a>

                <!-- <a href="{{ route('admin.import.lop') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.import.lop') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    Import LOP
                </a>

                <a href="{{ route('admin.import.lop.mapping') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.import.lop.mapping*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    Mapping PID - LOP
                </a> -->

                <a href="{{ route('admin.import.boq') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.import.boq*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                  
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                       Bulk Import BOQ
                    </span>
                </a>
            </div>
        </div>

        @php
            $masterDataOpen = request()->routeIs('admin.data-pid*')
                || request()->routeIs('admin.data-boq*');
        @endphp

        {{-- MASTER DATA PID & BOQ --}}
        <div x-data="{ open: {{ $masterDataOpen ? 'true' : 'false' }} }">

            <button type="button"
                    @click="open = !open"
                    class="w-full relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
                    {{ $masterDataOpen ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                <div class="flex items-center gap-3">
                    @if($masterDataOpen)
                        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                    @endif

                    <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/40 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M20 5a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2.5a1.5 1.5 0 0 1 1.2.6l.6.8a1.5 1.5 0 0 0 1.2.6z"/>
                            <path d="M3 8.268a2 2 0 0 0-1 1.738V19a2 2 0 0 0 2 2h11a2 2 0 0 0 1.732-1"/>
                        </svg>
                    </div>

                    <span>Master Data</span>
                </div>

                <svg :class="open ? 'rotate-180' : ''"
                    class="w-4 h-4 transition-transform"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open"
                x-transition
                class="mt-1 ml-5 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-1">

                <a href="{{ route('admin.data-pid') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.data-pid*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Data PID
                    </span>
                </a>

                <a href="{{ route('admin.data-boq') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.master-boq*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        Data BOQ
                    </span>
                </a>

            </div>
        </div>

        <a href="{{ route('assign-waspang.index') }}"
        class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
        {{ request()->routeIs('assign-waspang.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('assign-waspang.*'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-star-icon lucide-user-star">
                <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/><path d="M8 15H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/>
                </svg>    
            </div>
            <span>

            <span>Assign Waspang</span>
        </a>

        <p class="px-3 pt-5 mb-2 text-xs font-bold uppercase text-gray-400">
            Monitoring
        </p>

        <a href="{{ route('admin.map.monitoring') }}"
            class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
            {{ request()->routeIs('admin.map.monitoring') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                @if(request()->routeIs('admin.map.*'))
                    <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                @endif  

            <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pinned-icon lucide-map-pinned">
                    <path d="M18 8c0 3.613-3.869 7.429-5.393 8.795a1 1 0 0 1-1.214 0C9.87 15.429 6 11.613 6 8a6 6 0 0 1 12 0"/><circle cx="12" cy="8" r="2"/><path d="M8.714 14h-3.71a1 1 0 0 0-.948.683l-2.004 6A1 1 0 0 0 3 22h18a1 1 0 0 0 .948-1.316l-2-6a1 1 0 0 0-.949-.684h-3.712"/>
                </svg> 
            </div>
            
            <span>
                Map Monitoring
            </span>
        </a>

        <a href="{{ route('admin.evidences.approval') }}"
            class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
            {{ request()->routeIs('admin.evidences.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('admin.evidences.*'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check-icon lucide-badge-check">
                        <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>
                </svg>
            </div>
                

            <span>
                Approval Eviden
            </span>
            
        </a>

        <!-- <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
           
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-handshake-icon lucide-handshake">
                <path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/>
            </svg>
           <span>
                Uji terima
            </span>
           
        </a> -->

        <p class="px-3 pt-5 mb-2 text-xs font-bold uppercase text-gray-400">
            Report
        </p>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
          
           <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-no-axes-combined-icon lucide-chart-no-axes-combined">
                    <path d="M12 16v5"/><path d="M16 14.639V21"/><path d="M20 10.656V21"/><path d="m22 3-8.646 8.646a.5.5 0 0 1-.708 0L9.354 8.354a.5.5 0 0 0-.707 0L2 15"/><path d="M4 18.463V21"/><path d="M8 14.656V21"/>
                </svg>
            </div>    

            <span>
                Rekap Progress
            </span>
            
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold 
           {{ request()->routeIs('admin.users.*') ? text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
           
           @if(request()->routeIs('admin.users.*'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif
           
            <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-user-icon lucide-shield-user">
                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M6.376 18.91a6 6 0 0 1 11.249.003"/><circle cx="12" cy="11" r="4"/>
                </svg>
            </div>

            <span>
                User Management
            </span>
            
        </a>

    </nav>

</aside>