<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <title>DOMPIS CONS - Waspang Mobile</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f7f6f2] text-gray-900 min-h-full antialiased selection:bg-blue-500 selection:text-white">

    {{-- Main Mobile Container Wrapper --}}
    <main class="relative min-h-screen max-w-md mx-auto bg-[#f7f6f2] shadow-xl border-x border-gray-200/30">
        
        {{-- Flash Session Toast/Alert Bawaan (Jika Ada) --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                 class="fixed top-4 inset-x-4 max-w-sm mx-auto z-50 bg-emerald-600 text-white p-3.5 rounded-xl text-xs font-bold shadow-lg flex items-center gap-2 animate-fade-in">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                 class="fixed top-4 inset-x-4 max-w-sm mx-auto z-50 bg-red-600 text-white p-3.5 rounded-xl text-xs font-bold shadow-lg flex items-center gap-2 animate-fade-in">
                <i class="fa-solid fa-circle-xmark text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Konten Utama Halaman Step Lapangan --}}
        @yield('content')

    </main>

    {{-- Script JavaScript Penampung Utama (Untuk SweetAlert2 & JQuery Global) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    {{-- Tempat menyuntikkan script SweetAlert2 dari instalasi.blade --}}
    @yield('scripts')

</body>
</html>