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
                    class="w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800">
                <span x-show="!darkMode">🌙</span>
                <span x-show="darkMode">☀️</span>
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