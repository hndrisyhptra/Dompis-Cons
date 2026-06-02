<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem]">

        <div class="flex items-center gap-3">

            <a href="{{ route('waspang.dashboard') }}"
               class="text-3xl leading-none">
                ‹
            </a>

            <div>
                <h1 class="text-xl font-bold">
                    Notifikasi
                </h1>

                <p class="text-xs opacity-90">
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

                <button class="w-full h-10 rounded-2xl bg-white border border-red-200 text-red-700 text-xs font-bold shadow-sm">
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
                        'dot' => 'bg-blue-600',
                        'badge' => 'bg-blue-100 text-blue-700',
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

            <div class="bg-white rounded-2xl border {{ $color['border'] }} p-3 shadow-sm">

                <div class="flex items-start gap-3">

                    <div class="w-2.5 h-2.5 rounded-full mt-1.5 shrink-0 {{ $color['dot'] }}"></div>

                    <a href="{{ $notif->redirect_url ?? route('waspang.projects.show', $notif->project_id) }}"
                    class="flex-1 min-w-0 block">

                        <div class="flex items-start justify-between gap-2">

                            <h3 class="text-sm font-bold text-gray-900 leading-tight">
                                {{ $notif->title }}
                            </h3>

                            <span class="shrink-0 px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $color['badge'] }}">
                                {{ $color['label'] }}
                            </span>

                        </div>

                        <p class="mt-1 text-xs leading-relaxed text-gray-600 line-clamp-2">
                            {{ $notif->message }}
                        </p>

                        <p class="mt-2 text-[11px] text-gray-400">
                            {{ $notif->created_at->diffForHumans() }}
                        </p>

                    </a>

                    <form method="POST"
                        action="{{ route('waspang.notifications.delete', $notif->id_notification) }}">
                        @csrf
                        @method('DELETE')

                        <button class="w-7 h-7 rounded-full bg-gray-100 text-gray-500 text-xs">
                            ×
                        </button>
                    </form>

                </div>

            </div>

        @empty

            <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center">
                <p class="text-sm font-bold text-gray-700">
                    Belum ada notifikasi
                </p>

                <p class="text-xs text-gray-500 mt-1">
                    Update approval eviden akan muncul di sini.
                </p>
            </div>

        @endforelse

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'notif'])

</div>

</body>
</html>