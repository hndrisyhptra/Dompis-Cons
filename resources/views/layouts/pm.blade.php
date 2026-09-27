<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false, darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', value => localStorage.setItem('darkMode', value))"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-dompis-cons.png') }}">
    
    <title>Dompis Cons - PM Module</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-800 dark:bg-gray-950 dark:text-gray-100 antialiased font-sans">

<div class="min-h-screen flex">

    {{-- Desktop Sidebar Khusus PM --}}
    @include('pm.components.sidebar')

    {{-- Mobile Overlay --}}
    <div x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/40 z-40 lg:hidden">
    </div>

    {{-- Mobile Sidebar Khusus PM --}}
    <div x-show="sidebarOpen"
         x-transition
         class="fixed inset-y-0 left-0 z-50 w-64 lg:hidden">
        @include('pm.components.sidebar-mobile')
    </div>

    {{-- Main Content Area --}}
    <div class="flex-1 min-w-0 flex flex-col">

        {{-- Topbar Khusus PM (Bisa diduplikasi/disesuaikan dari topbar admin ke folder pm) --}}
        @include('pm.components.topbar')

        <main class="p-4 lg:p-6 flex-1">
            {{-- Toast Notification System --}}
            <x-toast />

            @yield('content')
        </main>

    </div>

</div>

@stack('scripts')

</body>
</html>
