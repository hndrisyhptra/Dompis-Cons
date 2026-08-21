<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Survey Lapangan') - DOMPIS Cons</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-dompis-cons.png') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
            overscroll-behavior-y: contain;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-thumb { background: rgba(15,23,42,.15); border-radius: 10px; }
        .glass {
            background: rgba(255,255,255,.72);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
        .safe-top { padding-top: env(safe-area-inset-top); }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-[#eef1f7] text-slate-900 min-h-full antialiased selection:bg-blue-500 selection:text-white">

    <main class="relative min-h-screen max-w-md mx-auto bg-[#eef1f7] shadow-2xl border-x border-slate-200/60 pb-28">

        {{-- Toast Flash --}}
        <div id="flashToastRoot" class="fixed top-4 inset-x-4 max-w-sm mx-auto z-[60] space-y-2 safe-top"></div>

        @if(session('success'))
            <div data-flash="success" data-flash-text="{{ session('success') }}"></div>
        @endif
        @if(session('error'))
            <div data-flash="error" data-flash-text="{{ session('error') }}"></div>
        @endif

        @yield('content')

    </main>

    @hasSection('bottom-nav')
        @yield('bottom-nav')
    @else
        @include('surveyor.partials.bottom-nav')
    @endif

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Render flash messages sebagai toast kecil bergaya modern
        document.querySelectorAll('[data-flash]').forEach(function (el) {
            const type = el.getAttribute('data-flash');
            const text = el.getAttribute('data-flash-text');
            const bg = type === 'success' ? 'bg-emerald-600' : 'bg-red-600';
            const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark';

            const toast = document.createElement('div');
            toast.className = `${bg} text-white p-3.5 rounded-2xl text-xs font-bold shadow-xl flex items-center gap-2`;
            toast.innerHTML = `<i class="fa-solid ${icon} text-base"></i><span>${text}</span>`;

            document.getElementById('flashToastRoot').appendChild(toast);
            setTimeout(() => { toast.style.transition = 'opacity .4s'; toast.style.opacity = '0'; }, 2600);
            setTimeout(() => toast.remove(), 3200);
        });
    </script>

    @stack('scripts')

</body>
</html>
