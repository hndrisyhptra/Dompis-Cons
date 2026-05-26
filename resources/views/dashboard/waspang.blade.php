<x-app-layout>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-xl p-6">

                <h1 class="text-3xl font-bold text-gray-800">
                    Dashboard Waspang
                </h1>

                <p class="text-gray-500 mt-2">
                    Selamat datang {{ auth()->user()->name }}
                </p>

            </div>

        </div>

    </div>

</x-app-layout>