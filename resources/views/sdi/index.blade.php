@extends('layouts.sdi') 

@section('content')
<div class="container mx-auto px-4 py-6">
    
    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Filter & Search Header --}}
    <div class="mb-6 bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">SDI Approval (Go Live)</h1>
            <p class="text-sm text-gray-500 mt-1">Validasi UIM khusus Program PT 2</p>
        </div>
        <form method="GET" action="{{ route('sdi.index') }}" class="w-full md:w-auto relative">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari PID / Nama Project..." 
                   class="w-full md:w-80 h-11 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</div>
        </form>
    </div>

    {{-- Tabel Utama --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden mb-12">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-black text-gray-900 dark:text-white">Daftar Project PT 2</h2>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700">
                    <span class="font-medium">Tampilkan</span>
                    <select onchange="window.location.href=this.value" class="bg-transparent border-none text-gray-900 dark:text-white text-xs font-bold focus:ring-0 cursor-pointer p-0 pr-5">
                        @foreach([10, 20, 50, 100] as $val)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $val, 'page' => 1]) }}" {{ request('per_page', 10) == $val ? 'selected' : '' }}>{{ $val }}</option>
                        @endforeach
                    </select>
                    <span class="font-medium">Baris</span>
                </div>
                <span class="px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold whitespace-nowrap">Total: {{ $projects->total() }}</span>
            </div>
        </div>

        <div class="overflow-x-auto relative">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Project</th>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Lokasi</th>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Progress</th>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Status UIM</th>
                        <th class="px-5 py-3 text-center text-xs font-black uppercase text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($projects as $project)
                        @php
                            $summary = $project->progressSummary();
                            $progress = $summary['progress'];
                            $isComplete = ($progress == 100);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                            <td class="px-5 py-4 min-w-[220px]">
                                <p class="font-black text-gray-900 dark:text-white leading-snug">{{ $project->project_name }}</p>
                                <p class="text-xs text-gray-500 mt-1">PID: {{ $project->pid ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold text-gray-800 dark:text-gray-100">{{ $project->lop?->branch ?? '-' }}</p>
                                <p class="text-xs text-gray-500 mt-1">STO {{ $project->lop?->sto ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4 min-w-[150px]">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-gray-500">Progress</span>
                                    <span class="text-sm font-black {{ $isComplete ? 'text-green-600' : 'text-amber-600' }}">{{ $progress }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full {{ $isComplete ? 'bg-green-600' : 'bg-amber-500' }}" style="width: {{ $progress }}%"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($project->is_golive)
                                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold flex items-center gap-1 w-max">
                                        <span>✔️</span> GO LIVE
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold">Menunggu</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="action-menu-container inline-block text-left">
                                    <button type="button" onclick="toggleMenu(event, 'menu-{{ $project->id_project }}', this)"
                                            class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 text-gray-600 hover:bg-gray-200 hover:text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5h.01M12 12h.01M12 19h.01"/>
                                        </svg>
                                    </button>

                                    <div id="menu-{{ $project->id_project }}" class="action-menu-dropdown hidden fixed w-48 rounded-2xl bg-white border border-gray-200 shadow-2xl z-[9999] overflow-hidden">
                                        <div class="flex flex-col text-left py-2">
                                            {{-- Aksi View Detail (Standar) --}}
                                            <a href="{{ route('admin.projects.tracking', $project->id_project) }}" class="w-full px-4 py-2 text-sm flex items-center gap-3 text-gray-700 hover:bg-gray-100 transition-colors">
                                                <span class="text-lg">📊</span><span class="font-semibold">Tracking Detail</span>
                                            </a>
                                            
                                            <hr class="my-1.5 border-gray-100" />
                                            
                                            {{-- Aksi Eksekusi SDI (Hanya muncul jika Progress 100% dan Belum GoLive) --}}
                                            @if($isComplete && !$project->is_golive)
                                                <button type="button" onclick="openGoLiveModal('{{ $project->id_project }}', '{{ $project->pid }}')" class="w-full px-4 py-2 text-sm flex items-center gap-3 text-green-700 hover:bg-green-50 transition-colors">
                                                    <span class="text-lg">🚀</span><span class="font-semibold">Update Go Live</span>
                                                </button>
                                            @elseif($project->is_golive)
                                                <a href="{{ Storage::url($project->golive_evidence_path) }}" target="_blank" class="w-full px-4 py-2 text-sm flex items-center gap-3 text-blue-700 hover:bg-blue-50 transition-colors">
                                                    <span class="text-lg">🖼️</span><span class="font-semibold">Lihat Eviden UIM</span>
                                                </a>
                                            @else
                                                <div class="w-full px-4 py-2 text-xs flex items-center gap-2 text-red-500 bg-red-50">
                                                    <span class="text-lg">🔒</span><span class="font-semibold">Belum Complete ({{ $progress }}%)</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="font-black text-gray-900">Belum ada project PT 2</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($projects->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL UPLOAD GO LIVE --}}
<div id="goLiveModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl overflow-hidden flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Upload Eviden UIM</h2>
                <p id="goliveProjectPid" class="text-xs text-gray-500 mt-1"></p>
            </div>
            <button type="button" onclick="closeGoLiveModal()" class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200">×</button>
        </div>
        
        <form id="goLiveForm" method="POST" enctype="multipart/form-data" class="flex flex-col">
            @csrf
            <div class="p-5">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Capture / Absen UIM</label>
                    <div class="relative flex items-center justify-center w-full h-32 px-4 transition bg-white border-2 border-gray-300 border-dashed rounded-xl appearance-none hover:border-gray-400 focus:outline-none">
                        <span class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            <span class="font-medium text-gray-600">Pilih file gambar...</span>
                        </span>
                        <input type="file" name="golive_evidence" accept="image/*" class="absolute block w-full h-full opacity-0 cursor-pointer" required>
                    </div>
                    <p class="text-[10px] text-gray-500 mt-2">*Format: JPG, JPEG, PNG (Maks 5MB)</p>
                </div>
            </div>
            <div class="px-5 py-4 bg-gray-50 dark:bg-gray-800 flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="closeGoLiveModal()" class="px-4 py-2 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-100">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-green-600 hover:bg-green-700 shadow-sm">Submit Go Live</button>
            </div>
        </form>
    </div>
</div>

<script>
    // FUNGSI TOGGLE MENU FIXED
    function toggleMenu(event, menuId, btnElement) {
        event.stopPropagation();
        let menu = document.getElementById(menuId);
        let isHidden = menu.classList.contains('hidden');
        
        document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
        
        if (isHidden) {
            menu.classList.remove('hidden');
            let rect = btnElement.getBoundingClientRect();
            let menuHeight = menu.offsetHeight || 150; 
            let spaceBelow = window.innerHeight - rect.bottom;
            
            if (spaceBelow < menuHeight && rect.top > menuHeight) {
                menu.style.top = (rect.top - menuHeight - 5) + 'px'; 
            } else {
                menu.style.top = (rect.bottom + 5) + 'px';
            }
            menu.style.left = (rect.right - menu.offsetWidth) + 'px';
        }
    }

    window.addEventListener('click', function(e) {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
        }
    });

    let tableContainer = document.querySelector('.overflow-x-auto');
    if(tableContainer) {
        tableContainer.addEventListener('scroll', function() {
            document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
        });
    }

    // MODAL GO LIVE
    function openGoLiveModal(projectId, pid) {
        document.getElementById('goliveProjectPid').innerText = 'PID: ' + (pid || '-');
        document.getElementById('goLiveForm').action = '/sdi/golive/' + projectId; // Sesuai dengan route
        
        let modal = document.getElementById('goLiveModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeGoLiveModal() {
        let modal = document.getElementById('goLiveModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.getElementById('goLiveForm').reset();
    }
</script>
@endsection