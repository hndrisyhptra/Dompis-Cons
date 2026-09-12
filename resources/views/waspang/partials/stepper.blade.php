@php
    // Revisi stepper (6-tahap, sebelumnya 5): "Persiapan Instalasi"
    // (sequence 6) yg tadinya cuma pass-through di dalam Step 1 Persiapan,
    // sekarang jadi HALAMAN SENDIRI (Step 2) dgn 2 kartu Barang Tiba/
    // Perizinan -- persis pola Step 1 Persiapan sebelum refactor 5 sub-step
    // Stage 4d (lihat WaspangController::persiapanInstalasi()). Stepper
    // tetap membaca POSISI NYATA LOP dari Project::progressSummary()
    // (project_stages 11-tahap), bukan dihitung ulang dari evidence lama.
    //
    // 6 kolom & RANGE sequence yg diwakili:
    //   1. Persiapan            -> sequence 1-5  (inisiasi..material_delivery, 5 sub-step)
    //   2. Persiapan Instalasi  -> sequence 6    (halaman sendiri, Barang Tiba/Perizinan)
    //   3. Instalasi            -> sequence 7
    //   4. Pengukuran           -> sequence 8
    //   5. Finishing            -> sequence 9
    //   6. Selesai              -> sequence 10-11 (FI-OGP Golive/Golive) -- belum ada
    //      halaman waspang sendiri, jadi cuma indikator status (tidak bisa diklik).
    $summary = $project->progressSummary();
    $seq = $summary['effectiveStageSequence'];

    $rejectedByStage = fn (string $stage) => ($project->evidences ?? collect())
        ->where('stage', $stage)->where('status', 'rejected')->isNotEmpty();

    $step1Rejected = $rejectedByStage('persiapan') && ($seq === null || $seq <= 5);
    $step2Rejected = $rejectedByStage('persiapan') && ($seq === null || $seq === 6);
    $step3Rejected = $rejectedByStage('instalasi');
    $step4Rejected = $rejectedByStage('pengukuran');
    $step5Rejected = $rejectedByStage('finishing');

    // "Selesai" (checkmark hijau) & "terbuka" (boleh diklik) murni dari
    // posisi sequence -- fallback ke boolean lama HANYA kalau LOP/kode
    // stage tak dikenal (edge-case, lihat Project::progressSummary()).
    $step1Done = $seq !== null ? $seq > 5 : false;
    $step2Done = $seq !== null ? $seq > 6 : ($summary['persiapanDone'] ?? false);
    $step3Done = $seq !== null ? $seq > 7 : ($summary['instalasiDone'] ?? false);
    $step4Done = $seq !== null ? $seq > 8 : ($summary['pengukuranDone'] ?? false);
    $step5Done = $seq !== null ? $seq > 9 : ($summary['finishingDone'] ?? false);

    // Section AD (revisi): checklist stepper 2 warna. HIJAU ($stepNDone di
    // atas) tetap murni posisi sequence/approval admin -- TIDAK diubah,
    // supaya status_progress LOP & gate lanjut-step tetap seperti sekarang.
    // KUNING ($stepNUploaded) = Waspang sudah selesai upload SEMUA foto
    // wajib tahap itu (status eviden apapun -- pending atau approved),
    // indikator visual "progres upload saya" independen dari approval.
    $uploadFlags = $project->stepUploadFlags();
    $step1Uploaded = ! $step1Done && ($uploadFlags['persiapanUploaded'] ?? false);
    $step2Uploaded = ! $step2Done && ($uploadFlags['persiapanInstalasiUploaded'] ?? false);
    $step3Uploaded = ! $step3Done && ($uploadFlags['instalasiUploaded'] ?? false);
    $step4Uploaded = ! $step4Done && ($uploadFlags['pengukuranUploaded'] ?? false);
    $step5Uploaded = ! $step5Done && ($uploadFlags['finishingUploaded'] ?? false);

    $route = Route::currentRouteName();
    // FIX (Stage 4d): 'waspang.projects.show' cuma dipakai utk redirect ke
    // 'waspang.projects.persiapan' (lihat WaspangController::show()) -- route
    // itu SENDIRI tidak pernah jadi currentRouteName() saat halaman Persiapan
    // benar2 tampil, jadi $isStep1 sebelumnya SELALU false (bug laten,
    // stepper tidak pernah highlight Step 1 aktif). Tambahkan
    // 'waspang.projects.persiapan' sebagai match yg valid.
    $isStep1 = in_array($route, ['waspang.projects.show', 'waspang.projects.persiapan'], true);
    $isStep2 = $route === 'waspang.projects.persiapan-instalasi';
    $isStep3 = $route === 'waspang.projects.instalasi';
    $isStep4 = $route === 'waspang.projects.pengukuran';
    $isStep5 = $route === 'waspang.projects.finishing';

    $step2Open = $isStep2 || $step1Done || ($seq !== null && $seq >= 6);
    $step3Open = $isStep3 || $step2Done || ($seq !== null && $seq >= 7);
    $step4Open = $isStep4 || $step3Done || ($seq !== null && $seq >= 8);
    $step5Open = $isStep5 || $step4Done || ($seq !== null && $seq >= 9);

    $backUrl = route('waspang.inbox');
    $title = 'Step 1 · Persiapan';

    if ($isStep1) {
        $backUrl = route('waspang.inbox');
        $title = 'Step 1 · Persiapan';
    } elseif ($isStep2) {
        $backUrl = route('waspang.projects.show', $project->id_project);
        $title = 'Step 2 · Persiapan Instalasi';
    } elseif ($isStep3) {
        $backUrl = route('waspang.projects.persiapan-instalasi', $project->id_project);
        $title = 'Step 3 · Instalasi';
    } elseif ($isStep4) {
        $backUrl = route('waspang.projects.instalasi', $project->id_project);
        $title = 'Step 4 · Pengukuran';
    } elseif ($isStep5) {
        $backUrl = route('waspang.projects.pengukuran', $project->id_project);
        $title = 'Step 5 · Finishing';
    }

    // Titik warna kecil pada chip tahap -- daftar KELAS STATIS (bukan
    // interpolasi "bg-{$color}-400") supaya tetap ke-scan & ke-compile oleh
    // Tailwind JIT. Samakan dgn nilai `project_stages.color` yg diseed.
    $stageDotClass = match ($summary['effectiveStageColor'] ?? null) {
        'slate' => 'bg-slate-300',
        'amber' => 'bg-amber-300',
        'blue' => 'bg-blue-300',
        'indigo' => 'bg-blue-300',
        'emerald' => 'bg-emerald-300',
        'purple' => 'bg-purple-300',
        'green' => 'bg-green-300',
        'orange' => 'bg-orange-300',
        'red' => 'bg-red-300',
        default => 'bg-white',
    };

    $segments = [
        [
            'number' => 1,
            'label' => 'Persiapan',
            'href' => route('waspang.projects.show', $project->id_project),
            'open' => true,
            'done' => $step1Done,
            'uploaded' => $step1Uploaded,
            'active' => $isStep1,
            'rejected' => $step1Rejected,
        ],
        [
            'number' => 2,
            'label' => 'Persiapan Instalasi',
            'href' => route('waspang.projects.persiapan-instalasi', $project->id_project),
            'open' => $step2Open,
            'done' => $step2Done,
            'uploaded' => $step2Uploaded,
            'active' => $isStep2,
            'rejected' => $step2Rejected,
        ],
        [
            'number' => 3,
            'label' => 'Instalasi',
            'href' => route('waspang.projects.instalasi', $project->id_project),
            'open' => $step3Open,
            'done' => $step3Done,
            'uploaded' => $step3Uploaded,
            'active' => $isStep3,
            'rejected' => $step3Rejected,
        ],
        [
            'number' => 4,
            'label' => 'Pengukuran',
            'href' => route('waspang.projects.pengukuran', $project->id_project),
            'open' => $step4Open,
            'done' => $step4Done,
            'uploaded' => $step4Uploaded,
            'active' => $isStep4,
            'rejected' => $step4Rejected,
        ],
        [
            'number' => 5,
            'label' => 'Finishing',
            'href' => route('waspang.projects.finishing', $project->id_project),
            'open' => $step5Open,
            'done' => $step5Done,
            'uploaded' => $step5Uploaded,
            'active' => $isStep5,
            'rejected' => $step5Rejected,
        ],
    ];
