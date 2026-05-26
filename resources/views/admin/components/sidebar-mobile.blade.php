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
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold
           {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
            <span>📊</span>
            Dashboard
        </a>

        <a href="{{ route('projects.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold
           {{ request()->routeIs('projects.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
            <span>📁</span>
            LOP & BOQ
        </a>

        <a href="{{ route('designators.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold
            {{ request()->routeIs('designators.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                <span>🏷️</span>
                Master Designator
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
            <span>👷</span>
            Assign Waspang
        </a>

        <a href="{{ route('admin.evidences.approval') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm 
        font-semibold transition {{ request()->routeIs('admin.evidences.*') ? text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
         @if(request()->routeIs('admin.evidences.*'))
        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
        @endif
            <span>✅</span>
            Approval Eviden
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
            <span>📄</span>
            Uji Terima
        </a>

        {{-- Report --}}
        <p class="text-xs uppercase text-gray-400 font-semibold px-3 mt-6 mb-2">
            Report
        </p>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl
                  hover:bg-gray-100 transition">

            <span>📈</span>
            Rekap Progress

        </a>

        <a href="#"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl
                  hover:bg-gray-100 transition">

            <span>👤</span>
            User Management

        </a>

    </div>

</aside>