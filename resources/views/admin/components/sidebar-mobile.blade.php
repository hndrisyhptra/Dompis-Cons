<aside class="flex flex-col w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 min-h-screen">

    {{-- Logo --}}
    <div class="h-16 px-5 flex items-center justify-between border-b border-gray-200 dark:border-gray-800">


        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold">
                DC
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
        <button @click="sidebarOpen = false"
                class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700">
            ×
        </button>

    </div>

    {{-- Menu --}}
    <div class="flex-1 p-4 space-y-1">

        {{-- Main --}}
        <p class="text-xs uppercase text-gray-400 font-semibold px-3 mb-2">
            Main Menu
        </p>

         <a href="{{ route('dashboard') }}"
            class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
            {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                @if(request()->routeIs('dashboard'))
                    <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                @endif

                <!-- Lucide: Layout Dashboard -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard">
                    <rect width="7" height="9" x="3" y="3" rx="1"/>
                    <rect width="7" height="5" x="14" y="3" rx="1"/>
                    <rect width="7" height="9" x="14" y="12" rx="1"/>
                    <rect width="7" height="5" x="3" y="16" rx="1"/>
                </svg>

                <span>Dashboard</span>
        </a>

        <a href="{{ route('projects.index') }}"
            class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
            {{ request()->routeIs('projects.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                
                @if(request()->routeIs('projects.index'))
                    <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                @endif

            <!-- Lucide: Book Open Check -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open-text-icon lucide-book-open-text">
                <path d="M12 7v14"/><path d="M16 12h2"/><path d="M16 8h2"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/><path d="M6 12h2"/><path d="M6 8h2"/></svg>

            <span>Project ID</span>
        </a>

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
                    Designator
                </a>

                <a href="{{ route('packages.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('packages.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    Paket KHS
                </a>

                <a href="{{ route('designator-prices.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('designator-prices.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    KHS
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

                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>

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
                    Bulk Import PID
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
                    Bulk Import BOQ
                </a>

            </div>
        </div>

        <a href="{{ route('assign-waspang.index') }}"
        class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
        {{ request()->routeIs('assign-waspang.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('assign-waspang.*'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-star-icon lucide-user-star">
              <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/><path d="M8 15H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/></svg>    
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
            
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pinned-icon lucide-map-pinned">
                <path d="M18 8c0 3.613-3.869 7.429-5.393 8.795a1 1 0 0 1-1.214 0C9.87 15.429 6 11.613 6 8a6 6 0 0 1 12 0"/><circle cx="12" cy="8" r="2"/><path d="M8.714 14h-3.71a1 1 0 0 0-.948.683l-2.004 6A1 1 0 0 0 3 22h18a1 1 0 0 0 .948-1.316l-2-6a1 1 0 0 0-.949-.684h-3.712"/>
            </svg> 
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

                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check-icon lucide-badge-check">
                    <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
                <span>
                Approval Eviden   
            </span>
        </a>

        <!-- <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
        
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-handshake-icon lucide-handshake">
                <path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/>
            </svg>
        <span>
            Uji Terima
        </span>
        </a> -->

        {{-- Report --}}
        <p class="text-xs uppercase text-gray-400 font-semibold px-3 mt-6 mb-2">
            Report
        </p>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl
                  hover:bg-gray-100 transition">
            
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-no-axes-combined-icon lucide-chart-no-axes-combined">
                <path d="M12 16v5"/><path d="M16 14.639V21"/><path d="M20 10.656V21"/><path d="m22 3-8.646 8.646a.5.5 0 0 1-.708 0L9.354 8.354a.5.5 0 0 0-.707 0L2 15"/><path d="M4 18.463V21"/><path d="M8 14.656V21"/>
            </svg>
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
           
           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-user-icon lucide-shield-user">
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M6.376 18.91a6 6 0 0 1 11.249.003"/><circle cx="12" cy="11" r="4"/>
            </svg>

            <span>
                User Management
            </span>
            
        </a>

    </div>

</aside>