<aside class="hidden lg:flex lg:flex-col w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 min-h-screen sticky top-0">

    {{-- Header / Logo --}}
    <div class="h-16 px-5 flex items-center border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo-dompis-cons.png') }}" alt="Logo" class="w-9 h-9">
            <div>
                <h1 class="text-base font-black tracking-tight text-gray-900 dark:text-white">
                    DOMPIS <span class="text-blue-600">Cons</span>
                </h1>
                <p class="text-xs text-gray-500">
                    SDI Portal
                </p>
            </div>
        </div>
    </div>

    {{-- Menu Navigasi --}}
    <nav class="flex-1 p-4 space-y-1">

        {{-- Label Kategori --}}
        <p class="px-3 mb-2 text-xs font-bold uppercase text-gray-400">
            Main Menu
        </p>

        {{-- Menu Approval UIM --}}
        <a href="{{ route('sdi.index') }}"
        class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
        {{ request()->routeIs('sdi.index') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            {{-- Indikator Aktif (Garis Kiri) --}}
            @if(request()->routeIs('sdi.index'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif

            {{-- Ikon --}}
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 
                {{ request()->routeIs('sdi.index') ? 'bg-blue-100 dark:bg-blue-600/60' : 'bg-gray-100 dark:bg-gray-800' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="{{ request()->routeIs('sdi.index') ? 'text-blue-600 dark:text-blue-300' : 'text-gray-500' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4C15.5 8.5 16.5 7 18 6" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z" />
                </svg>
            </div>

            {{-- Teks & Badge --}}
            <div class="flex flex-1 items-center justify-between">
                <span>Approval UIM</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full {{ request()->routeIs('sdi.index') ? 'bg-blue-200 text-blue-800' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                    PT 2
                </span>
            </div>
        </a>

    </nav>
    
    {{-- Footer Sidebar (Opsional, info role) --}}
    <div class="p-4 border-t border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse shrink-0"></div>
            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">SDI Access Active</span>
        </div>
    </div>

</aside>