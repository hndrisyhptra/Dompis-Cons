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

            <span>📊</span>
            Dashboard
        </a>

        <a href="{{ route('projects.index') }}"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('projects.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('projects.*'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif

            <span>📁</span>
            LOP & BOQ
        </a>

        <a href="{{ route('designators.index') }}"
            class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
            {{ request()->routeIs('designators.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                @if(request()->routeIs('designators.*'))
                    <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                @endif

                <span>🏷️</span>
                Master Designator
        </a>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <span>👷</span>
            Assign Waspang
        </a>

        <p class="px-3 pt-5 mb-2 text-xs font-bold uppercase text-gray-400">
            Monitoring
        </p>

        <a href="{{ route('admin.map.monitoring') }}"
            class="flex items-center gap-3 px-4 py-2 rounded-xl text-sm font-semibold
            {{ request()->routeIs('admin.map.monitoring') ? 'bg-blue-700 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                🗺️ Map Monitoring
        </a>

        <a href="{{ route('admin.evidences.approval') }}"
            class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
            {{ request()->routeIs('admin.evidences.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('admin.evidences.*'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif

            <span>✅</span>
            Approval Eviden
        </a>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <span>📄</span>
            Uji Terima
        </a>

        <p class="px-3 pt-5 mb-2 text-xs font-bold uppercase text-gray-400">
            Laporan
        </p>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <span>📈</span>
            Rekap & Statistik
        </a>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <span>👤</span>
            Manajemen User
        </a>

    </nav>

</aside>