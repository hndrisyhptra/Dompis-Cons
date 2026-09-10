<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#F8FAFC] text-slate-900">

<div class="min-h-screen max-w-md mx-auto bg-[#F8FAFC] pb-24 shadow-2xl shadow-slate-900/10 border-x border-slate-200/60">

    {{-- HEADER --}}
    <div class="bg-[#1565D8] text-white px-5 pt-6 pb-6 rounded-b-[2rem] shadow-lg shadow-slate-900/10">

        <div class="flex items-center gap-3">

            <a href="{{ route('waspang.dashboard') }}"
               class="w-10 h-10 shrink-0 rounded-2xl bg-white/15 hover:bg-white/25 inline-flex items-center justify-center transition active:scale-90">
                <i class="fa-solid fa-chevron-left text-sm"></i>
            </a>

            <div>
                <h1 class="text-lg font-black tracking-tight">
                    Notifikasi
                </h1>

                <p class="text-[11px] text-blue-100 font-medium">
                    Update approval dan status eviden
                </p>
            </div>

        </div>

    </div>

    @if($notifications->count() > 0)
        <div class="px-4 mt-4">
            <form method="POST"
                action="{{ route('waspang.notifications.clear') }}"
                onsubmit="return confirm('Bersihkan Semua Notifikasi?')">
                @csrf
                @method('DELETE')

                <button class="w-full h-10 rounded-2xl bg-white border border-red-200 text-red-700 text-xs font-bold shadow-xs flex items-center justify-center gap-2 transition hover:bg-red-50">
                    <i class="fa-solid fa-trash-can text-[11px]"></i>
                    Bersihkan Semua Notifikasi
                </button>
            </form>
        </div>
    @endif


    {{-- CONTENT --}}
    <div class="px-4 mt-4 space-y-2.5">

        @forelse($notifications as $notif)

            @php
                $color = match($notif->type) {
                    'reject' => [
                        'border' => 'border-red-200',
                        'dot' => 'bg-red-500',
                        'badge' => 'bg-red-100 text-red-700',
                        'label' => 'Rejected',
                    ],
                    'approved' => [
                        'border' => 'border-blue-200',
                        'dot' => 'bg-[#1565D8]',
                        'badge' => 'bg-blue-100 text-[#0F4FAF]',
                        'label' => 'Approved',
                    ],
                    'new_order' => [
                        'border' => 'border-yellow-200',
                        'dot' => 'bg-yellow-600',
                        'badge' => 'bg-yellow-100 text-yellow-700',
                        'label' => 'New Order',
                    ],
                    'ready_ut' => [
                        'border' => 'border-green-200',
                        'dot' => 'bg-green-600',
                        'badge' => 'bg-green-100 text-green-700',
                        'label' => 'Ready UT',
                    ],
                    default => [
                        'border' => 'border-yellow-200',
                        'dot' => 'bg-yellow-500',
                        'badge' => 'bg-yellow-100 text-yellow-700',
                        'label' => 'Reminder',
                    ],
                };
            @endphp

            <div class="bg-white rounded-2xl border {{ $color['border'] }} p-3.5 shadow-xs">

                <div class="flex items-start gap-3">

                    <div class="w-2.5 h-2.5 rounded-full mt-1.5 shrink-0 {{ $color['dot'] }}"></div>

                    <a href="{{ $notif->redirect_url ?? route('waspang.projects.show', $notif->project_id) }}"
                    class="flex-1 min-w-0 block">

                        <div class="flex items-start justify-between gap-2">

                            <h3 class="text-sm font-bold text-slate-900 leading-tight">
                                {{ $notif->title }}
                            </h3>

                            <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide {{ $color['badge'] }}">
                                {{ $color['label'] }}
                            </span>

                        </div>

                        <p class="mt-1 text-xs leading-relaxed text-slate-600 line-clamp-2">
                            {{ $notif->message }}
                        </p>

                        <p class="mt-2 text-[11px] text-slate-400 flex items-center gap-1">
                            <i class="fa-regular fa-clock"></i>
                            {{ $notif->created_at->diffForHumans() }}
                        </p>

                    </a>

                    <form method="POST"
                        action="{{ route('waspang.notifications.delete', $notif->id_notification) }}">
                        @csrf
                        @method('DELETE')

                        <button class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 text-xs font-black flex items-center justify-center transition">
                            ×
                        </button>
                    </form>

                </div>

            </div>

        @empty

            <div class="bg-white rounded-3xl border border-slate-200 p-8 text-center shadow-xs">
                <div class="mx-auto w-12 h-12 rounded-2xl bg-blue-50 text-blue-400 flex items-center justify-center text-lg mb-3">
                    <i class="fa-solid fa-bell-slash"></i>
                </div>
                <p class="text-sm font-bold text-slate-700">
                    Belum ada notifikasi
                </p>

                <p class="text-xs text-slate-500 mt-1">
                    Update approval eviden akan muncul di sini.
                </p>
            </div>

        @endforelse

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'notif'])

</div>

</body>
</html>