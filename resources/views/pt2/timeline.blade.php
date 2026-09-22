@extends('layouts.admin')

@section('content')
@php
    $project = $lop->project;
    $totalPhotos = $events->sum(fn ($event) => $event['photos']->count());

    $colors = [
        'blue' => [
            'dot' => 'bg-blue-500',
            'icon' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
            'chip' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800',
        ],
        'emerald' => [
            'dot' => 'bg-emerald-500',
            'icon' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
            'chip' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
        ],
        'amber' => [
            'dot' => 'bg-amber-500',
            'icon' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
            'chip' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
        ],
        'red' => [
            'dot' => 'bg-red-500',
            'icon' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
            'chip' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
        ],
        'purple' => [
            'dot' => 'bg-purple-500',
            'icon' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
            'chip' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950 dark:text-purple-300 dark:border-purple-800',
        ],
        'gray' => [
            'dot' => 'bg-gray-400',
            'icon' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
            'chip' => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
        ],
    ];

    $styleFor = function (string $type) {
        return match (true) {
            str_contains($type, 'golive') => ['icon' => '🚀', 'color' => 'emerald'],
            str_contains($type, 'approve') => ['icon' => '✅', 'color' => 'emerald'],
            str_contains($type, 'reject') => ['icon' => '❌', 'color' => 'red'],
            str_contains($type, 'assign') => ['icon' => '👷', 'color' => 'amber'],
            str_contains($type, 'send_to_sdi') => ['icon' => '📤', 'color' => 'blue'],
            str_contains($type, 'survey') => ['icon' => '🗺️', 'color' => 'purple'],
            str_contains($type, 'evidence') => ['icon' => '📸', 'color' => 'blue'],
            str_contains($type, 'dismantle') => ['icon' => '🧰', 'color' => 'amber'],
            str_contains($type, 'mancore') => ['icon' => '🔌', 'color' => 'purple'],
            str_contains($type, 'created') => ['icon' => '🏁', 'color' => 'blue'],
            default => ['icon' => '•', 'color' => 'gray'],
        };
    };

    $formatDuration = function (?int $seconds) {
        if ($seconds === null) {
            return '-';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return $days.' hari '.$hours.' jam';
        }

        return $hours > 0 ? $hours.' jam '.$minutes.' menit' : $minutes.' menit';
    };
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <section class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="inline-flex px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 text-[10px] font-black uppercase tracking-wider">Project PT 2</span>
                    <span class="inline-flex px-2.5 py-1 rounded-full {{ $summary['badge'] ?? 'bg-gray-100 text-gray-600' }} text-[10px] font-black">{{ $summary['stageLabel'] ?? '-' }}</span>
                </div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">Timeline Project PT 2</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Kronologi {{ $lop->lop_name ?? '-' }} dari input PID/BOQ sampai Golive · {{ $events->count() }} aktivitas · {{ $totalPhotos }} eviden.
                </p>
            </div>
            <a href="{{ url()->previous() }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-700 shrink-0">
                ← Kembali
            </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mt-6">
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">PID</p>
                <p class="font-black text-gray-900 dark:text-white mt-1 break-words">{{ $project->pid ?? '-' }}</p>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">PID SAP</p>
                <p class="font-black text-gray-900 dark:text-white mt-1 break-words">{{ $project->pid_sap ?? $lop->pid_sap ?? '-' }}</p>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4 col-span-2 lg:col-span-1">
                <p class="text-xs text-gray-500">LOP</p>
                <p class="font-black text-gray-900 dark:text-white mt-1 break-words">{{ $lop->lop_name ?? '-' }}</p>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">Branch / STO</p>
                <p class="font-black text-gray-900 dark:text-white mt-1">{{ $lop->branch ?: ($project->branch ?? '-') }} / {{ $lop->sto ?? '-' }}</p>
            </div>
            <div class="rounded-2xl bg-blue-50 dark:bg-blue-900/20 p-4">
                <p class="text-xs text-blue-700 dark:text-blue-300">Progress</p>
                <p class="font-black text-blue-700 dark:text-blue-300 mt-1">{{ $summary['progress'] ?? 0 }}%</p>
            </div>
        </div>
    </section>

    <section class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 sm:p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-base font-black text-gray-900 dark:text-white">Durasi Antar-Milestone</h2>
            <p class="text-xs text-gray-400 mt-0.5">Estimasi dari timestamp data PT 2 yang tersedia. Tahap tanpa timestamp historis ditampilkan “-”.</p>
        </div>

        <div class="overflow-x-auto -mx-2 px-2">
            <table class="w-full min-w-[760px] text-sm">
                <thead>
                    <tr class="text-left text-xs font-bold text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-800">
                        <th class="py-2 pr-3">Tahap</th>
                        <th class="py-2 px-3">Mulai</th>
                        <th class="py-2 px-3">Milestone Berikutnya</th>
                        <th class="py-2 px-3">Estimasi Durasi</th>
                        <th class="py-2 pl-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stageDurations as $stage)
                        <tr class="border-b border-gray-50 dark:border-gray-800/60 {{ $stage['is_current'] ? 'bg-blue-50/60 dark:bg-blue-950/20' : '' }}">
                            <td class="py-3 pr-3 font-bold text-gray-700 dark:text-gray-200">{{ $stage['label'] }}</td>
                            <td class="py-3 px-3 text-gray-500">{{ $stage['entered_at']?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="py-3 px-3 text-gray-500">{{ $stage['completed_at']?->format('d M Y H:i') ?? ($stage['is_current'] ? 'Masih berjalan' : '-') }}</td>
                            <td class="py-3 px-3 font-semibold text-gray-700 dark:text-gray-200">{{ $formatDuration($stage['duration_seconds']) }}</td>
                            <td class="py-3 pl-3 text-right">
                                @if($stage['is_current'])
                                    <span class="inline-flex px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 text-[11px] font-bold">Sedang Berjalan</span>
                                @elseif($stage['visited'])
                                    <span class="inline-flex px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 text-[11px] font-bold">Tercapai</span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 text-[11px] font-bold">Belum Dimulai</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 sm:p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-base font-black text-gray-900 dark:text-white">Ringkasan Kronologi</h2>
            <p class="text-xs text-gray-400 mt-0.5">Geser ke samping dan klik titik untuk membuka aktivitas.</p>
        </div>

        <div class="overflow-x-auto pb-3 -mx-2 px-2">
            <div class="relative flex items-start" style="min-width: {{ max($events->count() * 168, 168) }}px;">
                <div class="absolute left-0 right-0 top-5 h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                @foreach($events as $index => $event)
                    @php
                        $eventStyle = $styleFor($event['type']);
                    @endphp
                    <button type="button" onclick="jumpToPt2Event({{ $index }})"
                            class="relative z-10 flex flex-col items-center text-center w-[168px] shrink-0 px-2 group">
                        <span class="w-4 h-4 rounded-full {{ $colors[$eventStyle['color']]['dot'] }} border-2 border-white dark:border-gray-900 shadow group-hover:scale-125 transition-transform"></span>
                        <span class="mt-2 text-[10px] font-bold text-gray-400">{{ $event['dt']->format('d M Y') }}</span>
                        <span class="text-[10px] text-gray-400">{{ $event['dt']->format('H:i') }}</span>
                        <span class="mt-1 text-xs font-bold text-gray-700 dark:text-gray-200 leading-snug line-clamp-2 group-hover:text-blue-600">
                            {{ $eventStyle['icon'] }} {{ $event['title'] }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h2 class="text-base font-black text-gray-900 dark:text-white">Detail Kronologi &amp; Eviden</h2>
                <p class="text-xs text-gray-400 mt-0.5">Klik aktivitas untuk melihat keterangan dan file eviden.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="toggleAllPt2Events(true)" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">Buka Semua</button>
                <button type="button" onclick="toggleAllPt2Events(false)" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">Tutup Semua</button>
            </div>
        </div>

        <div class="relative pl-8">
            <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

            @foreach($events as $index => $event)
                @php
                    $eventStyle = $styleFor($event['type']);
                @endphp
                <article id="pt2-event-{{ $index }}" class="relative pb-6 last:pb-0">
                    <span class="absolute -left-8 top-1.5 w-6 h-6 rounded-full {{ $colors[$eventStyle['color']]['icon'] ?? $colors['gray']['icon'] }} flex items-center justify-center text-xs ring-4 ring-white dark:ring-gray-900">{{ $eventStyle['icon'] }}</span>

                    <button type="button" onclick="togglePt2Event({{ $index }})"
                            class="w-full text-left bg-gray-50 dark:bg-gray-800/60 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-2xl px-4 py-3 transition flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-xs font-bold text-gray-400">{{ $event['dt']->format('d M Y · H:i') }} WIB</span>
                                @if($event['stage'])
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $colors[$eventStyle['color']]['chip'] ?? $colors['gray']['chip'] }}">{{ $event['stage'] }}</span>
                                @endif
                                @if($event['photos']->isNotEmpty())
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">🖼️ {{ $event['photos']->count() }} file</span>
                                @endif
                            </div>
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ $event['title'] }}</p>
                            @if($event['user'])
                                <p class="text-xs text-gray-400 mt-0.5">oleh {{ $event['user']->name ?? '-' }}</p>
                            @endif
                        </div>
                        <svg data-pt2-chevron="{{ $index }}" class="w-4 h-4 text-gray-400 shrink-0 mt-1 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div id="pt2-event-body-{{ $index }}" class="hidden mt-2 px-4 py-4 rounded-2xl border border-gray-100 dark:border-gray-800">
                        <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $event['desc'] ?: 'Tidak ada keterangan tambahan.' }}</p>

                        @if($event['photos']->isNotEmpty())
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mt-4">
                                @foreach($event['photos'] as $photo)
                                    @if($photo['path'])
                                        @php
                                            $extension = strtolower(pathinfo($photo['path'], PATHINFO_EXTENSION));
                                            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                        @endphp
                                        <a href="{{ Storage::url($photo['path']) }}" target="_blank" rel="noopener"
                                           class="group block rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                            @if($isImage)
                                                <img src="{{ Storage::url($photo['path']) }}" loading="lazy" alt="{{ $photo['label'] }}" class="w-full h-28 object-cover group-hover:scale-105 transition-transform duration-200">
                                            @else
                                                <div class="w-full h-28 flex items-center justify-center text-3xl">📄</div>
                                            @endif
                                            <div class="px-2 py-1.5">
                                                <p class="text-[10px] font-bold text-gray-600 dark:text-gray-300 truncate">{{ $photo['label'] }}</p>
                                                @if($photo['status'])
                                                    <span class="text-[9px] font-bold {{ $photo['status'] === 'approved' ? 'text-emerald-600' : ($photo['status'] === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ strtoupper($photo['status']) }}</span>
                                                @endif
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>

<script>
    function togglePt2Event(index) {
        const body = document.getElementById('pt2-event-body-' + index);
        const chevron = document.querySelector('[data-pt2-chevron="' + index + '"]');
        if (!body) return;
        body.classList.toggle('hidden');
        if (chevron) chevron.classList.toggle('rotate-180', !body.classList.contains('hidden'));
    }

    function toggleAllPt2Events(open) {
        document.querySelectorAll('[id^="pt2-event-body-"]').forEach(function (body) {
            body.classList.toggle('hidden', !open);
        });
        document.querySelectorAll('[data-pt2-chevron]').forEach(function (chevron) {
            chevron.classList.toggle('rotate-180', open);
        });
    }

    function jumpToPt2Event(index) {
        const body = document.getElementById('pt2-event-body-' + index);
        const target = document.getElementById('pt2-event-' + index);
        if (body && body.classList.contains('hidden')) togglePt2Event(index);
        if (!target) return;
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('ring-2', 'ring-blue-400', 'rounded-2xl');
        setTimeout(function () {
            target.classList.remove('ring-2', 'ring-blue-400', 'rounded-2xl');
        }, 1500);
    }
</script>
@endsection
