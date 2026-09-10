<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Waspang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#F8FAFC] text-slate-900">

<div class="min-h-screen max-w-md mx-auto bg-[#F8FAFC] pb-24 shadow-2xl shadow-slate-900/10 border-x border-slate-200/60">

    <div class="bg-[#1565D8] text-white px-6 pt-8 pb-10 rounded-b-[2rem] shadow-lg shadow-slate-900/10">

        <h1 class="text-2xl font-black tracking-tight">
            Profil
        </h1>

        <p class="text-sm text-blue-100 font-medium mt-1">
            Informasi akun waspang
        </p>

    </div>

    <div class="px-5 -mt-6">

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="w-16 h-16 rounded-2xl bg-[#1565D8] text-white flex items-center justify-center text-xl font-black shrink-0 shadow-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>

                <div class="min-w-0">
                    <h2 class="text-lg font-bold truncate">
                        {{ auth()->user()->name }}
                    </h2>

                    <p class="text-sm text-slate-500 truncate">
                        {{ auth()->user()->username }}
                    </p>

                    <span class="inline-flex items-center gap-1 mt-2 px-2.5 py-1 rounded-full bg-blue-100 text-[#0F4FAF] text-[10px] font-bold uppercase tracking-wide">
                        <i class="fa-solid fa-user-shield"></i>
                        {{ auth()->user()->role }}
                    </span>
                </div>

            </div>

        </div>

        <div class="bg-white border border-slate-200 rounded-2xl mt-4 overflow-hidden shadow-xs">

            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center text-xs shrink-0">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-400 font-medium">
                        Nama
                    </p>
                    <p class="font-bold text-sm text-slate-900 truncate">
                        {{ auth()->user()->name }}
                    </p>
                </div>
            </div>

            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center text-xs shrink-0">
                    <i class="fa-solid fa-at"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-400 font-medium">
                        Username
                    </p>
                    <p class="font-bold text-sm text-slate-900 truncate">
                        {{ auth()->user()->username }}
                    </p>
                </div>
            </div>

            <div class="px-5 py-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center text-xs shrink-0">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-400 font-medium">
                        Role
                    </p>
                    <p class="font-bold text-sm text-slate-900 uppercase truncate">
                        {{ auth()->user()->role }}
                    </p>
                </div>
            </div>

        </div>

        <div x-data="{ showPasswordForm: {{ ((isset($errors) && $errors->updatePassword->any()) || session('status') === 'password-updated') ? 'true' : 'false' }} }"
             class="bg-white border border-slate-200 rounded-2xl mt-4 overflow-hidden shadow-xs">

            <button type="button"
                    @click="showPasswordForm = !showPasswordForm"
                    class="w-full px-5 py-4 flex items-center justify-between text-left">
                <span class="flex items-center gap-3 font-bold text-sm text-slate-900">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center text-xs shrink-0">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    Ganti Password
                </span>
                <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform" :class="showPasswordForm ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="showPasswordForm" x-collapse class="px-5 pb-5 border-t border-slate-100 pt-4">
                <x-change-password-form />
            </div>
        </div>

        <form method="POST"
              action="{{ route('logout') }}"
              class="mt-5">
            @csrf

            <button type="submit"
                    class="w-full h-12 rounded-2xl bg-red-500 hover:bg-red-600 text-white font-bold text-sm shadow-sm transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </button>
        </form>

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'profil'])

</div>

</body>
</html>