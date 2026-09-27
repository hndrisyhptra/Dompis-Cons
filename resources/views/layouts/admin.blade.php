<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false, darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', value => localStorage.setItem('darkMode', value))"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-dompis-cons.png') }}">
    

    <title>Dompis Cons</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @stack('head-scripts')
</head>

<body class="bg-gray-100 text-gray-800 dark:bg-gray-950 dark:text-gray-100">

<div class="min-h-screen flex">

    {{-- Desktop Sidebar --}}
    {{-- Role super_tif & officer punya component sidebar sendiri
         (resources/views/super_tif|officer/components) supaya menu yang
         ditampilkan/disembunyikan untuk role tsb bisa diedit langsung di
         filenya sendiri, terpisah dari sidebar admin/superadmin.
         Role tif & pm memakai sidebar PM (resources/views/pm/components) --
         beberapa halaman bersama (mis. Tracking Progress) masih pakai
         layouts.admin, jadi role-nya perlu tetap melihat sidebar PM mereka
         sendiri di sini, bukan sidebar admin. --}}
    @if(auth()->user()?->role === 'super_tif')
        @include('super_tif.components.sidebar')
    @elseif(auth()->user()?->role === 'officer')
        @include('officer.components.sidebar')
    @elseif(in_array(auth()->user()?->role, ['tif', 'pm'], true))
        @include('pm.components.sidebar')
    @else
        @include('admin.components.sidebar')
    @endif

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
        @if(auth()->user()?->role === 'super_tif')
            @include('super_tif.components.sidebar-mobile')
        @elseif(auth()->user()?->role === 'officer')
            @include('officer.components.sidebar-mobile')
        @elseif(in_array(auth()->user()?->role, ['tif', 'pm'], true))
            @include('pm.components.sidebar-mobile')
        @else
            @include('admin.components.sidebar-mobile')
        @endif
    </div>

    {{-- Main --}}
    <div class="flex-1 min-w-0 flex flex-col">

        @include('admin.components.topbar')

        <main class="p-4 lg:p-6">
            
            
            <x-toast />

            @include('approval-center.partials.login-alert')

            @yield('content')
        </main>

    </div>

</div>

@stack('vendor-scripts')
@stack('scripts')

</body>
</html>
