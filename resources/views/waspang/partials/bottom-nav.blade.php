<div class="fixed bottom-0 left-0 right-0 z-40">
    <div class="max-w-md mx-auto bg-white border-t border-gray-200 px-4 py-2">

        <div class="grid grid-cols-5 items-end text-center text-xs">

            <a href="{{ route('waspang.dashboard') }}"
                class="flex flex-col items-center gap-1 {{ $active == 'home' ? 'text-blue-700' : 'text-gray-500' }}">
                
                <!-- Heroicons: Home (Outline) -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21.75h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21.822V10.5M15.75 21.822V10.5" />
                </svg>

                <span class="text-xs font-medium">Home</span>
            </a>

           <a href="{{ route('waspang.inbox') }}"
            class="flex flex-col items-center gap-1 {{ $active == 'inbox' ? 'text-blue-700' : 'text-gray-500' }}">
                
                <div class="relative">
                    <!-- Heroicons: Inbox (Outline) -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13.5l3 3 3-3m-3 3v-6m10.125-3H17.25m3 0v1.125c0 .621-.504 1.125-1.125 1.125H3.75A1.125 1.125 0 012.625 11.25V5.25m17.625 0A1.125 1.125 0 0019.125 4.125H4.875A1.125 1.125 0 003.75 5.25m17.625 0v11.25c0 .621-.504 1.125-1.125 1.125H3.75a1.125 1.125 0 01-1.125-1.125V5.25t" />
                    </svg>

                    <!-- Dot Merah Penanda Pesan Baru -->
                    <!-- <span class="absolute -top-0.5 -right-0.5 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"></span> -->
                </div>

                <span class="text-xs font-medium">Inbox</span>
            </a>

            <a href="{{ route('waspang.inbox') }}"
                class="flex flex-col items-center relative -mt-8">
                
                <div class="mx-auto w-14 h-14 rounded-full bg-blue-700 text-white flex items-center justify-center shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 5.25h16.5m-16.5-10.5h16.5" />
                    </svg>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mt-1">List</p>
            </a>

           <a href="{{ route('waspang.notifications') }}"
            class="flex flex-col items-center gap-1 {{ $active == 'notif' ? 'text-blue-700' : 'text-gray-500' }}">
                
                <div class="relative">
                    <!-- Heroicons: Bell (Outline) -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>

                    <!-- Opsional: Dot Merah Penanda Notifikasi Baru (Hapus baris di bawah ini jika tidak dipakai) -->
                    <!-- <span class="absolute -top-0.5 -right-0.5 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"></span> -->
                </div>

                <span class="text-xs font-medium">Notif</span>
            </a>

            <a href="{{ route('waspang.profile') }}"
                class="flex flex-col items-center gap-1 {{ $active == 'profil' ? 'text-blue-700' : 'text-gray-500' }}">
                
                <!-- Heroicons: User (Outline) -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>

                <span class="text-xs font-medium">Profil</span>
            </a>

        </div>

    </div>
</div>