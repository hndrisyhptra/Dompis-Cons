<x-guest-layout>

    <div class="min-h-screen bg-[#f7f6f2] flex items-center justify-center px-4 py-4">

        <div class="w-full max-w-sm">

            {{-- LOGIN CARD --}}
            <div class="bg-white border border-gray-200 rounded-[1.7rem] shadow-lg overflow-hidden">

                {{-- HEADER --}}
                <div class="bg-blue-700 px-5 py-5 text-white text-center">

                    <div class="mx-auto w-14 h-14 rounded-2xl bg-white overflow-hidden flex items-center justify-center mb-3">
                        <img
                            src="{{ asset('images/logo-dompis-cons.png') }}"
                            alt="DOMPIS CONS"
                            class="w-full h-full object-cover">
                    </div>

                    <h1 class="text-2xl font-black leading-tight">
                        DOMPIS CONS
                    </h1>

                    <p class="text-xs opacity-90 mt-1">
                        Kontruksi Telkom Akses Area 3
                    </p>

                </div>

                {{-- BODY --}}
                <div class="p-5">

                    <x-auth-session-status class="mb-3" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf

                        {{-- Username --}}
                        <div>
                            <label for="username" class="block text-xs font-bold text-gray-600 mb-1.5">
                                Username
                            </label>

                            <input id="username"
                                   type="text"
                                   name="username"
                                   value="{{ old('username') }}"
                                   required
                                   autofocus
                                   autocomplete="username"
                                   placeholder="Masukkan username"
                                   class="w-full h-11 rounded-xl border border-gray-300 bg-gray-50 px-4 text-sm font-medium
                                          focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">

                            <x-input-error :messages="$errors->get('username')" class="mt-1.5" />
                        </div>

                        {{-- Password --}}
                        <div>
                            <label for="password" class="block text-xs font-bold text-gray-600 mb-1.5">
                                Password
                            </label>

                            <div class="relative">
                                <input id="password"
                                       type="password"
                                       name="password"
                                       required
                                       autocomplete="current-password"
                                       placeholder="Masukkan password"
                                       class="w-full h-11 rounded-xl border border-gray-300 bg-gray-50 px-4 pr-12 text-sm font-medium
                                              focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">

                                <button type="button"
                                        onclick="togglePassword()"
                                        class="absolute inset-y-0 right-0 px-4 text-xs font-bold text-gray-500">
                                    Show
                                </button>
                            </div>

                            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                        </div>

                        {{-- Remember --}}
                        <div class="flex items-center justify-between pt-1">

                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me"
                                       type="checkbox"
                                       name="remember"
                                       class="rounded border-gray-300 text-blue-700 shadow-sm focus:ring-blue-600">

                                <span class="ml-2 text-xs text-gray-600">
                                    Ingat saya
                                </span>
                            </label>

                            <!--@if (Route::has('password.request'))-->
                            <!--    <a href="{{ route('password.request') }}"-->
                            <!--       class="text-xs font-bold text-blue-700">-->
                            <!--        Lupa password?-->
                            <!--    </a>-->
                            <!--@endif-->

                        </div>

                        {{-- Button --}}
                        <button type="submit"
                                class="w-full h-11 rounded-xl bg-blue-700 text-white text-sm font-black
                                       hover:bg-blue-800 active:scale-[0.98] transition">
                            Login
                        </button>

                    </form>

                </div>

            </div>

            <p class="text-center text-[11px] text-gray-400 mt-4">
                © {{ date('Y') }} DOMPIS CONS
            </p>

        </div>

    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const button = event.currentTarget;

            if (password.type === 'password') {
                password.type = 'text';
                button.innerText = 'Hide';
            } else {
                password.type = 'password';
                button.innerText = 'Show';
            }
        }
    </script>

</x-guest-layout>