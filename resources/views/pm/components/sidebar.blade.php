<aside class="hidden lg:flex lg:flex-col w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 min-h-screen">

    <!-- Header Aplikasi -->
    <div class="h-16 px-5 flex items-center border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-200 dark:shadow-none">
                P
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
    </div>

    <!-- Navigasi Menu PM (5 Menu Esensial) -->
    <nav class="flex-1 p-4 space-y-1">

        <p class="px-3 mb-2 text-xs font-bold uppercase text-gray-400 tracking-wider">
            Main Dashboard
        </p>

        {{-- 1. DASHBOARD PM --}}
        <a href="{{ route('pm.dashboard') }}"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('pm.dashboard') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('pm.dashboard'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-indigo-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard">
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
        <a href="{{ route('pm.kinerja.waspang') }}"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('pm.kinerja.waspang') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('pm.kinerja.waspang'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-indigo-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                <!-- Lucide: Trending Up / Kinerja -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                    <polyline points="16 7 22 7 22 13"/>
                </svg>
            </div>
            <span>Kinerja Waspang</span>
        </a>

        {{-- 4. ASSIGN WASPANG --}}
        <a href="{{ route('pm.assign.waspang') }}"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('pm.assign.waspang') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('pm.assign.waspang'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-indigo-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-star">
                    <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/><path d="M8 15H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/>
                </svg>    
            </div>
            <span>Assign Waspang</span>
        </a>

        <p class="px-3 pt-5 mb-2 text-xs font-bold uppercase text-gray-400 tracking-wider">
            Management Report
        </p>

        {{-- 5. REKAP PROGRESS LOP --}}
        <a href="{{ route('pm.rekap.progress') }}"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('pm.rekap.progress') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('pm.rekap.progress'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-indigo-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-no-axes-combined">
                    <path d="M12 16v5"/><path d="M16 14.639V21"/><path d="M20 10.656V21"/><path d="m22 3-8.646 8.646a.5.5 0 0 1-.708 0L9.354 8.354a.5.5 0 0 0-.707 0L2 15"/><path d="M4 18.463V21"/><path d="M8 14.656V21"/>
                </svg>
            </div>    
            <span>Rekap Progress LOP</span>
        </a>

    </nav>
</aside>