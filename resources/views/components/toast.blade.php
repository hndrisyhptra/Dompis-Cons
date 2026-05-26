<div
    x-data="{
        show: true
    }"

    x-init="
        setTimeout(() => show = false, 3000)
    "

    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"

    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"

    class="fixed top-5 right-5 z-[9999] w-full max-w-sm">

    {{-- Success --}}
    @if(session('success'))

        <div class="rounded-2xl border border-green-200
                    bg-white dark:bg-gray-900
                    dark:border-green-800
                    shadow-2xl overflow-hidden">

            <div class="flex items-start gap-4 p-4">

                {{-- Icon --}}
                <div class="w-11 h-11 rounded-2xl
                            bg-green-100 dark:bg-green-900/40
                            text-green-700 dark:text-green-300
                            flex items-center justify-center
                            text-lg font-bold shrink-0">

                    ✓

                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">

                    <h3 class="text-sm font-bold
                               text-gray-900 dark:text-white">

                        Success

                    </h3>

                    <p class="text-sm text-gray-500
                              dark:text-gray-400 mt-1 leading-relaxed">

                        {{ session('success') }}

                    </p>

                </div>

                {{-- Close --}}
                <button
                    @click="show = false"

                    class="text-gray-400 hover:text-gray-600
                           dark:hover:text-gray-200">

                    ×

                </button>

            </div>

            {{-- Progress --}}
            <div class="h-1 bg-green-500"></div>

        </div>

    @endif

    {{-- Error --}}
    @if(session('error'))

        <div class="rounded-2xl border border-red-200
                    bg-white dark:bg-gray-900
                    dark:border-red-800
                    shadow-2xl overflow-hidden">

            <div class="flex items-start gap-4 p-4">

                <div class="w-11 h-11 rounded-2xl
                            bg-red-100 dark:bg-red-900/40
                            text-red-700 dark:text-red-300
                            flex items-center justify-center
                            text-lg font-bold shrink-0">

                    !

                </div>

                <div class="flex-1 min-w-0">

                    <h3 class="text-sm font-bold
                               text-gray-900 dark:text-white">

                        Error

                    </h3>

                    <p class="text-sm text-gray-500
                              dark:text-gray-400 mt-1 leading-relaxed">

                        {{ session('error') }}

                    </p>

                </div>

                <button
                    @click="show = false"

                    class="text-gray-400 hover:text-gray-600
                           dark:hover:text-gray-200">

                    ×

                </button>

            </div>

            <div class="h-1 bg-red-500"></div>

        </div>

    @endif

</div>