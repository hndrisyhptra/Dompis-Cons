<script>
    // 1. FUNGSI MENU AKSI (TOGGLE MENU)
    function toggleMenu(event, menuId, btnElement) {
        event.stopPropagation();
        let menu = document.getElementById(menuId);
        let isHidden = menu.classList.contains('hidden');
        
        document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
        
        if (isHidden) {
            menu.classList.remove('hidden');
            let rect = btnElement.getBoundingClientRect();
            let menuHeight = menu.offsetHeight;
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
        // Jika pengguna klik di luar container menu, 
        // ATAU pengguna klik opsi (button/link) di dalam menu itu sendiri
        let clickedOutside = !e.target.closest('.action-menu-container');
        let clickedActionItem = e.target.closest('.action-menu-dropdown button, .action-menu-dropdown a');

        if (clickedOutside || clickedActionItem) {
            document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
        }
    });

    let tableContainer = document.querySelector('.overflow-x-auto');
    if(tableContainer) {
        tableContainer.addEventListener('scroll', function() {
            document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
        });
    }

    // 2. FUNGSI MODAL DETAIL & IMPORT
    function openDetailModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
    }
    function closeDetailModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
    }

    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
        document.getElementById('importModal').classList.add('flex');
    }
    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
        document.getElementById('importModal').classList.remove('flex');
    }

    // 3. FUNGSI ASSIGN WASPANG/TEKNISI
    function searchAssignUser(keyword) {
        let filter = keyword.toLowerCase();
        let items = document.querySelectorAll('.assign-user-item');
        let hasVisible = false;
        
        let neededText = document.getElementById('assignRoleNeeded').innerText || '';
        let neededRole = neededText.includes('Teknisi') ? 'teknisi' : 'waspang';

        items.forEach(item => {
            let name = item.getAttribute('data-name') || '';
            let role = item.getAttribute('data-role');
            
            if (role === neededRole && name.includes(filter)) {
                item.style.display = 'block'; 
                hasVisible = true;
            } else {
                item.style.display = 'none';
            }
        });

        let emptyState = document.getElementById('emptyUserSearch');
        if (emptyState) emptyState.style.display = hasVisible ? 'none' : 'block';
    }

    function openAssignModal(projectId, projectName, program) {
        document.getElementById('project_id').value = projectId;
        document.getElementById('assignProjectName').innerText = projectName;
        
        let prog = program ? String(program).toUpperCase().replace(/\s/g, "") : '';
        let isPT2 = (prog === 'PT2');

        document.getElementById('assignRoleNeeded').innerText = isPT2 ? 'Project ini membutuhkan: Teknisi' : 'Project ini membutuhkan: Waspang';

        let searchInput = document.getElementById('searchWaspangAssign');
        if(searchInput) searchInput.value = '';
        searchAssignUser('');

        document.getElementById('assignModal').classList.remove('hidden');
        document.getElementById('assignModal').classList.add('flex');
    }

    function closeAssignModal() {
        document.getElementById('assignModal').classList.add('hidden');
        document.getElementById('assignModal').classList.remove('flex');
    }

    // 4. FUNGSI KML
    function openKmlModal(projectId, projectName) {
        document.getElementById('kmlModal').classList.remove('hidden');
        document.getElementById('kmlModal').classList.add('flex');
        document.getElementById('kmlForm').action = `/projects/${projectId}/upload-kml`;
        document.getElementById('kmlProjectName').innerText = projectName;
    }
    function closeKmlModal() {
        document.getElementById('kmlModal').classList.add('hidden');
        document.getElementById('kmlModal').classList.remove('flex');
    }

    // 5. FUNGSI MODAL ADD & EDIT PROJECT (GPS & DESIGNATOR)
    
    // MEMBUAT STRING MASTER DESIGNATOR OPTION UNTUK DIGUNAKAN DI JS
    const masterDesignatorOptions = `
        @foreach($designators as $designator)
            <option value="{{ $designator->id_designator }}"
                    data-designator="{{ $designator->designator }}"
                    data-item="{{ addslashes($designator->item_name) }}"
                    data-unit="{{ $designator->unit }}">
                {{ $designator->designator }} - {{ addslashes($designator->item_name) }}
            </option>
        @endforeach
    `;

    function getProjectLocation() {
        if (!navigator.geolocation) { alert('Browser tidak mendukung GPS'); return; }
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                document.getElementById('latitude').value = pos.coords.latitude.toFixed(8);
                document.getElementById('longitude').value = pos.coords.longitude.toFixed(8);
            },
            function(err) { alert('Gagal mengambil lokasi.'); },
            { enableHighAccuracy: true }
        );
    }

    function openProjectModal() {
        document.getElementById('projectModal').classList.remove('hidden');
        document.getElementById('projectModal').classList.add('flex');
        document.getElementById('projectModalTitle').innerText = 'Input LOP Baru';
        document.getElementById('projectForm').action = "{{ route('projects.store') }}";
        document.getElementById('projectMethod').value = 'POST';
        document.getElementById('projectForm').reset();
        document.getElementById('designatorContainer').innerHTML = ''; 
        addDesignatorRow(); 
    }

    function openEditProjectModal(project) {
        document.getElementById('projectModal').classList.remove('hidden');
        document.getElementById('projectModal').classList.add('flex');

        document.getElementById('projectModalTitle').innerText = 'Edit Project & BOQ';
        document.getElementById('projectForm').action = `/projects/update/${project.id}`;
        document.getElementById('projectMethod').value = 'PUT';

        document.getElementById('project_name').value = project.project_name || '';
        document.getElementById('branch').value = project.branch || '';
        document.getElementById('sto').value = project.sto || '';
        document.getElementById('mitra_name').value = project.mitra_name || '';
        document.getElementById('latitude').value = project.latitude || '';
        document.getElementById('longitude').value = project.longitude || '';
        document.getElementById('location_address').value = project.location_address || '';

        renderEditBoqItems(project.boq_items || []);
    }

    function fillDesignatorData(select) {
        const row = select.closest('.designator-row');
        const selected = select.options[select.selectedIndex];
        
        // Cek input target, apakah mode edit atau mode create baru
        const itemNameInput = row.querySelector('input[name="boq_item_name[]"]') || row.querySelector('input[name="existing_item_name[]"]');
        const unitInput = row.querySelector('input[name="boq_unit[]"]') || row.querySelector('input[name="existing_unit[]"]');

        if (itemNameInput) itemNameInput.value = selected.dataset.item || '';
        if (unitInput) unitInput.value = selected.dataset.unit || '';
    }

    function renderEditBoqItems(items) {
        const container = document.getElementById('designatorContainer');
        container.innerHTML = '';
        if (items.length === 0) { addDesignatorRow(); return; }

        items.forEach((item) => {
            const row = `
                <div class="grid grid-cols-12 gap-2 designator-row items-center border-b border-gray-100 dark:border-gray-800 pb-2 mb-2">
                    <input type="hidden" name="existing_boq_id[]" value="${item.id_boq || ''}">
                    
                    <div class="col-span-12 sm:col-span-4">
                        <select name="existing_designator_id[]" onchange="fillDesignatorData(this)" class="designator-select-edit h-10 w-full text-sm rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <option value="${item.designator_id || ''}" data-item="${item.item_name || ''}" data-unit="${item.unit || ''}" selected>
                                ${item.designator || ''} - ${item.item_name || ''}
                            </option>
                            ${masterDesignatorOptions}
                        </select>
                    </div>

                    <input type="text" name="existing_item_name[]" value="${item.item_name || ''}" readonly class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm px-3">
                    <input type="text" name="existing_unit[]" value="${item.unit || ''}" readonly class="col-span-6 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-center px-3">
                    <input type="number" step="0.01" name="existing_qty[]" value="${item.quantity_plan || 0}" class="col-span-6 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm text-center px-3">
                </div>
            `;
            container.insertAdjacentHTML('beforeend', row);
        });

        // Initialize TomSelect jika diperlukan
        container.querySelectorAll('.designator-select-edit').forEach(select => {
            initSingleDesignatorSearch(select);
        });
    }

    function addDesignatorRow() {
        const container = document.getElementById('designatorContainer');
        const row = `
            <div class="grid grid-cols-12 gap-2 designator-row mt-2">
                <div class="col-span-12 sm:col-span-4">
                    <select name="designator_id[]" onchange="fillDesignatorData(this)" class="designator-select h-10 w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                        <option value="">Pilih designator baru...</option>
                        ${masterDesignatorOptions}
                    </select>
                </div>
                
                <input type="text" name="boq_item_name[]" placeholder="Item pekerjaan" readonly class="col-span-12 sm:col-span-4 h-10 rounded-xl bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-sm px-3">
                <input type="text" name="boq_unit[]" placeholder="Satuan" readonly class="col-span-5 sm:col-span-2 h-10 rounded-xl bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-sm text-center px-3">
                <input type="number" step="1" name="boq_qty[]" placeholder="Qty" class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm text-center px-3">
                
                <button type="button" onclick="removeDesignatorRow(this)" class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl font-bold flex items-center justify-center border border-gray-200 dark:border-gray-700">×</button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', row);

        // Aktifkan TomSelect untuk baris baru
        const newRow = container.lastElementChild;
        initSingleDesignatorSearch(newRow.querySelector('.designator-select'));
    }

    function removeDesignatorRow(button) {
        const rows = document.querySelectorAll('.designator-row');
        if (rows.length <= 1) {
            const row = button.closest('.designator-row');
            if (row.querySelector('select').tomselect) {
                row.querySelector('select').tomselect.clear();
            } else {
                row.querySelector('select').value = '';
            }
            row.querySelector('input[name="boq_item_name[]"]').value = '';
            row.querySelector('input[name="boq_unit[]"]').value = '';
            row.querySelector('input[name="boq_qty[]"]').value = '';
            return;
        }
        button.closest('.designator-row').remove();
    }

    function closeProjectModal() {
        document.getElementById('projectModal').classList.add('hidden');
        document.getElementById('projectModal').classList.remove('flex');
    }

    // 6. FUNGSI INISIALISASI TOM SELECT SEARCH (Opsional, jika Anda menggunakan library TomSelect)
    function initSingleDesignatorSearch(select) {
        if (!select || select.tomselect) return;
        
        // Pastikan library TomSelect ada di aplikasi Anda. Jika tidak ada, abaikan blok ini.
        if (typeof TomSelect === 'function') {
            new TomSelect(select, {
                create: false,
                placeholder: 'Cari designator...',
                maxOptions: 1000,
                onChange: function () { fillDesignatorData(select); }
            });
        }
    }

    // DATA BOQ WINDOW SEEDER (Untuk Modal BOQ Tambahan Bawaan)
    window.projectBoqItems = {
        @foreach($projects as $project)
            "{{ $project->id_project }}": @json($project->boqItems),
        @endforeach
    };
</script>