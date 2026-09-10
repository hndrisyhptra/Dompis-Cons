<div class="fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto">
    <div class="bg-white/95 backdrop-blur-md border-t border-slate-100 shadow-[0_-8px_24px_-8px_rgba(30,27,75,0.12)] px-3 pt-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))]">

        <div class="grid grid-cols-5 items-end text-center text-xs">

            <a href="{{ route('waspang.dashboard') }}"
                class="flex flex-col items-center gap-1 py-1 rounded-xl transition {{ $active == 'home' ? 'text-[#1565D8]' : 'text-slate-400' }}">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="text-[10px] font-bold">Home</span>
            </a>

            <a href="{{ route('waspang.inbox') }}"
                class="flex flex-col items-center gap-1 py-1 rounded-xl transition {{ $active == 'inbox' ? 'text-[#1565D8]' : 'text-slate-400' }}">
                <i class="fa-solid fa-inbox text-lg"></i>
                <span class="text-[10px] font-bold">Inbox</span>
            </a>

            <a href="{{ route('surveyor.index') }}"
                class="flex flex-col items-center relative -mt-7">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-[#1565D8] text-white flex items-center justify-center shadow-lg shadow-slate-900/20 ring-4 ring-white transition active:scale-95">
                    <i class="fa-solid fa-location-dot text-xl"></i>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-1">Survey</p>
            </a>

            <a href="{{ route('waspang.notifications') }}"
                class="flex flex-col items-center gap-1 py-1 rounded-xl transition {{ $active == 'notif' ? 'text-[#1565D8]' : 'text-slate-400' }}">
                <i class="fa-solid fa-bell text-lg"></i>
                <span class="text-[10px] font-bold">Notif</span>
            </a>

            <a href="{{ route('waspang.profile') }}"
                class="flex flex-col items-center gap-1 py-1 rounded-xl transition {{ $active == 'profil' ? 'text-[#1565D8]' : 'text-slate-400' }}">
                <i class="fa-solid fa-user text-lg"></i>
                <span class="text-[10px] font-bold">Profil</span>
            </a>

        </div>

    </div>
</div>
