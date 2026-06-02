<header class="h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 lg:px-6">

    <div class="h-full flex items-center justify-between">

        <div class="flex items-center gap-3">

            {{-- Mobile Button --}}
            <button @click="sidebarOpen = true"
                    class="lg:hidden w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center">
                ☰
            </button>

            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                    Dashboard Admin
                </h1>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Monitoring Project Kontruksi
                </p>
            </div>

        </div>

        <div class="flex items-center gap-3">

            {{-- Dark Toggle --}}
            <button @click="darkMode = !darkMode"
                    class="w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors focus:outline-none">
                
                <!-- Heroicons: Moon (Muncul saat Mode Terang) -->
                <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>

                <!-- Heroicons: Sun (Muncul saat Mode Gelap) -->
                <svg x-show="darkMode" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-yellow-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21M5.25 12H3m18 0h-2.25m-1.314-6.364-1.591 1.591M6.82 17.18l-1.591 1.591M16.95 17.18l1.591 1.591M5.25 5.25l1.591 1.591M12 7.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z" />
                </svg>
            </button>

            <div class="hidden sm:block text-right">
                <p class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-xs uppercase text-gray-500">
                    {{ auth()->user()->role }}
                </p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button class="h-10 px-4 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold">
                    Logout
                </button>
            </form>

        </div>

    </div>

</header>