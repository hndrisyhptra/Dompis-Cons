<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false, darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', value => localStorage.setItem('darkMode', value))"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <title>Dompis Cons</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 text-gray-800 dark:bg-gray-950 dark:text-gray-100">

<div class="min-h-screen flex">

    {{-- Desktop Sidebar --}}
    @include('admin.components.sidebar')

    {{-- Mobile Overlay --}}
    <div x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/40 z-40 lg:hidden">
    </div>

    {{-- Mobile Sidebar --}}
    <div x-show="sidebarOpen"
         x-transition
         class="fixed inset-y-0 left-0 z-50 w-64 lg:hidden">
        @include('admin.components.sidebar-mobile')
    </div>

    {{-- Main --}}
    <div class="flex-1 min-w-0 flex flex-col">

        @include('admin.components.topbar')

        <main class="p-4 lg:p-6">
            
            
            <x-toast />

            @yield('content')
        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@stack('scripts')

</body>
</html>