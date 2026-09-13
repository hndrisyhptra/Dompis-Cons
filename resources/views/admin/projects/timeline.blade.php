@extends('layouts.admin')

@section('content')

{{--
    Revisi (permintaan user): Fitur BARU "Timeline" -- terpisah dari
    "Tracking Progress" (resources/views/admin/projects/tracking.blade.php).
    Sengaja dibuat file baru (bukan menambah section ke tracking.blade.php)
    krn layout-nya beda total: di sini ada 2 bagian --
      1. Timeline HORIZONTAL (ringkasan, scroll ke samping) -- semua
         ProjectActivityLog + LopKronologi diurutkan tanggal, mulai dari
         Project dibuat (proxy "upload PID BOQ") s.d. entri terakhir
         (idealnya Golive).
      2. Timeline VERTICAL (detail, accordion per entri) -- deskripsi
         lengkap + eviden foto (kalau ada) + teks update kronologi (kalau
         ada), bisa expand/collapse per baris atau expand/collapse semua.
    Muncul di role: superadmin, admin, tif, super_tif, officer, pm (lihat
    routes/web.php -- group role:superadmin,admin,tif,super_tif,officer,pm
    KHUSUS utk route ini, terpisah dari group tracking() yg TIDAK include
    tif/pm).
--}}

