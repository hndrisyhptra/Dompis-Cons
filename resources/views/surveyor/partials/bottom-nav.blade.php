@php
    $active = $active ?? '';
@endphp

<div class="fixed bottom-0 left-0 right-0 z-40 safe-bottom">
    <div class="max-w-md mx-auto glass border-t border-slate-200/70 px-4 pt-2 pb-2 shadow-[0_-8px_24px_-8px_rgba(15,23,42,.15)]">
        <div class="grid grid-cols-3 items-end text-center text-xs">

            <a href="{{ route('surveyor.index') }}"
               class="flex flex-col items-center gap-1 py-1 {{ $active == 'home' ? 'text-blue-700' : 'text-slate-500' }}">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="text-[11px] font-bold">Beranda</span>
            </a>

            <a href="{{ route('surveyor.create') }}" class="flex flex-col items-center relative -mt-8">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center shadow-lg shadow-blue-600/30 ring-4 ring-white">
                    <i class="fa-solid fa-map-location-dot text-xl"></i>
                </div>
                <p class="text-[11px] font-bold text-slate-500 mt-1">Survey Baru</p>
            </a>

            <a href="{{ route('profile.edit') }}"
               class="flex flex-col items-center gap-1 py-1 {{ $active == 'profil' ? 'text-blue-700' : 'text-slate-500' }}">
                <i class="fa-solid fa-user-gear text-lg"></i>
                <span class="text-[11px] font-bold">Profil</span>
            </a>

        </div>
    </div>
</div>
