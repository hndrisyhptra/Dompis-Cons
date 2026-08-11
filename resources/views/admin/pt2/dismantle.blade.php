@extends('layouts.admin')

@section('content')
@php
    $lop = $project->lop; // Diambil dari injeksi trik Controller

    // Definisikan daftar item dismantle yang didukung
    $dismantleItems = [
        'odp' => 'Foto Eviden ODP Dibongkar',
        'splitter_1_2' => 'Foto Eviden Splitter 1:2',
        'splitter_1_4' => 'Foto Eviden Splitter 1:4',
        'splitter_1_8' => 'Foto Eviden Splitter 1:8',
        'splitter_1_16' => 'Foto Eviden Splitter 1:16',
    ];

    $allEvidences = $project->evidences->where('stage', 'finishing');

    // Cek apakah step 4 selesai (Semua eviden dismantle yang ada di database sudah di-approve)
    $uploadedDismantleEvidences = $allEvidences->whereIn('evidence_type', array_keys($dismantleItems));
    
    // Logika Step 4: Selesai jika semua fotonya 'approved'. 
    // Atau jika memang tidak ada dismantle sama sekali (karena ini opsional), anggap true.
    $step4Completed = true; 
    if ($uploadedDismantleEvidences->count() > 0) {
        $step4Completed = $uploadedDismantleEvidences->where('status', 'approved')->count() === $uploadedDismantleEvidences->count();
    } elseif ($dismantles->count() > 0) {
        // Ada data teks dismantle, tapi belum ada foto
        $step4Completed = false;
    }
@endphp

<div class="max-w-4xl mx-auto space-y-4 px-4 py-6">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Approval LOP PT 2</h1>
        <p class="text-sm text-gray-500">Pilih project untuk mulai review step by step</p>
    </div>

    {{-- LOP Info Card & Stepper --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-bold text-gray-900 dark:text-white truncate">
                    {{ $lop->lop_name }}
                </h2>
                <p class="text-sm text-gray-500 font-medium">
                    PID: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $project->pid ?? '-' }}</span> · 
                    IHLD: <span class="font-mono text-cyan-600 dark:text-cyan-400">{{ $lop->id_ihld ?? '-' }}</span> · 
                    STO {{ $lop->sto ?? '-' }}
                </p>
            </div>
            <a href="{{ route('admin.pt2.approval') }}" class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 inline-flex items-center text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                ← Kembali
            </a>
        </div>
        
        {{-- PANGGIL STEPPER --}}
        @include('admin.pt2.partials.stepper', ['currentStep' => 4, 'lop' => $lop])
    </div>

    {{-- Step Title --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="h-1 bg-indigo-500"></div>
        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Step 4 — Dismantle (Opsional)</h2>
                <p class="text-sm text-gray-500">Tinjau rincian material dan foto eviden dismantle (pembongkaran).</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $step4Completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $step4Completed ? 'Approved / Clear' : 'Pending Review' }}
            </span>
        </div>
    </div>

    {{-- Tabel Dismantle (Data Teks) --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider">Rincian Data Item Dismantle</h3>
        </div>
        @if($dismantles->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-[10px] uppercase font-bold text-gray-500">
                        <tr>
                            <th class="p-4 border-b border-gray-200 dark:border-gray-700">No</th>
                            <th class="p-4 border-b border-gray-200 dark:border-gray-700">Kategori</th>
                            <th class="p-4 border-b border-gray-200 dark:border-gray-700">Nama Item</th>
                            <th class="p-4 border-b border-gray-200 dark:border-gray-700 text-center">Qty</th>
                            <th class="p-4 border-b border-gray-200 dark:border-gray-700">Tanggal Input</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium text-gray-700 dark:text-gray-300">
                        @foreach($dismantles as $idx => $dsm)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="p-4">{{ $idx + 1 }}</td>
                            <td class="p-4 uppercase text-[11px] font-bold">{{ $dsm->category ?? '-' }}</td>
                            <td class="p-4">{{ $dsm->item_name }}</td>
                            <td class="p-4 text-center font-black text-indigo-600 dark:text-indigo-400">{{ $dsm->qty }}</td>
                            <td class="p-4 text-xs text-gray-500">{{ \Carbon\Carbon::parse($dsm->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center flex flex-col items-center justify-center">
                <div class="w-14 h-14 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-gray-400 text-2xl mb-3">📦</div>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Data Dismantle Kosong</p>
                <p class="text-xs text-gray-400 mt-1">Teknisi tidak menginput item yang dibongkar pada LOP ini.</p>
            </div>
        @endif
    </div>

    {{-- Eviden Foto Dismantle (Dipisah per Item Menggunakan Review-Item Terpisah) --}}
    @php
        $counter = 1;
    @endphp

    @foreach($dismantleItems as $key => $label)
        @php
            $evList = $allEvidences->where('evidence_type', $key);
        @endphp

        {{-- Hanya tampilkan komponen jika teknisi mengupload foto untuk item tersebut --}}
        @if($evList->count() > 0)
            @include('admin.evidences.partials.review-item', [
                'isPt2' => true,
                'number' => $counter++,
                'title' => $label,
                'description' => 'Bukti foto fisik penarikan ' . strtolower($label),
                'items' => $evList,
                'type' => $key,
            ])
        @endif
    @endforeach

    {{-- Footer --}}
    <div class="flex items-center justify-between pt-2">
        <a href="{{ route('admin.pt2.redaman', $lop->id_pt2_lop) }}" class="text-sm font-bold text-gray-500 hover:text-gray-900 dark:hover:text-white transition">← Step Redaman</a>
        <a href="{{ route('admin.pt2.mancore', $lop->id_pt2_lop) }}" class="h-10 px-5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-bold inline-flex items-center justify-center transition hover:opacity-90">
            Step Mancore →
        </a>
    </div>

</div>
@endsection