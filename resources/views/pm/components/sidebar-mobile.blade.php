<div class="flex flex-col w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 min-h-screen shadow-xl">

    <div class="h-16 px-5 flex items-center border-b border-gray-200 dark:border-gray-800 justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-200 dark:shadow-none">
                D
            </div>
            <div>
                <h1 class="text-base font-bold text-gray-900 dark:text-white">
                    Dompis Cons
                </h1>
                <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                    Project Manager
                </p>
            </div>
        </div>
        <button @click="sidebarOpen = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 lg:hidden focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">

        <p class="px-3 mb-2 text-xs font-bold uppercase text-gray-400 tracking-wider">
            Main Dashboard
        </p>

        {{-- 1. DASHBOARD PM --}}
        <a href="{{ route('pm.dashboard') }}"
           @click="sidebarOpen = false"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('pm.dashboard') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('pm.dashboard'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-indigo-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="7" height="9" x="3" y="3" rx="1"/>
                    <rect width="7" height="5" x="14" y="3" rx="1"/>
                    <rect width="7" height="9" x="14" y="12" rx="1"/>
                    <rect width="7" height="5" x="3" y="16" rx="1"/>
                </svg>
            </div>
            <span>Dashboard PM</span>
        </a>

        <p class="px-3 pt-5 mb-2 text-xs font-bold uppercase text-gray-400 tracking-wider">
            Operational & Control
        </p>

        {{-- KONTAINER UTAMA MENU DENGAN STATE ALPINE.JS --}}
        {{-- x-data "{ open: true }" membuat dropdown otomatis terbuka jika PM sedang berada di halaman rekap --}}
        <div x-data="{ open: {{ request()->routeIs('pm.rekap_progress') ? 'true' : 'false' }} }" class="space-y-1">
            
            {{-- TOMBOL UTAMA REKAP PROGRESS (Pemicu Dropdown) --}}
            <button type="button" 
                    @click="open = !open"
                    class="w-full relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('pm.rekap_progress') ? 'bg-gray-50/50 dark:bg-gray-800/40' : '' }}">
                
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center text-blue-600 dark:text-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-no-axes-combined">
                            <path d="M12 16v5"/><path d="M16 14.639V21"/><path d="M20 10.656V21"/><path d="m22 3-8.646 8.646a.5.5 0 0 1-.708 0L9.354 8.354a.5.5 0 0 0-.707 0L2 15"/><path d="M4 18.463V21"/><path d="M8 14.656V21"/>
                        </svg>
                    </div>    
                    <span>Rekap Progress</span>
                </div>
                
                {{-- Ikon Indikator Panah (Berputar otomatis saat dropdown terbuka/tutup) --}}
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" 
                    class="text-gray-400 transition-transform duration-200"
                    :class="open ? 'rotate-180' : ''">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            {{-- GRUP SUB-MENU PROGRAM (AKORDEON) --}}
            <div x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="mt-1 ml-5 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-1">

                {{-- Sub-Menu: OSP --}}
                <a href="{{ route('pm.rekap_progress', ['program' => 'OSP']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition
                {{ (request('program', 'OSP') == 'OSP' && request()->routeIs('pm.rekap_progress')) ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        OSP
                    </span>
                    <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-blue-100/80 text-blue-700 font-bold dark:bg-blue-900/40 dark:text-blue-300">Fiber</span>
                </a>

                {{-- Sub-Menu: NODE B --}}
                <a href="{{ route('pm.rekap_progress', ['program' => 'NODE B']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition
                {{ (request('program') == 'NODE B' && request()->routeIs('pm.rekap_progress')) ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        NODE B
                    </span>
                    <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-purple-100/80 text-purple-700 font-bold dark:bg-purple-900/40 dark:text-purple-300">Site</span>
                </a>

                {{-- Sub-Menu: HEM --}}
                <a href="{{ route('pm.rekap_progress', ['program' => 'HEM']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition
                {{ (request('program') == 'HEM' && request()->routeIs('pm.rekap_progress')) ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        HEM
                    </span>
                    <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-amber-100/80 text-amber-700 font-bold dark:bg-amber-900/40 dark:text-amber-300">Home</span>
                </a>

                {{-- Sub-Menu: OLO --}}
                <a href="{{ route('pm.rekap_progress', ['program' => 'OLO']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition
                {{ (request('program') == 'OLO' && request()->routeIs('pm.rekap_progress')) ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        OLO
                    </span>
                    <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-green-100/80 text-green-700 font-bold dark:bg-green-900/40 dark:text-green-300">Partner</span>
                </a>

                {{-- Sub-Menu: Konstruksi Eksternal --}}
                <a href="{{ route('pm.rekap_progress', ['program' => 'Konstruksi Eksternal']) }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition
                {{ (request('program') == 'Konstruksi Eksternal' && request()->routeIs('pm.rekap_progress')) ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                        Eksternal
                    </span>
                    <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-red-100/80 text-red-700 font-bold dark:bg-red-900/40 dark:text-red-300">External</span>
                </a>

            </div>
        </div>

        {{-- 2. MAP MONITORING --}}
        <a href="{{ route('pm.map.monitoring') }}"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('pm.map.monitoring') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('pm.map.monitoring'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-indigo-600"></span>
            @endif  

            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pinned">
                    <path d="M18 8c0 3.613-3.869 7.429-5.393 8.795a1 1 0 0 1-1.214 0C9.87 15.429 6 11.613 6 8a6 6 0 0 1 12 0"/><circle cx="12" cy="8" r="2"/><path d="M8.714 14h-3.71a1 1 0 0 0-.948.683l-2.004 6A1 1 0 0 0 3 22h18a1 1 0 0 0 .948-1.316l-2-6a1 1 0 0 0-.949-.684h-3.712"/>
                </svg> 
            </div>
            <span>Map Monitoring</span>
        </a>

        {{-- 3. KINERJA WASPANG --}}
        <a href="{{ route('pm.waspang.performance') }}"
        class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
        {{ request()->routeIs('pm.waspang.performance') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('pm.waspang.performance'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif
            
            <div class="w-8 h-8 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-600 dark:text-green-400">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-round">
                    <path d="M14 19a6 6 0 0 0-12 0"/><circle cx="8" cy="9" r="4"/><path d="M22 19a6 6 0 0 0-6-6 4 4 0 0 0 0-8"/>
                </svg>
            </div>    

            <span>Kinerja Waspang</span>
        </a>

        <!-- {{-- 4. ASSIGN WASPANG --}}
        <a href="{{ route('pm.assign.waspang') }}"
           @click="sidebarOpen = false"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('pm.assign.waspang') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('pm.assign.waspang'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-indigo-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/><path d="M8 15H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/>
                </svg>    
            </div>
            <span>Assign Waspang</span>
        </a> -->

        <p class="px-3 pt-5 mb-2 text-xs font-bold uppercase text-gray-400 tracking-wider">
            Management Report
        </p>

        

    </nav>
</div>