@php
    $stageLabels = [
        'persiapan' => 'Persiapan',
        'instalasi' => 'Instalasi',
        'pengukuran' => 'Pengukuran',
        'finishing' => 'Finishing',
        'fi_ogp_golive' => 'FI-OGP Golive',
        'golive' => 'Golive',
    ];

    // Kelas Tailwind LENGKAP (literal, bukan interpolasi "bg-{{ }}-500")
    // supaya tetap terdeteksi build JIT Tailwind. Key = "warna" logis per
    // kelompok activity_type.
    $colorClasses = [
        'blue' => [
            'dot' => 'bg-blue-500',
            'chip' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800',
            'iconBg' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300',
            'ring' => 'ring-blue-200 dark:ring-blue-800',
        ],
        'emerald' => [
            'dot' => 'bg-emerald-500',
            'chip' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
            'iconBg' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300',
            'ring' => 'ring-emerald-200 dark:ring-emerald-800',
        ],
        'amber' => [
            'dot' => 'bg-amber-500',
            'chip' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
            'iconBg' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-300',
            'ring' => 'ring-amber-200 dark:ring-amber-800',
        ],
        'red' => [
            'dot' => 'bg-red-500',
            'chip' => 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
            'iconBg' => 'bg-red-100 text-red-600 dark:bg-red-900/50 dark:text-red-300',
            'ring' => 'ring-red-200 dark:ring-red-800',
        ],
        'purple' => [
            'dot' => 'bg-purple-500',
            'chip' => 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950 dark:text-purple-300 dark:border-purple-800',
            'iconBg' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-300',
            'ring' => 'ring-purple-200 dark:ring-purple-800',
        ],
        'indigo' => [
            'dot' => 'bg-indigo-500',
            'chip' => 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-800',
            'iconBg' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-300',
            'ring' => 'ring-indigo-200 dark:ring-indigo-800',
        ],
        'gray' => [
            'dot' => 'bg-gray-400',
            'chip' => 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            'iconBg' => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
            'ring' => 'ring-gray-200 dark:ring-gray-700',
        ],
    ];

    $styleFor = function (string $type) {
        return match (true) {
            str_contains($type, 'assign') => ['icon' => '👷', 'color' => 'amber'],
            in_array($type, ['upload_evidence', 'upload_evidence_regular', 'replace_evidence'], true) => ['icon' => '📸', 'color' => 'blue'],
            $type === 'approve_evidence' => ['icon' => '✅', 'color' => 'emerald'],
            $type === 'reject_evidence' => ['icon' => '❌', 'color' => 'red'],
            in_array($type, ['delete_evidence', 'reset_evidence', 'toggle_measurement_na'], true) => ['icon' => '🗑️', 'color' => 'gray'],
            $type === 'update_kendala' => ['icon' => '⚠️', 'color' => 'amber'],
            $type === 'resume_project' => ['icon' => '▶️', 'color' => 'emerald'],
            $type === 'update_kronologi' => ['icon' => '📝', 'color' => 'indigo'],
            in_array($type, ['add_perizinan', 'update_permit_category'], true) => ['icon' => '📄', 'color' => 'indigo'],
            str_starts_with($type, 'survey_') => ['icon' => '🗺️', 'color' => 'purple'],
            $type === 'update_quantity_actual' => ['icon' => '📐', 'color' => 'gray'],
            str_starts_with($type, 'golive_submission') => ['icon' => '📤', 'color' => 'blue'],
            $type === 'golive_verification_upload' => ['icon' => '🔐', 'color' => 'emerald'],
            in_array($type, ['lop_golive', 'lop_stage_advance'], true) => ['icon' => '🚀', 'color' => 'emerald'],
            $type === 'project_completed' => ['icon' => '🎉', 'color' => 'emerald'],
            $type === 'stage_transition' => ['icon' => '➡️', 'color' => 'blue'],
            $type === 'webhook_stage_uploaded_published' => ['icon' => '🌐', 'color' => 'gray'],
            $type === 'project_created' => ['icon' => '🏁', 'color' => 'blue'],
            default => ['icon' => '•', 'color' => 'gray'],
        };
    };

    $events = collect();

    // Titik awal: project dibuat -- proxy "upload PID BOQ" paling awal,
    // supaya timeline selalu punya titik mulai yg jelas walau belum ada
    // ProjectActivityLog sama sekali.
    $events->push([
        'dt' => $project->created_at,
        'style' => $styleFor('project_created'),
        'title' => 'Project Dibuat (PID/BOQ)',
        'desc' => 'PID '.($project->pid ?? '-').' · PID SAP '.($project->pid_sap ?? '-').' diinput ke sistem.',
        'user' => null,
        'stage' => null,
        'photos' => collect(),
    ]);

    foreach ($logs as $log) {
        $events->push([
            'dt' => $log->created_at,
            'style' => $styleFor($log->activity_type),
            'title' => $log->title,
            'desc' => $log->description,
            'user' => $log->user,
            'stage' => $log->stage ? ($stageLabels[$log->stage] ?? $log->stage) : null,
            'photos' => $log->evidence ? collect([$log->evidence]) : collect(),
        ]);
    }

    foreach ($kronologis as $k) {
        $events->push([
            'dt' => $k->created_at ?? $k->event_date,
            'style' => $styleFor('update_kronologi'),
            'title' => 'Update Kronologi'.($k->permitCategory ? ' · '.$k->permitCategory->name : ''),
            'desc' => $k->note,
            'user' => $k->creator,
            'stage' => $k->stage_code ? ($stageLabels[$k->stage_code] ?? $k->stage_code) : null,
            'photos' => $k->evidences,
        ]);
    }

    $events = $events->sortBy('dt')->values();
    $totalEvents = $events->count();
    $totalPhotos = $events->sum(fn ($e) => $e['photos']->count());
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- HEADER --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">Timeline Project</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Kronologi lengkap project dari awal (PID/BOQ) sampai dengan Golive -- {{ $totalEvents }} entri, {{ $totalPhotos }} eviden foto.
                </p>
            </div>
            <a href="{{ url()->previous() }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-700 shrink-0">
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">PID</p>
                <p class="font-black text-gray-900 dark:text-white mt-1">{{ $project->pid ?? '-' }}</p>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">PID SAP</p>
                <p class="font-black text-gray-900 dark:text-white mt-1">{{ $project->pid_sap ?? '-' }}</p>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">Nama Project</p>
                <p class="font-black text-gray-900 dark:text-white mt-1 truncate">{{ $project->project_name ?? '-' }}</p>
            </div>
            <div class="rounded-2xl bg-blue-50 dark:bg-blue-900/20 p-4">
                <p class="text-xs text-blue-700 dark:text-blue-300">Progress</p>
                <p class="font-black text-blue-700 dark:text-blue-300 mt-1">{{ $project->progressSummary()['progress'] ?? 0 }}%</p>
            </div>
        </div>
    </div>

    {{--
        DURASI PER TAHAP (permintaan user) -- berapa lama LOP ini
        "menginap" di tiap staging (Persiapan s.d. Golive), dihitung dari
        lop_stage_histories (diisi otomatis lewat Lop::advanceStage()
        setiap kali status_progress berubah -- lihat Section BE
        ANALISA_REFACTOR_PERSIAPAN.md). $stageDurations dikirim dari
        DashboardController::timeline() -> buildStageDurations(), SATU
        baris per tahap alur normal (project_stages::sequential()), tahap
        yang belum dicapai LOP ini tetap ditampilkan (durasi '-').

        CATATAN: histori baru mulai dicatat sejak fitur ini aktif -- utk
        LOP yang sudah lama & sempat melewati tahap2 SEBELUM fitur ini ada,
        durasi tahap yang SUDAH dilewati sebelum tanggal aktif bisa kosong
        (tidak ada data lama utk direkonstruksi), tapi tahap yang SEDANG
        berjalan & seluruh transisi SETELAH fitur ini aktif akan tercatat
        lengkap.
    --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-base font-black text-gray-900 dark:text-white">Durasi per Tahap</h2>
                <p class="text-xs text-gray-400 mt-0.5">Berapa lama LOP ini berada di tiap staging, dari masuk s.d. selesai (atau s.d. sekarang kalau masih berjalan)</p>
            </div>
        </div>

        @php
            $formatStageDuration = function (?int $seconds) {
                if ($seconds === null) {
                    return '-';
                }

                $days = intdiv($seconds, 86400);
                $hours = intdiv($seconds % 86400, 3600);

                if ($days > 0) {
                    return $days.' hari '.$hours.' jam';
                }

                $minutes = intdiv($seconds % 3600, 60);

                if ($hours > 0) {
                    return $hours.' jam '.$minutes.' menit';
                }

                return $minutes.' menit';
            };
        @endphp

        @if(! $project->lop)
            <p class="text-sm text-gray-400 py-6 text-center">LOP belum ada untuk project ini.</p>
        @else
            <div class="overflow-x-auto -mx-2 px-2">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-bold text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-800">
                            <th class="py-2 pr-3">Tahap</th>
                            <th class="py-2 px-3">Masuk</th>
                            <th class="py-2 px-3">Selesai</th>
                            <th class="py-2 px-3">Durasi</th>
                            <th class="py-2 pl-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stageDurations as $sd)
                            <tr class="border-b border-gray-50 dark:border-gray-800/60 {{ $sd['is_current'] ? 'bg-blue-50/50 dark:bg-blue-950/20' : '' }}">
                                <td class="py-2.5 pr-3 font-bold text-gray-700 dark:text-gray-200">{{ $sd['label'] }}</td>
                                <td class="py-2.5 px-3 text-gray-500">{{ $sd['entered_at'] ? $sd['entered_at']->format('d M Y H:i') : '-' }}</td>
                                <td class="py-2.5 px-3 text-gray-500">{{ $sd['completed_at'] ? $sd['completed_at']->format('d M Y H:i') : ($sd['is_current'] ? 'Masih berjalan' : '-') }}</td>
                                <td class="py-2.5 px-3 font-semibold text-gray-700 dark:text-gray-200">{{ $formatStageDuration($sd['duration_seconds']) }}</td>
                                <td class="py-2.5 pl-3 text-right">
                                    @if($sd['is_current'])
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">Sedang Berjalan</span>
                                    @elseif($sd['visits'] > 0)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">Selesai</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">Belum Dimulai</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- TIMELINE HORIZONTAL (RINGKASAN) --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-base font-black text-gray-900 dark:text-white">Ringkasan Kronologi</h2>
                <p class="text-xs text-gray-400 mt-0.5">Geser ke samping · klik titik untuk lompat ke detail di bawah</p>
            </div>
        </div>

        @if($events->isEmpty())
            <p class="text-sm text-gray-400 py-6 text-center">Belum ada aktivitas tercatat.</p>
        @else
            <div class="overflow-x-auto pb-3 -mx-2 px-2">
                <div class="relative flex items-start" style="min-width: {{ max($events->count() * 168, 100) }}px;">
                    <div class="absolute left-0 right-0 top-5 h-0.5 bg-gray-200 dark:bg-gray-700"></div>
                    @foreach($events as $i => $e)
                        <button type="button"
                                onclick="jumpToEvent({{ $i }})"
                                class="relative z-10 flex flex-col items-center text-center w-[168px] shrink-0 px-2 group">
                            <span class="w-4 h-4 rounded-full {{ $e['style']['color'] ? $colorClasses[$e['style']['color']]['dot'] : $colorClasses['gray']['dot'] }} border-2 border-white dark:border-gray-900 shadow group-hover:scale-125 transition-transform"></span>
                            <span class="mt-2 text-[10px] font-bold text-gray-400">{{ optional($e['dt'])->format('d M Y') }}</span>
                            <span class="text-[10px] text-gray-400">{{ optional($e['dt'])->format('H:i') }}</span>
                            <span class="mt-1 text-xs font-bold text-gray-700 dark:text-gray-200 leading-snug line-clamp-2 group-hover:text-blue-600">
                                {{ $e['style']['icon'] }} {{ $e['title'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- TIMELINE VERTICAL (DETAIL + EVIDEN FOTO) --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h2 class="text-base font-black text-gray-900 dark:text-white">Detail Kronologi &amp; Eviden</h2>
                <p class="text-xs text-gray-400 mt-0.5">Klik satu entri untuk lihat detail lengkap &amp; foto eviden</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="toggleAllEvents(true)" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">Buka Semua</button>
                <button type="button" onclick="toggleAllEvents(false)" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">Tutup Semua</button>
            </div>
        </div>

        @if($events->isEmpty())
            <p class="text-sm text-gray-400 py-6 text-center">Belum ada aktivitas tercatat.</p>
        @else
            <div class="relative pl-8">
                <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                @foreach($events as $i => $e)
                    @php $cls = $colorClasses[$e['style']['color']] ?? $colorClasses['gray']; @endphp
                    <div id="event-{{ $i }}" class="relative pb-6 last:pb-0">
                        <span class="absolute -left-8 top-1.5 w-6 h-6 rounded-full {{ $cls['iconBg'] }} flex items-center justify-center text-xs ring-4 ring-white dark:ring-gray-900">
                            {{ $e['style']['icon'] }}
                        </span>

                        <button type="button"
                                onclick="toggleEvent({{ $i }})"
                                class="w-full text-left bg-gray-50 dark:bg-gray-800/60 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-2xl px-4 py-3 transition flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-xs font-bold text-gray-400">{{ optional($e['dt'])->format('d M Y · H:i') }} WIB</span>
                                    @if($e['stage'])
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $cls['chip'] }}">{{ $e['stage'] }}</span>
                                    @endif
                                    @if($e['photos']->count() > 0)
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">🖼️ {{ $e['photos']->count() }} foto</span>
                                    @endif
                                </div>
                                <p class="text-sm font-black text-gray-900 dark:text-white truncate">{{ $e['title'] }}</p>
                                @if($e['user'])
                                    <p class="text-xs text-gray-400 mt-0.5">oleh {{ $e['user']->name ?? '-' }}</p>
                                @endif
                            </div>
                            <svg data-chevron="{{ $i }}" class="w-4 h-4 text-gray-400 shrink-0 mt-1 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="event-body-{{ $i }}" class="hidden mt-2 px-4 py-4 rounded-2xl border border-gray-100 dark:border-gray-800">
                            @if($e['desc'])
                                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $e['desc'] }}</p>
                            @else
                                <p class="text-sm text-gray-400 italic">Tidak ada keterangan tambahan.</p>
                            @endif

                            @if($e['photos']->count() > 0)
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mt-4">
                                    @foreach($e['photos'] as $photo)
                                        @if($photo->file_path)
                                            <a href="{{ Storage::url($photo->file_path) }}" target="_blank" class="group block rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                                @php
                                                    $ext = strtolower(pathinfo($photo->file_path, PATHINFO_EXTENSION));
                                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                                @endphp
                                                @if($isImage)
                                                    <img src="{{ Storage::url($photo->file_path) }}" loading="lazy" class="w-full h-28 object-cover group-hover:scale-105 transition-transform duration-200">
                                                @else
                                                    <div class="w-full h-28 flex items-center justify-center text-3xl">📄</div>
                                                @endif
                                                <div class="px-2 py-1.5">
                                                    <p class="text-[10px] font-bold text-gray-600 dark:text-gray-300 truncate">{{ $photo->evidence_type ?? ($photo->stage ?? 'Eviden') }}</p>
                                                    @if($photo->status)
                                                        <span class="text-[9px] font-bold {{ $photo->status === 'approved' ? 'text-emerald-600' : ($photo->status === 'rejected' ? 'text-red-600' : 'text-amber-600') }}">{{ strtoupper($photo->status) }}</span>
                                                    @endif
                                                </div>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    function toggleEvent(i) {
        const body = document.getElementById('event-body-' + i);
        const chevron = document.querySelector('[data-chevron="' + i + '"]');
        if (!body) return;
        body.classList.toggle('hidden');
        if (chevron) {
            chevron.classList.toggle('rotate-180', !body.classList.contains('hidden'));
        }
    }

    function toggleAllEvents(open) {
        document.querySelectorAll('[id^="event-body-"]').forEach(function (body) {
            body.classList.toggle('hidden', !open);
        });
        document.querySelectorAll('[data-chevron]').forEach(function (chevron) {
            chevron.classList.toggle('rotate-180', open);
        });
    }

    function jumpToEvent(i) {
        const body = document.getElementById('event-body-' + i);
        const target = document.getElementById('event-' + i);
        if (body && body.classList.contains('hidden')) {
            toggleEvent(i);
        }
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('ring-2', 'ring-blue-400', 'rounded-2xl');
            setTimeout(function () {
                target.classList.remove('ring-2', 'ring-blue-400', 'rounded-2xl');
            }, 1500);
        }
    }
</script>
@endpush

@endsection
