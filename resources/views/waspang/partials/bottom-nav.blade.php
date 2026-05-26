<div class="fixed bottom-0 left-0 right-0 z-40">
    <div class="max-w-md mx-auto bg-white border-t border-gray-200 px-4 py-2">

        <div class="grid grid-cols-5 items-end text-center text-xs">

            <a href="{{ route('waspang.dashboard') }}"
               class="{{ $active == 'home' ? 'text-blue-700' : 'text-gray-500' }}">
                <div class="text-2xl">⌂</div>
                Home
            </a>

            <a href="{{ route('waspang.inbox') }}"
               class="relative {{ $active == 'inbox' ? 'text-blue-700' : 'text-gray-500' }}">
                <span class="absolute top-0 right-6 w-2.5 h-2.5 bg-red-500 rounded-full"></span>
                <div class="text-2xl">▣</div>
                Inbox
            </a>

            <a href="{{ route('waspang.inbox') }}"
               class="relative -mt-8">
                <div class="mx-auto w-14 h-14 rounded-full bg-blue-700 text-white flex items-center justify-center text-3xl shadow-lg">
                    +
                </div>
                <p class="text-gray-500 mt-1">Upload</p>
            </a>

            <a href="#"
               class="{{ $active == 'notif' ? 'text-blue-700' : 'text-gray-500' }}">
                <div class="text-2xl">♧</div>
                Notif
            </a>

            <a href="{{ route('waspang.profile') }}"
                class="{{ $active == 'profil' ? 'text-blue-700' : 'text-gray-500' }}">
                <div class="text-2xl">♙</div>
                Profil
            </a>

        </div>

    </div>
</div>