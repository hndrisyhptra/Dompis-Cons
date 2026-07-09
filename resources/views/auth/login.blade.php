<x-guest-layout>

<div class="min-h-screen flex bg-white">

    {{-- LEFT IMAGE --}}
    <div class="hidden lg:block lg:w-2/3 relative overflow-hidden">

        <img
            src="{{ asset('images/login-bg.webp') }}"
            class="absolute inset-0 w-full h-full object-cover"
            alt="Login Background">

        {{-- Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-r from-black/40 via-black/15 to-transparent"></div>

        {{-- Optional Text --}}
        <div class="absolute bottom-12 left-12 text-white max-w-xl">

            <h2 class="text-5xl font-black leading-tight">
                DOMPIS CONS
            </h2>

            <p class="mt-4 text-xl text-gray-100 leading-relaxed">
                Digital Operation Monitoring Project Information System
                <br>
                Construction Telkom Akses Area 3
            </p>

        </div>

    </div>


    {{-- RIGHT LOGIN --}}
   <div class="w-full lg:w-[35%] bg-white flex items-center justify-center">

    <div class="w-full max-w-md px-10">

        {{-- Logo --}}
        <div class="mb-12">

            <img
                src="{{ asset('images/logo-dompis-cons.png') }}"
                class="w-20 h-20 rounded-2xl shadow-md">

            <h1 class="mt-6 text-4xl font-black text-gray-900">
                DOMPIS CONS
            </h1>

            <p class="mt-2 text-gray-500">
                Digital Operation Monitoring Project Information System
            </p>

        </div>

        <x-auth-session-status
            class="mb-6"
            :status="session('status')" />

        <form method="POST"
              action="{{ route('login') }}"
              class="space-y-7">

            @csrf

            {{-- Username --}}
            <div>

                <label
                    class="block text-sm font-semibold text-gray-700 mb-2">

                    Username

                </label>

                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan username"

                    class="w-full
                           border-0
                           border-b-2
                           border-gray-300
                           bg-transparent
                           px-0
                           py-3
                           text-lg
                           focus:border-blue-600
                           focus:ring-0
                           placeholder:text-gray-400">

                <x-input-error
                    :messages="$errors->get('username')"
                    class="mt-2"/>

            </div>

            {{-- Password --}}
            <div>

                <label
                    class="block text-sm font-semibold text-gray-700 mb-2">

                    Password

                </label>

                <div class="relative">

                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan password"

                        class="w-full
                               border-0
                               border-b-2
                               border-gray-300
                               bg-transparent
                               px-0
                               py-3
                               pr-12
                               text-lg
                               focus:border-blue-600
                               focus:ring-0
                               placeholder:text-gray-400">

                    <button
                        type="button"
                        onclick="togglePassword()"
                        class="absolute right-0 top-1/2 -translate-y-1/2 text-sm font-medium text-blue-600 hover:text-blue-800">

                        Show

                    </button>

                </div>

                <x-input-error
                    :messages="$errors->get('password')"
                    class="mt-2"/>

            </div>

            {{-- Remember --}}
            <div class="flex items-center justify-between">

                <label class="flex items-center">

                    <input
                        type="checkbox"
                        name="remember"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">

                    <span class="ml-2 text-sm text-gray-600">
                        Ingat saya
                    </span>

                </label>

            </div>

            {{-- Button --}}
            <button
                type="submit"
                class="w-full
                       h-12
                       rounded-xl
                       bg-blue-700
                       text-white
                       font-semibold
                       text-lg
                       hover:bg-blue-800
                       transition
                       duration-300">

                Login

            </button>

        </form>

        <div class="mt-12 text-sm text-gray-400">
            © {{ date('Y') }} DOMPIS CONS
        </div>

    </div>

</div>
</div>


<script>

function togglePassword(){

    const password=document.getElementById('password');
    const button=event.currentTarget;

    if(password.type==="password"){

        password.type="text";
        button.innerHTML="Hide";

    }else{

        password.type="password";
        button.innerHTML="Show";

    }

}

</script>

</x-guest-layout>