@endphp

<div class="bg-[#1565D8] text-white px-5 pt-6 pb-6 rounded-b-[2rem] shadow-lg shadow-slate-900/10">

    {{-- HEADER KEMBALI & JUDUL --}}
    <div class="flex items-center gap-3">
        <a href="{{ $backUrl }}"
            class="w-10 h-10 shrink-0 rounded-2xl bg-white/15 hover:bg-white/25 inline-flex items-center justify-center transition active:scale-90">
            <i class="fa-solid fa-chevron-left text-sm"></i>
        </a>
        <div class="min-w-0">
            <h1 class="text-lg font-black tracking-tight truncate">{{ $title }}</h1>
            <p class="text-[11px] text-blue-100 font-medium truncate">{{ $project->project_name }}</p>
        </div>
    </div>

    {{-- CHIP POSISI NYATA LOP (project_stages) --}}
    <div class="mt-3.5 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 bg-white/15 border border-white/20 backdrop-blur-sm rounded-full pl-2 pr-3 py-1 text-[11px] font-bold">
            <span class="w-1.5 h-1.5 rounded-full {{ $stageDotClass }}"></span>
            Posisi: {{ $summary['effectiveStageLabel'] ?? '-' }}
        </span>

        @if($summary['isHold'] ?? false)
            <span class="inline-flex items-center gap-1.5 bg-amber-400/90 text-amber-950 rounded-full px-3 py-1 text-[11px] font-black">
                <i class="fa-solid fa-pause"></i> LOP di-HOLD
            </span>
        @elseif($summary['isDrop'] ?? false)
            <span class="inline-flex items-center gap-1.5 bg-red-500/90 text-white rounded-full px-3 py-1 text-[11px] font-black">
                <i class="fa-solid fa-ban"></i> LOP di-DROP
            </span>
        @endif
    </div>

    {{-- STEPPER UTAMA: 1-6 (Persiapan / Persiapan Instalasi / Instalasi / Pengukuran / Finishing / Selesai) --}}
    <div class="relative mt-5 px-1">
        {{-- garis penghubung --}}
        <div class="absolute top-4 left-4 right-4 h-0.5 bg-white/25 rounded-full"></div>

        <div class="relative grid grid-cols-5 text-center gap-0.5">
            @foreach($segments as $seg)
                @php
                    // Aturan warna sesuai spesifikasi:
                    // - Aktif   -> circle biru & text biru, background putih
                    // - Selesai -> icon check hijau
                    // - Belum aktif -> abu-abu
                    // - Ditolak (state tambahan, di luar 3 aturan di atas) -> merah
                    $circleClass = match(true) {
                        $seg['rejected'] => 'bg-white text-red-600 ring-2 ring-red-500',
                        $seg['active'] => 'bg-white text-[#1565D8] ring-2 ring-[#1565D8]',
                        $seg['done'] => 'bg-white text-emerald-600 ring-2 ring-emerald-500',
                        // KUNING: sudah upload lengkap, masih menunggu admin approve.
                        $seg['uploaded'] ?? false => 'bg-white text-amber-500 ring-2 ring-amber-400',
                        default => 'bg-slate-100 text-slate-400',
                    };
                    $labelClass = match(true) {
                        $seg['rejected'] => 'text-red-100',
                        $seg['active'], $seg['done'] => 'text-white',
                        ($seg['uploaded'] ?? false) => 'text-amber-100',
                        default => 'text-blue-200/70',
                    };
                @endphp

                @if($seg['open'] && $seg['href'])
                    <a href="{{ $seg['href'] }}" class="z-10 flex flex-col items-center gap-1 transition active:scale-95">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-black shadow-sm {{ $circleClass }}">
                            @if($seg['rejected'])
                                <i class="fa-solid fa-exclamation"></i>
                            @elseif($seg['done'] || ($seg['uploaded'] ?? false))
                                <i class="fa-solid fa-check"></i>
                            @else
                                {{ $seg['number'] }}
                            @endif
                        </div>
                        <p class="text-[9px] font-bold leading-tight {{ $labelClass }}">{{ $seg['label'] }}</p>
                    </a>
                @else
                    <div class="z-10 flex flex-col items-center gap-1 {{ $seg['open'] ? '' : 'opacity-90' }}">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-black shadow-sm {{ $circleClass }}">
                            @if($seg['done'] || ($seg['uploaded'] ?? false))
                                <i class="fa-solid fa-check"></i>
                            @else
                                {{ $seg['number'] }}
                            @endif
                        </div>
                        <p class="text-[9px] font-bold leading-tight {{ $labelClass }}">{{ $seg['label'] }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
