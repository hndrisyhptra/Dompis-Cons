<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persiapan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    <div class="bg-blue-700 text-white px-5 pt-7 pb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('waspang.projects.show', $project->id_project) }}" class="text-3xl">‹</a>
            <div>
                <h1 class="text-xl font-bold">Step 1 - Persiapan</h1>
                <p class="text-xs opacity-90">{{ $project->project_name }}</p>
            </div>
        </div>
    </div>

    <div class="px-4 mt-5 space-y-3">

        {{-- Barang Tiba --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-4 flex items-center gap-3">

            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold
                {{ optional($barangTiba)->status == 'approved' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ optional($barangTiba)->status == 'approved' ? '✓' : '1' }}
            </div>

            <div class="flex-1">
                <h2 class="text-sm font-bold">Eviden Barang Tiba</h2>
                <p class="text-xs text-gray-500">
                    Status: {{ optional($barangTiba)->status ?? 'belum upload' }}
                </p>
            </div>

            <a href="#"
               class="h-9 px-3 rounded-xl bg-blue-700 text-white text-xs font-bold inline-flex items-center">
                Upload
            </a>

        </div>

        {{-- Perizinan --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-4 flex items-center gap-3">

            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold
                {{ optional($perizinan)->status == 'approved' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ optional($perizinan)->status == 'approved' ? '✓' : '2' }}
            </div>

            <div class="flex-1">
                <h2 class="text-sm font-bold">Eviden Perizinan</h2>
                <p class="text-xs text-gray-500">
                    Status: {{ optional($perizinan)->status ?? 'belum upload' }}
                </p>
            </div>

            <a href="#"
               class="h-9 px-3 rounded-xl bg-blue-700 text-white text-xs font-bold inline-flex items-center">
                Upload
            </a>

        </div>

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])

</div>

</body>
</html>