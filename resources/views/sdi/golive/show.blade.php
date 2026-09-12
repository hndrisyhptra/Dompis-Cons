@extends('layouts.sdi')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">

    @php
        // Revisi (permintaan user): tiap kategori dokumen sekarang boleh
        // MULTIPLE file -- SDI perlu melihat SEMUA file per kategori
        // (bukan cuma 1) sebelum verifikasi capture UIM.
        $submission = $lop->goliveSubmission;
        $complete = $submission?->isComplete() ?? false;
        $docs = [
            'Capture Valins' => $submission?->captureValinsFiles() ?? [],
            'PDF ABD & Valid4' => $submission?->abdValid4Files() ?? [],
            'File KML' => $submission?->kmlFiles() ?? [],
            'Mancore' => $submission?->mancoreFiles() ?? [],
        ];
    @endphp

    <a href="{{ route('sdi.golive.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-gray-500 hover:text-gray-700 mb-4">
        ← Kembali ke Daftar
    </a>

    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm mb-4">
        <h1 class="text-lg font-black text-gray-900 dark:text-white">{{ $lop->lop_name }}</h1>
        <p class="text-xs text-gray-500 mt-1">{{ $lop->sto }} · {{ $lop->id_ihld }}</p>
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm mb-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-black text-gray-800 dark:text-gray-200">Dokumen FI-OGP Golive (dari Admin)</h2>
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $complete ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $complete ? 'Lengkap' : 'Belum Lengkap' }}
            </span>
        </div>
        <ul class="space-y-2">
            @foreach($docs as $label => $paths)
                <li class="text-sm border-b border-gray-50 dark:border-gray-800 pb-2 last:border-0 last:pb-0">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ count($paths) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400' }}">
                            {{ count($paths) }} file
                        </span>
                    </div>
                    @if(count($paths) > 0)
                        <div class="mt-1.5 flex flex-wrap gap-2">
                            @foreach($paths as $i => $path)
                                <a href="{{ Storage::url($path) }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">File {{ $i + 1 }} ↗</a>
                            @endforeach
                        </div>
                    @else
                        <span class="text-xs text-gray-400">Belum ada</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <h2 class="text-sm font-black text-gray-800 dark:text-gray-200 mb-3">Verifikasi Capture UIM</h2>

        @if($lop->goliveVerification?->capture_uim_path)
            <p class="text-xs text-gray-500 mb-3">
                Sudah diverifikasi pada {{ optional($lop->goliveVerification->verified_at)->format('d M Y H:i') }}.
            </p>
            <a href="{{ Storage::url($lop->goliveVerification->capture_uim_path) }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">Lihat Capture UIM ↗</a>
        @elseif(! $complete)
            <p class="text-xs text-gray-500">
                Dokumen FI-OGP Golive dari Admin belum lengkap. Verifikasi belum bisa dilakukan.
            </p>
        @else
            <form method="POST" action="{{ route('sdi.golive.verify', $lop->id_lop) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">Upload Capture UIM</label>
                    <input type="file" name="capture_uim" accept="image/*" required
                        class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-[10px] text-gray-400 mt-1">JPG/PNG/WEBP, maks 5MB</p>
                </div>
                <button type="submit" class="w-full h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold transition">
                    Verifikasi & Golive-kan LOP
                </button>
            </form>
        @endif
    </div>

</div>
@endsection
