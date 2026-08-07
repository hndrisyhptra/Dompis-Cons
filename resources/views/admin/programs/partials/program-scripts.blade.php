<script>
function searchAssignUser(keyword) {
        let filter = keyword.toLowerCase();
        let items = document.querySelectorAll('.assign-user-item');
        let hasVisible = false;
        
        // Cek indikator teks untuk mengetahui apakah butuh Teknisi atau Waspang
        let neededText = document.getElementById('assignRoleNeeded').innerText || '';
        let neededRole = neededText.includes('Teknisi') ? 'teknisi' : 'waspang';

        items.forEach(item => {
            let name = item.getAttribute('data-name');
            name = name ? name.toLowerCase() : '';
            let role = item.getAttribute('data-role');
            
            // Logika: Jika Role-nya cocok DAN namanya mengandung huruf yang diketik
            if (role === neededRole && name.includes(filter)) {
                item.style.display = 'block'; 
                hasVisible = true;
            } else {
                item.style.display = 'none';
            }
        });

        // Tampilkan peringatan "Kosong" jika tidak ada yang cocok
        let emptyState = document.getElementById('emptyUserSearch');
        if (emptyState) {
            emptyState.style.display = hasVisible ? 'none' : 'block';
        }
    }

    // FUNGSI 2: Buka Modal
    function openAssignModal(projectId, projectName, program) {
        document.getElementById('project_id').value = projectId;
        document.getElementById('assignProjectName').innerText = projectName;
        
        // Deteksi program PT2
        let prog = program ? String(program).toUpperCase().replace(/\s/g, "") : '';
        let isPT2 = (prog === 'PT2');

        // Ubah teks indikator
        document.getElementById('assignRoleNeeded').innerText = isPT2 ? 'Project ini membutuhkan: Teknisi' : 'Project ini membutuhkan: Waspang';

        // Bersihkan kolom pencarian setiap kali modal dibuka
        let searchInput = document.getElementById('searchWaspangAssign');
        if(searchInput) searchInput.value = '';

        // Panggil fungsi search dengan keyword kosong untuk me-reset daftar list (menyesuaikan role)
        searchAssignUser('');

        // Tampilkan Modal
        document.getElementById('assignModal').classList.remove('hidden');
        document.getElementById('assignModal').classList.add('flex');
    }

