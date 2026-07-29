@extends('layouts.sdi') 

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Filter & Search Header --}}
    <div class="mb-6 bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">SDI Approval (Go Live)</h1>
            <p class="text-sm text-gray-500 mt-1">Validasi UIM khusus Program PT 2</p>
        </div>
        <form method="GET" action="{{ route('sdi.index') }}" class="w-full md:w-auto relative">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari PID / Nama Project..." 
                   class="w-full md:w-80 h-11 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 pl-10 pr-4 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</div>
        </form>
    </div>

    {{-- Tabel Utama --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden mb-12">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-black text-gray-900 dark:text-white">Antrean GOLIVE Project PT 2</h2>
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
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Project & PID</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Lokasi STO</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Tanggal Send</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Status</th>
                        <th class="px-5 py-3 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($projects as $project)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                            <td class="px-5 py-4 min-w-[220px]">
                                <p class="font-black text-gray-900 dark:text-white leading-snug">{{ $project->project_name }}</p>
                                <p class="text-xs font-mono text-gray-500 mt-1">PID: {{ $project->pid ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold text-gray-800 dark:text-gray-100">{{ $project->lop?->branch ?? '-' }}</p>
                                <p class="text-xs text-gray-500 mt-1">STO {{ $project->lop?->sto ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($project->updated_at)->format('d M Y') }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($project->updated_at)->format('H:i') }} WIB</p>
                            </td>
                            <td class="px-5 py-4">
                                @if($project->is_golive)
                                    <span class="px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center gap-1.5 w-max border border-emerald-200">
                                        <span>✅</span> GOLIVE
                                    </span>
                                @else
                                    <span class="px-3 py-1.5 rounded-xl bg-amber-100 text-amber-700 text-xs font-bold flex items-center gap-1.5 w-max border border-amber-200">
                                        <span class="animate-pulse">⏳</span> Waiting Approval
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if(!$project->is_golive)
                                    {{-- TOMBOL PROSES GO-LIVE (Hanya jika belum golive) --}}
                                    <button type="button" onclick="openGoLiveModal('{{ route('sdi.eksekusi.golive', $project->id_project) }}', '{{ $project->pid }}', '{{ $project->project_name }}')" 
                                            class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
                                        Proses Go-Live 🚀
                                    </button>
                                @else
                                    {{-- TOMBOL LIHAT EVIDEN (Jika sudah golive) --}}
                                    <a href="{{ Storage::url($project->golive_evidence_path) }}" target="_blank" 
                                       class="h-9 px-4 rounded-xl bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
                                        Lihat Eviden 🖼️
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="text-4xl mb-3 opacity-30">📭</div>
                                <p class="font-black text-gray-900 text-lg">Antrean Kosong</p>
                                <p class="text-gray-500 text-sm mt-1">Belum ada project yang dikirim dari Admin ke SDI.</p>
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

{{-- MODAL PROSES GO LIVE INTERAKTIF --}}
{{-- Hapus class hidden dan tambahkan m-auto pada modalContent untuk memastikan posisinya presisi --}}
<div id="goLiveModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-900 w-full max-w-md m-auto rounded-3xl overflow-hidden flex flex-col shadow-2xl transform scale-95 transition-transform duration-300" id="modalContent">
        
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50">
            <div>
                <h2 class="text-lg font-black text-gray-900 dark:text-white">Eksekusi Go-Live</h2>
                <p id="goliveProjectName" class="text-xs font-bold text-blue-600 mt-1 truncate max-w-[250px]"></p>
            </div>
            <button type="button" onclick="closeGoLiveModal()" class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center hover:bg-red-100 hover:text-red-600 transition font-black">✕</button>
        </div>
        
        <form id="goLiveForm" method="POST" enctype="multipart/form-data" class="flex flex-col">
            @csrf
            <div class="p-6 space-y-6">
                
                {{-- 1. UPLOAD BUKTI UIM --}}
                <div>
                    <label class="flex items-center justify-between text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        <span>1. Upload Capture UIM</span>
                        <span class="text-red-500 text-[10px] uppercase tracking-wider bg-red-50 px-2 py-0.5 rounded">* Wajib</span>
                    </label>
                    <label class="relative flex flex-col items-center justify-center w-full h-32 px-4 transition bg-gray-50 border-2 border-gray-300 border-dashed rounded-2xl cursor-pointer hover:bg-gray-100 hover:border-blue-400">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <span id="uploadIcon" class="text-2xl mb-2">📸</span>
                            <p id="uploadText" class="text-xs font-bold text-gray-500 text-center">Klik untuk memilih file bukti<br><span class="font-medium text-[10px]">(JPG, PNG, max 5MB)</span></p>
                        </div>
                        <input type="file" id="fileUim" name="golive_evidence" accept="image/*" class="hidden" onchange="validateForm()" required>
                    </label>
                </div>

                <hr class="border-gray-100">

                {{-- 2. TOGGLE GO LIVE --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">2. Konfirmasi Status</label>
                    
                    <label class="flex items-center justify-between cursor-pointer p-4 rounded-2xl border border-gray-200 bg-white hover:bg-gray-50 transition shadow-sm">
                        <div>
                            <p class="text-sm font-black text-gray-900">Ubah Status menjadi GO-LIVE</p>
                            <p class="text-[10px] font-medium text-gray-500 mt-0.5">Dengan ini, data UIM dinyatakan sinkron.</p>
                        </div>
                        <div class="relative">
                            {{-- Input Checkbox yang disembunyikan --}}
                            <input type="checkbox" id="goliveToggle" class="sr-only peer" onchange="validateForm()" required>
                            
                            {{-- Desain Toggle Switch UI --}}
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </div>
                    </label>
                </div>

            </div>
            
            <div class="px-6 py-5 bg-gray-50 dark:bg-gray-800 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 rounded-b-3xl">
                <button type="button" onclick="closeGoLiveModal()" class="h-11 px-5 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 transition">Batal</button>
                
                {{-- Tombol Submit (Secara default Disable/Abu-abu) --}}
                <button type="submit" id="btnSubmitGolive" disabled class="h-11 px-6 rounded-xl text-sm font-bold text-white bg-gray-400 cursor-not-allowed transition-all shadow-sm flex items-center gap-2">
                    Submit & Selesaikan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // SweetAlert Notifikasi Berhasil
    @if(session('success'))
        Swal.fire({
            title: 'Berhasil Go-Live!',
            text: "{{ session('success') }}",
            icon: 'success',
            confirmButtonColor: '#10b981',
            customClass: { popup: 'rounded-3xl' }
        });
    @endif

    // Fungsi Buka Modal (Dengan Animasi)
    function openGoLiveModal(actionUrl, pid, projectName) {
        document.getElementById('goliveProjectName').innerText = projectName + ' (PID: ' + (pid || '-') + ')';
        
        // Form langsung mengambil URL utuh yang sudah di-generate sempurna oleh Laravel
        document.getElementById('goLiveForm').action = actionUrl; 
        
        let modal = document.getElementById('goLiveModal');
        let modalContent = document.getElementById('modalContent');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    // Fungsi Tutup Modal
    function closeGoLiveModal() {
        let modal = document.getElementById('goLiveModal');
        let modalContent = document.getElementById('modalContent');
        
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        
        setTimeout(() => {
            // PERBAIKAN: Kembalikan ke 'hidden'
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            
            document.getElementById('goLiveForm').reset();
            resetUploadUI(); 
            validateForm();  
        }, 300); 
    }

    // Fungsi Interaktif saat File dipilih
    document.getElementById('fileUim').addEventListener('change', function(e) {
        let fileName = e.target.files[0]?.name;
        if(fileName) {
            document.getElementById('uploadIcon').innerText = '✅';
            document.getElementById('uploadText').innerHTML = `<span class="text-emerald-600 font-bold">${fileName}</span><br><span class="text-[10px] text-gray-400">Siap diupload</span>`;
        } else {
            resetUploadUI();
        }
    });

    function resetUploadUI() {
        document.getElementById('uploadIcon').innerText = '📸';
        document.getElementById('uploadText').innerHTML = `Klik untuk memilih file bukti<br><span class="font-medium text-[10px]">(JPG, PNG, max 5MB)</span>`;
    }

    // Fungsi Validasi Toggle & File
    function validateForm() {
        let isToggled = document.getElementById('goliveToggle').checked;
        let hasFile = document.getElementById('fileUim').files.length > 0;
        let btn = document.getElementById('btnSubmitGolive');

        // Tombol HANYA aktif jika file terisi DAN toggle tergeser ke kanan (ON)
        if(isToggled && hasFile) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-gray-400', 'cursor-not-allowed');
            btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
        }
    }
</script>
@endsection