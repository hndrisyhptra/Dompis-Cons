<aside class="hidden lg:flex lg:flex-col w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 min-h-screen">

    {{--
        Sidebar khusus role OFFICER. Sengaja file terpisah (bukan reuse
        admin/components/sidebar.blade.php) karena menu-nya beda: sama
        seperti admin, TAPI tanpa Master Designator, Bulk Import Data, dan
        Approval Eviden -- serta ditambah User Management (lihat instruksi
        user). Karena file ini HANYA pernah di-include untuk role officer
        (lihat layouts/admin.blade.php), kondisi role-check yang ada di versi
        admin (mis. sembunyikan Inbox utk superadmin, User Management hanya
        utk superadmin) sengaja TIDAK dipakai lagi di sini.
    --}}

    {{-- Header / Logo --}}
    <div class="h-16 px-5 flex items-center border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo-dompis-cons.png') }}" alt="Logo" class="w-9 h-9">
            <div>
                <h1 class="text-base font-black tracking-tight text-gray-900 dark:text-white">
                    DOMPIS <span class="text-blue-600">Cons</span>
                </h1>
                <p class="text-xs text-gray-500">
                    Officer Panel
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
            $inboxOpen = request()->routeIs('admin.inbox*') || request()->routeIs('admin.history*');
        @endphp

        {{-- INBOX: file ini hanya dipakai officer, jadi selalu ditampilkan
             (di versi admin, menu ini disembunyikan khusus utk superadmin) --}}
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open"
                x-transition
                class="mt-1 ml-5 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-1">

                <a href="{{ route('admin.inbox') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.inbox') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Active Project
                    </span>
                </a>

                {{-- SUB-MENU BARU UNTUK PT 2 --}}
                <a href="{{ route('admin.inbox.pt2') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.inbox.pt2') ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                        Active PT 2
                    </span>
                </a>

                <a href="{{ route('admin.history') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.history*') ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        History
                    </span>
                </a>

            </div>
        </div>

        @php
            // Mendeteksi apakah salah satu dari menu Project / Program sedang aktif
            $projectOpen = request()->routeIs('program.*') || request()->routeIs('admin.pt2.index');
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
                x-cloak
                class="mt-2 ml-5 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-1">

                <a href="{{ route('program.osp') }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ request()->routeIs('program.osp') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        OSP
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Fiber</span>
                </a>

                <a href="{{ route('program.nodeb') }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ request()->routeIs('program.nodeb') ? 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        NODE B
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Site</span>
                </a>

                <a href="{{ route('program.hem') }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ request()->routeIs('program.hem') ? 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        HEM
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">HEM</span>
                </a>

                <a href="{{ route('program.olo') }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ request()->routeIs('program.olo') ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        OLO
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-green-100 text-green-700">Partner</span>
                </a>

                <a href="{{ route('program.konstruk') }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ request()->routeIs('program.konstruk') ? 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Eksternal
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-100 text-red-700">Exbis</span>
                </a>

                <a href="{{ route('admin.pt2.index') }}"
                class="group flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold transition
                {{ request()->routeIs('admin.pt2.*') ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                        PT 2
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-700">Swakelola</span>
                </a>

                </div>
        </div>

        {{--
            NOTE: Menu "Master Designator" dan "Bulk Import Data" SENGAJA
            dihapus dari sidebar officer (permintaan user), beda dengan versi
            admin di admin/components/sidebar.blade.php.
        --}}

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

        {{-- SURVEY LAPANGAN (DROPDOWN) --}}
        @php
            $surveyLapanganOpen = request()->routeIs('admin.site-surveys.*')
                || request()->routeIs('admin.gis-cad.*');
        @endphp

        <div x-data="{ open: {{ $surveyLapanganOpen ? 'true' : 'false' }} }">

            <button type="button"
                    @click="open = !open"
                    class="w-full relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
                    {{ $surveyLapanganOpen ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                <div class="flex items-center gap-3">
                    @if($surveyLapanganOpen)
                        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
                    @endif

                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list-icon lucide-clipboard-list">
                            <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>
                        </svg>
                    </div>

                    <span>Survey Lapangan</span>
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

                <a href="{{ route('admin.site-surveys.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.site-surveys.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Hasil Survey Lapangan
                    </span>
                </a>

                <a href="{{ route('admin.gis-cad.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.gis-cad.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Generate/Export CAD
                    </span>
                </a>

            </div>
        </div>

        {{--
            NOTE: Menu "Approval Eviden" SENGAJA dihapus dari sidebar officer
            (permintaan user), beda dengan versi admin.
        --}}

        {{-- RESULT FILE (DROPDOWN) --}}
        @php
            $resultFileOpen = request()->routeIs('admin.pt2.baut.*')
                || request()->routeIs('admin.pt2.lact.*');
        @endphp

        <div x-data="{ open: {{ $resultFileOpen ? 'true' : 'false' }} }">

            <button type="button"
                    @click="open = !open"
                    class="w-full relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
                    {{ $resultFileOpen ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                <div class="flex items-center gap-3">
                    @if($resultFileOpen)
                        <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-indigo-600"></span>
                    @endif

                    <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-handshake-icon lucide-handshake">
                            <path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/>
                        </svg>
                    </div>

                    <span>Result File</span>
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

                <a href="{{ route('admin.pt2.baut.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.pt2.baut.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Berkas BAUT
                    </span>
                </a>

                <a href="{{ route('admin.pt2.lact.index') }}"
                class="block px-3 py-2 rounded-lg text-sm font-semibold transition
                {{ request()->routeIs('admin.pt2.lact.*') ? 'bg-violet-50 text-violet-700 dark:bg-violet-950 dark:text-violet-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                        Berkas LACT
                    </span>
                </a>

            </div>
        </div>

        <p class="px-3 pt-5 mb-2 text-xs font-bold uppercase text-gray-400">
            Report
        </p>

        <a href="{{ route('admin.report_deployment') }}"
            class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
            {{ request()->routeIs('admin.report_deployment') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('admin.report_deployment'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard">
                    <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
                </svg>
            </div>

            <span>
                Report Deployment
            </span>
        </a>

        <a href="{{ route('admin.rekap_progress') }}"
            class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
            {{ request()->routeIs('admin.rekap_progress') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

            @if(request()->routeIs('admin.dashboard.rekap_progress'))
                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-600"></span>
            @endif

            <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-600/60 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chart-no-axes-combined-icon lucide-chart-no-axes-combined">
                    <path d="M12 16v5"/><path d="M16 14.639V21"/><path d="M20 10.656V21"/><path d="m22 3-8.646 8.646a.5.5 0 0 1-.708 0L9.354 8.354a.5.5 0 0 0-.707 0L2 15"/><path d="M4 18.463V21"/><path d="M8 14.656V21"/>
                </svg>
            </div>

            <span>
                Rekap Progress
            </span>
        </a>

        {{-- User Management: menu BARU khusus utk officer (selalu tampil di
             file ini -- akses & pembatasan aksi sudah dijaga sepenuhnya di
             UserManagementController, bukan sekadar disembunyikan di sini) --}}
        <a href="{{ route('admin.users.index') }}"
           class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('admin.users.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">

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

    {{-- Footer Sidebar (info role) --}}
    <div class="p-4 border-t border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse shrink-0"></div>
            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Officer Access Active</span>
        </div>
    </div>

</aside>