function closeAssignModal()
    {
        const modal = document.getElementById('assignModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('searchWaspangAssign');

    if (!searchInput) return;

    searchInput.addEventListener('keyup', function () {

        const keyword = this.value.toLowerCase().trim();

        const items = document.querySelectorAll('.assign-waspang-item');
        const emptyState = document.getElementById('emptyWaspangSearch');

        let visibleCount = 0;

        items.forEach(item => {

            const name = item.dataset.name || '';

            if (name.includes(keyword)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }

        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }

    });

});
</script>

<script>
    function openDetailModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDetailModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
        document.getElementById('importModal').classList.add('flex');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
        document.getElementById('importModal').classList.remove('flex');
    }

    function openProjectModal() {
        const modal = document.getElementById('projectModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('projectModalTitle').innerText = 'Input LOP Baru';
        document.getElementById('projectForm').action = "{{ route('projects.store') }}";
        document.getElementById('projectMethod').value = 'POST';
        document.getElementById('projectForm').reset();

        if (document.getElementById('status')) {
            document.getElementById('status').value = 'active';
        }
    }

    function openEditProjectModal(project) {
        document.getElementById('projectModal').classList.remove('hidden');
        document.getElementById('projectModal').classList.add('flex');

        document.getElementById('projectModalTitle').innerText = 'Edit Project & BOQ';
        document.getElementById('projectForm').action = `/projects/update/${project.id}`;
        document.getElementById('projectMethod').value = 'PUT';

        document.getElementById('project_name').value = project.project_name ?? '';
        document.getElementById('branch').value = project.branch ?? '';
        document.getElementById('sto').value = project.sto ?? '';
        document.getElementById('mitra_name').value = project.mitra_name ?? '';
        document.getElementById('latitude').value = project.latitude ?? '';
        document.getElementById('longitude').value = project.longitude ?? '';
        document.getElementById('location_address').value = project.location_address ?? '';

        if (document.getElementById('status')) {
            document.getElementById('status').value = project.status ?? 'active';
        }

        renderEditBoqItems(project.boq_items ?? []);
    }

    // Fungsi Pengisi Data Master Designator ke Field Input
    function fillDesignatorData(select) {
        const row = select.closest('.designator-row');
        const selected = select.options[select.selectedIndex];

        // Mendukung input array untuk item baru (boq_item_name) dan edit lama (existing_item_name)
        const itemNameInput = row.querySelector('input[name="boq_item_name[]"]') || row.querySelector('input[name="existing_item_name[]"]');
        const unitInput = row.querySelector('input[name="boq_unit[]"]') || row.querySelector('input[name="existing_unit[]"]');

        if (itemNameInput) itemNameInput.value = selected.dataset.item || '';
        if (unitInput) unitInput.value = selected.dataset.unit || '';
    }

    // Merender Daftar BOQ yang sudah ada pada Modal Edit (Dilengkapi Dropdown TomSelect)
    function renderEditBoqItems(items) {
        const container = document.getElementById('designatorContainer');
        container.innerHTML = '';

        if (items.length === 0) {
            addDesignatorRow();
            return;
        }

        items.forEach((item) => {
            const row = `
                <div class="grid grid-cols-12 gap-2 designator-row items-center border-b border-gray-100 dark:border-gray-800 pb-2 mb-2">
                    <input type="hidden" name="existing_boq_id[]" value="${item.id_boq ?? ''}">

                    <div class="col-span-12 sm:col-span-4">
                        <select name="existing_designator_id[]"
                                onchange="fillDesignatorData(this)"
                                class="designator-select-edit h-10 text-sm">
                            <option value="${item.designator_id ?? ''}" 
                                    data-item="${item.item_name ?? ''}" 
                                    data-unit="${item.unit ?? ''}" 
                                    selected>
                                ${item.designator ?? ''} - ${item.item_name ?? ''}
                            </option>
                            @foreach($designators as $designator)
                                <option value="{{ $designator->id_designator }}"
                                        data-designator="{{ $designator->designator }}"
                                        data-item="{{ $designator->item_name }}"
                                        data-unit="{{ $designator->unit }}">
                                    {{ $designator->designator }} - {{ $designator->item_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="text"
                           name="existing_item_name[]"
                           value="${item.item_name ?? ''}"
                           readonly
                           class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 focus:ring-0">

                    <input type="text"
                           name="existing_unit[]"
                           value="${item.unit ?? ''}"
                           readonly
                           class="col-span-6 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 focus:ring-0 text-center">

                    <input type="number"
                           step="0.01"
                           name="existing_qty[]"
                           value="${item.quantity_plan ?? 0}"
                           class="col-span-6 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm text-center">
                </div>
            `;
            container.insertAdjacentHTML('beforeend', row);
        });

        // Inisialisasi TomSelect pada kolom dropdown edit
        container.querySelectorAll('.designator-select-edit').forEach(select => {
            initSingleDesignatorSearch(select);
        });
    }

    // Fungsi Tambah Baris Designator Baru
    function addDesignatorRow() {
        const container = document.getElementById('designatorContainer');
        const row = `
            <div class="grid grid-cols-12 gap-2 designator-row mt-2">
                <div class="col-span-12 sm:col-span-4">
                    <select name="designator_id[]"
                            onchange="fillDesignatorData(this)"
                            class="designator-select h-10 text-sm">
                        <option value="">Pilih designator baru...</option>
                        @foreach($designators as $designator)
                            <option value="{{ $designator->id_designator }}"
                                    data-designator="{{ $designator->designator }}"
                                    data-item="{{ $designator->item_name }}"
                                    data-unit="{{ $designator->unit }}">
                                {{ $designator->designator }} - {{ $designator->item_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="text"
                       name="boq_item_name[]"
                       placeholder="Item pekerjaan"
                       readonly
                       class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                <input type="text"
                       name="boq_unit[]"
                       placeholder="Satuan"
                       readonly
                       class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 text-center">

                <input type="number"
                       step="1"
                       name="boq_qty[]"
                       placeholder="Qty Plan"
                       class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm text-center">

                <button type="button"
                        onclick="removeDesignatorRow(this)"
                        class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl font-bold flex items-center justify-center">
                    ×
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', row);
        const newRow = container.lastElementChild;
        const newSelect = newRow.querySelector('.designator-select');
        initSingleDesignatorSearch(newSelect);
    }

    function removeDesignatorRow(button) {
        const rows = document.querySelectorAll('.designator-row');
        if (rows.length <= 1) {
            const row = button.closest('.designator-row');
            if(row.querySelector('select').tomselect) {
                row.querySelector('select').tomselect.clear();
            }
            row.querySelector('input[name="boq_item_name[]"]').value = '';
            row.querySelector('input[name="boq_unit[]"]').value = '';
            row.querySelector('input[name="boq_qty[]"]').value = '';
            return;
        }
        button.closest('.designator-row').remove();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initDesignatorSearch();
    });

    function initDesignatorSearch() {
        document.querySelectorAll('.designator-select').forEach(select => {
            initSingleDesignatorSearch(select);
        });
    }

    function initSingleDesignatorSearch(select) {
        if (!select || select.tomselect) return;
        new TomSelect(select, {
            create: false,
            placeholder: 'Ketik designator...',
            maxOptions: 1000,
            searchField: ['text'],
            sortField: { field: 'text', direction: 'asc' },
            onChange: function () { fillDesignatorData(select); }
        });
    }

    function closeProjectModal() {
        document.getElementById('projectModal').classList.add('hidden');
        document.getElementById('projectModal').classList.remove('flex');
    }

    // ---------------------------------------------------------
    // FUNGSI AKSI BARIS TABEL: EDIT & HAPUS DESIGNATOR
    // ---------------------------------------------------------
    function editBoqItem(idBoq, designator, plan) {
        // Karena sistem edit sudah tergabung dalam Modal Utama "Edit Project", 
        // Anda bisa langsung menampilkan alert instruksi atau otomatis memicu modal utama
        alert("Silakan klik tombol 'Edit Project' berwarna putih di pojok kanan bawah modal detail ini untuk mengganti designator atau menyesuaikan volume plan.");
    }

    function deleteBoqItem(idBoq) {
        if (!confirm('Peringatan: Yakin ingin menghapus item designator ini dari proyek? (Tindakan ini tidak bisa dibatalkan)')) {
            return;
        }

        // Membuat form POST (spoofed menjadi DELETE) secara dinamis
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/projects/boq/${idBoq}`; // Menunjuk ke route destroyBoq yang baru kita buat

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodOverride = document.createElement('input');
        methodOverride.type = 'hidden';
        methodOverride.name = '_method';
        methodOverride.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodOverride);
        
        document.body.appendChild(form);
        form.submit();
    }

    // ---------------------------------------------------------
    // KUMPULAN FUNGSI LAINNYA
    // ---------------------------------------------------------
    function getProjectLocation() {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung GPS / Geolocation');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('latitude').value = position.coords.latitude.toFixed(8);
                document.getElementById('longitude').value = position.coords.longitude.toFixed(8);
            },
            function(error) {
                alert('Gagal mengambil lokasi. Pastikan izin lokasi browser aktif.');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function openKmlModal(projectId, projectName) {
        const modal = document.getElementById('kmlModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('kmlForm').action = `/projects/${projectId}/upload-kml`;
        document.getElementById('kmlProjectName').innerText = projectName;
    }

    function closeKmlModal() {
        const modal = document.getElementById('kmlModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function toggleMenu(event, menuId, btnElement) {
        event.stopPropagation(); // Mencegah klik menyebar ke window
        
        let menu = document.getElementById(menuId);
        let isHidden = menu.classList.contains('hidden');
        
        // 1. Tutup semua dropdown menu yang sedang terbuka
        document.querySelectorAll('.action-menu-dropdown').forEach(el => {
            el.classList.add('hidden');
        });
        
        if (isHidden) {
            // 2. Tampilkan menu
            menu.classList.remove('hidden');
            
            // 3. Ambil koordinat tombol yang di-klik
            let rect = btnElement.getBoundingClientRect();
            
            // 4. Hitung ruang layar yang tersedia
            let menuHeight = menu.offsetHeight;
            let spaceBelow = window.innerHeight - rect.bottom;
            
            // 5. Jika ruang di bawah sempit, buka ke ATAS. Jika luas, buka ke BAWAH.
            if (spaceBelow < menuHeight && rect.top > menuHeight) {
                menu.style.top = (rect.top - menuHeight - 5) + 'px'; // Buka ke Atas
            } else {
                menu.style.top = (rect.bottom + 5) + 'px'; // Buka ke Bawah
            }
            
            // 6. Rata Kanan dengan tombol
            menu.style.left = (rect.right - menu.offsetWidth) + 'px';
        }
    }

        // TUTUP MENU SAAT KLIK DI LUAR
        window.addEventListener('click', function(e) {
            if (!e.target.closest('.action-menu-container')) {
                document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
            }
        });

        // TUTUP MENU SAAT TABEL DI-SCROLL (Agar menu fixed tidak melayang tertinggal)
        let tableContainer = document.querySelector('.overflow-x-auto');
        if(tableContainer) {
            tableContainer.addEventListener('scroll', function() {
                document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
            });
        }

    document.addEventListener('click', function(e){
        if(!e.target.closest('.relative')){
            document.querySelectorAll('[id^="menu-"]').forEach(menu=>{
                menu.classList.add('hidden');
            });
        }
    });
</script>

<script>
    // WINDOW BOQ ITEMS SEEDER
    window.projectBoqItems = {
        @foreach($projects as $project)
            "{{ $project->id_project }}": [
                @foreach($project->boqItems as $boq)
                    {
                        id_boq: @js($boq->id_boq ?? null),
                        designator_id: @js($boq->designator_id ?? null),
                        designator: @js($boq->designator ?? '-'),
                        item_name: @js($boq->item_name ?? '-'),
                        unit: @js($boq->unit ?? '-'),
                        quantity_plan: @js($boq->quantity_plan ?? 0),
                    },
                @endforeach
            ],
        @endforeach
    };
</script>

<script>
    // FUNGSI MODAL BOQ (TAMBAH DESIGNATOR DARI LUAR)
    function openBoqModal(projectId, projectName) {
        const modal = document.getElementById('boqModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('boq_project_id').value = projectId;
        document.getElementById('boqProjectName').innerText = projectName;

        renderExistingBoq(projectId);
        resetBoqRows();

        setTimeout(() => {
            initBoqDesignatorSearch();
        }, 50);
    }

    function closeBoqModal() {
        const modal = document.getElementById('boqModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function renderExistingBoq(projectId) {
        const list = document.getElementById('existingBoqList');
        const count = document.getElementById('existingBoqCount');
        const items = window.projectBoqItems[projectId] || [];

        count.innerText = `${items.length} item`;

        if (items.length === 0) {
            list.innerHTML = `<div class="p-4 text-sm text-gray-500 text-center">Belum ada item designator pada project ini.</div>`;
            return;
        }

        list.innerHTML = items.map((item) => {
            return `
                <div class="p-3 flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">${item.item_name}</p>
                        <p class="text-xs text-gray-500 mt-0.5">${item.designator} · ${item.unit}</p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-[11px] font-bold">
                        Plan ${item.quantity_plan}
                    </span>
                </div>
            `;
        }).join('');
    }

    function resetBoqRows() {
        const container = document.getElementById('boqContainer');
        const rows = container.querySelectorAll('.boq-row');

        rows.forEach((row, index) => {
            if (index > 0) row.remove();
        });

        const firstRow = container.querySelector('.boq-row');
        if (!firstRow) return;

        const select = firstRow.querySelector('select');
        if (select && select.tomselect) {
            select.tomselect.clear();
        } else if (select) {
            select.value = '';
        }

        firstRow.querySelector('input[name="boq_item_name[]"]').value = '';
        firstRow.querySelector('input[name="boq_unit[]"]').value = '';
        firstRow.querySelector('input[name="boq_qty[]"]').value = '';
    }

    function fillBoqDesignatorData(select) {
        const row = select.closest('.boq-row');
        const selected = select.options[select.selectedIndex];
        row.querySelector('input[name="boq_item_name[]"]').value = selected.dataset.item || '';
        row.querySelector('input[name="boq_unit[]"]').value = selected.dataset.unit || '';
    }

    function addBoqRow() {
        const container = document.getElementById('boqContainer');
        const row = `
            <div class="grid grid-cols-12 gap-2 boq-row mt-2">
                <select name="designator_id[]"
                        onchange="fillBoqDesignatorData(this)"
                        class="boq-designator-select col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                    <option value="">Cari designator...</option>
                    @foreach($designators as $designator)
                        <option value="{{ $designator->id_designator }}"
                                data-designator="{{ $designator->designator }}"
                                data-item="{{ $designator->item_name }}"
                                data-unit="{{ $designator->unit }}">
                            {{ $designator->designator }} - {{ $designator->item_name }}
                        </option>
                    @endforeach
                </select>

                <input type="text"
                       name="boq_item_name[]"
                       placeholder="Item pekerjaan"
                       readonly
                       class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                <input type="text"
                       name="boq_unit[]"
                       placeholder="Satuan"
                       readonly
                       class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 text-center">

                <input type="number"
                       step="0.01"
                       name="boq_qty[]"
                       placeholder="0"
                       class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm text-center">

                <button type="button"
                        onclick="removeBoqRow(this)"
                        class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl font-bold flex items-center justify-center">
                    ×
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', row);
        const newRow = container.lastElementChild;
        const newSelect = newRow.querySelector('.boq-designator-select');
        initSingleBoqDesignatorSearch(newSelect);
    }

    function removeBoqRow(button) {
        const rows = document.querySelectorAll('.boq-row');
        if (rows.length <= 1) {
            const row = button.closest('.boq-row');
            const select = row.querySelector('select');
            if (select.tomselect) select.tomselect.clear();
            else select.value = '';

            row.querySelector('input[name="boq_item_name[]"]').value = '';
            row.querySelector('input[name="boq_unit[]"]').value = '';
            row.querySelector('input[name="boq_qty[]"]').value = '';
            return;
        }
        button.closest('.boq-row').remove();
    }

    function initSingleBoqDesignatorSearch(select) {
        if (!select || select.tomselect) return;
        new TomSelect(select, {
            create: false,
            placeholder: 'Cari designator...',
            maxOptions: 1000,
            searchField: ['text'],
            sortField: { field: 'text', direction: 'asc' },
            onChange: function () { fillBoqDesignatorData(select); }
        });
    }

    function initBoqDesignatorSearch() {
        document.querySelectorAll('.boq-designator-select').forEach(select => {
            initSingleBoqDesignatorSearch(select);
        });
    }
</script>
{{-- SCRIPT UNTUK TOGGLE MATRIKS --}}
        <script>
            function toggleRegion(className, iconId) {
                const rows = document.querySelectorAll('.' + className);
                const icon = document.getElementById(iconId);
                let isHidden = true;
                
                rows.forEach(row => {
                    if (row.classList.contains('hidden')) {
                        row.classList.remove('hidden');
                        isHidden = false;
                    } else {
                        row.classList.add('hidden');
                    }
                });

                if (icon) icon.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(90deg)';
            }
        </script>