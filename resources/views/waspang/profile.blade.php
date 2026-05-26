<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Waspang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    <div class="bg-blue-700 text-white px-6 pt-8 pb-8 rounded-b-[2rem]">

        <h1 class="text-2xl font-bold">
            Profil
        </h1>

        <p class="text-sm opacity-90 mt-1">
            Informasi akun waspang
        </p>

    </div>

    <div class="px-5 -mt-5">

        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="w-16 h-16 rounded-full bg-blue-700 text-white flex items-center justify-center text-xl font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>

                <div>
                    <h2 class="text-lg font-bold">
                        {{ auth()->user()->name }}
                    </h2>

                    <p class="text-sm text-gray-500">
                        {{ auth()->user()->username }}
                    </p>

                    <span class="inline-block mt-2 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold uppercase">
                        {{ auth()->user()->role }}
                    </span>
                </div>

            </div>

        </div>

        <div class="bg-white border border-gray-200 rounded-2xl mt-4 overflow-hidden">

            <div class="px-5 py-4 border-b border-gray-100">
                <p class="text-sm text-gray-500">
                    Nama
                </p>
                <p class="font-bold">
                    {{ auth()->user()->name }}
                </p>
            </div>

            <div class="px-5 py-4 border-b border-gray-100">
                <p class="text-sm text-gray-500">
                    Username
                </p>
                <p class="font-bold">
                    {{ auth()->user()->username }}
                </p>
            </div>

            <div class="px-5 py-4">
                <p class="text-sm text-gray-500">
                    Role
                </p>
                <p class="font-bold uppercase">
                    {{ auth()->user()->role }}
                </p>
            </div>

        </div>

        <form method="POST"
              action="{{ route('logout') }}"
              class="mt-5">
            @csrf

            <button type="submit"
                    class="w-full h-12 rounded-2xl bg-red-500 hover:bg-red-600 text-white font-bold">
                Logout
            </button>
        </form>

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'profil'])

</div>

</body>
</html>