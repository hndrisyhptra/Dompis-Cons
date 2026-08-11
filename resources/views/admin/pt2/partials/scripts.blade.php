<script>
    // 1. FUNGSI MENU AKSI (TOGGLE MENU DROPDOWN LOP)
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

    // 2. FUNGSI MODAL DETAIL LOP
    function openDetailModalPt2(modalId) {
        let modal = document.getElementById(modalId) || document.getElementById('detail-modal-pt2-' + modalId);
        if(modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }
    
    function closeDetailModalPt2(modalId) {
        let modal = document.getElementById(modalId);
        if(modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // 3. FUNGSI MODAL EDIT LOP
    function openEditLopModalPt2(lopId) {
        let modal = document.getElementById('edit-lop-modal-' + lopId);
        if(modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeEditLopModalPt2(modalId) {
        let modal = document.getElementById(modalId);
        if(modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // 4. FUNGSI MODAL KML
    function openKmlModalPt2(lopId, lopName) {
        document.getElementById('kmlLopName').innerText = 'LOP: ' + lopName;
        // document.getElementById('kmlFormPt2').action = `/pt2/upload-kml/${lopId}`; // Sesuaikan endpoint KML PT 2 jika ada
        
        document.getElementById('kmlModalPt2').classList.remove('hidden');
        document.getElementById('kmlModalPt2').classList.add('flex');
    }

    function closeKmlModalPt2() {
        document.getElementById('kmlModalPt2').classList.add('hidden');
        document.getElementById('kmlModalPt2').classList.remove('flex');
    }

    // 5. FUNGSI ASSIGN TEKNISI
    function searchAssignTeknisiPt2(keyword) {
        let filter = keyword.toLowerCase();
        let items = document.querySelectorAll('.assign-teknisi-item');
        let hasVisible = false;

        items.forEach(item => {
            let name = item.getAttribute('data-name') || '';
            if (name.includes(filter)) {
                item.style.display = 'block'; 
                hasVisible = true;
            } else {
                item.style.display = 'none';
            }
        });

        let emptyState = document.getElementById('emptyTeknisiSearch');
        if (emptyState) emptyState.style.display = hasVisible ? 'none' : 'block';
    }

    function openAssignModalPt2(projectId, lopId, lopName) {
        document.getElementById('pt2_project_id_input').value = projectId;
        document.getElementById('pt2_lop_id_input').value = lopId;
        document.getElementById('assignLopName').innerText = 'LOP: ' + lopName;
        
        let searchInput = document.getElementById('searchTeknisiPt2');
        if(searchInput) searchInput.value = '';
        searchAssignTeknisiPt2('');

        document.getElementById('assignModalPt2').classList.remove('hidden');
        document.getElementById('assignModalPt2').classList.add('flex');
    }

    function closeAssignModalPt2() {
        document.getElementById('assignModalPt2').classList.add('hidden');
        document.getElementById('assignModalPt2').classList.remove('flex');
    }
</script>