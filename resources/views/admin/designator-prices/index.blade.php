@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Harga Designator
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kelola harga designator berdasarkan package tiap customer
            </p>
        </div>

        <div class="flex items-center gap-2">

            <button type="button"
                    onclick="openImportPriceModal()"
                    class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                Import CSV
            </button>

            <button type="button"
                    onclick="openPriceModal()"
                    class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                + Tambah Harga
            </button>

        </div>

    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="rounded-2xl bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Search & Filter --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <form method="GET"
              action="{{ route('designator-prices.index') }}"
              class="flex flex-col sm:flex-row gap-3">

            <select name="customer_id" 
                    class="sm:w-48 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                <option value="">Semua Customer</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id_customer }}" {{ request('customer_id') == $c->id_customer ? 'selected' : '' }}>
                        {{ $c->customer_name }}
                    </option>
                @endforeach
            </select>

            <input type="text"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Cari designator, item, package..."
                   class="flex-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

            <button class="h-10 px-4 rounded-xl bg-gray-900 text-white text-sm font-semibold">
                Cari
            </button>

            @if(!empty($search) || request()->has('customer_id'))
                <a href="{{ route('designator-prices.index') }}"
                   class="h-10 px-4 inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold">
                    Reset
                </a>
            @endif

        </form>

    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Customer
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Designator
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Item
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Type
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Package
                        </th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">
                            Price
                        </th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($prices as $price)

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">

                            <td class="px-4 py-3 font-bold text-indigo-700 dark:text-indigo-400 whitespace-nowrap">
                                {{ $price->package->customer->customer_code ?? '-' }}
                            </td>

                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $price->designator->designator ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                <p class="font-semibold max-w-xs truncate">
                                    {{ $price->designator->item_name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Unit: {{ $price->designator->unit ?? '-' }}
                                </p>
                            </td>

                            <td class="px-4 py-3">
                                @if(optional($price->designator)->type == 'material')
                                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">
                                        Material
                                    </span>
                                @elseif(optional($price->designator)->type == 'jasa')
                                    <span class="px-2.5 py-1 rounded-lg bg-green-50 text-green-700 text-xs font-bold">
                                        Jasa
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-xs font-bold">
                                        -
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ $price->package->package_code ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $price->package->package_name ?? '-' }}
                                </p>
                            </td>

                           <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($price->price, 2, ',', '.') }}
                            </td>

                            <td class="px-4 py-3">

                                <div class="flex justify-end gap-2">

                                    <button type="button"
                                            onclick="openEditPriceModal({
                                                id: '{{ $price->id_price }}',
                                                customer_id: '{{ $price->package->customer_id ?? '' }}',
                                                designator_id: '{{ $price->designator_id }}',
                                                package_id: '{{ $price->package_id }}',
                                                price: '{{ $price->price }}'
                                            })"
                                            class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Edit
                                    </button>

                                    <form method="POST"
                                          action="{{ route('designator-prices.destroy', $price->id_price) }}"
                                          onsubmit="return confirm('Hapus harga designator ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button class="h-9 px-3 rounded-xl border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50">
                                            Delete
                                        </button>
                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                Belum ada data harga designator.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($prices->hasPages())
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-800">

            <div class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $prices->firstItem() }}</span>
                -
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $prices->lastItem() }}</span>
                dari
                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $prices->total() }}</span>
                data
            </div>

            <div class="flex flex-wrap items-center justify-center gap-1">

                @if ($prices->onFirstPage())
                    <span class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-400 cursor-not-allowed">
                        ←
                    </span>
                @else
                    <a href="{{ $prices->previousPageUrl() }}"
                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        ←
                    </a>
                @endif

                @foreach ($prices->getUrlRange(
                    max(1, $prices->currentPage() - 1),
                    min($prices->lastPage(), $prices->currentPage() + 1)
                ) as $page => $url)

                    @if ($page == $prices->currentPage())
                        <span class="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                            {{ $page }}
                        </a>
                    @endif

                @endforeach

                @if ($prices->hasMorePages())
                    <a href="{{ $prices->nextPageUrl() }}"
                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        →
                    </a>
                @else
                    <span class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-400 cursor-not-allowed">
                        →
                    </span>
                @endif

            </div>

        </div>
        @endif

    </div>

</div>

{{-- MODAL TAMBAH/EDIT PRICE --}}
<div id="priceModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 id="priceModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">
                    Tambah Harga
                </h2>
                <p class="text-sm text-gray-500">
                    Pilih customer, designator, dan package
                </p>
            </div>

            <button type="button"
                    onclick="closePriceModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form id="priceForm" method="POST" action="{{ route('designator-prices.store') }}">
            @csrf
            <input type="hidden" name="_method" id="priceMethod" value="POST">

            <div class="p-5 space-y-4">

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Pilih Customer <span class="text-red-500">*</span>
                    </label>
                    <select id="modal_customer_id" onchange="updateDependentDropdowns()" required
                            class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        <option value="">-- Pilih Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id_customer }}">{{ $c->customer_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Designator <span class="text-red-500">*</span>
                    </label>

                    <select id="designator_id" name="designator_id" required disabled
                            class="select2-dark mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Pilih Customer Terlebih Dahulu</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Package <span class="text-red-500">*</span>
                    </label>

                    <select name="package_id" id="package_id" required disabled
                            class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        <option value="">Pilih Customer Terlebih Dahulu</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Price <span class="text-red-500">*</span>
                    </label>

                    <input type="number" name="price" id="price" required min="0" step="0.01"
                           placeholder="contoh: 7098.50"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">

                <button type="button"
                        onclick="closePriceModal()"
                        class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold">
                    Batal
                </button>

                <button type="submit"
                        class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                    Simpan
                </button>

            </div>

        </form>

    </div>

</div>

{{-- MODAL IMPORT PRICE --}}
<div id="importPriceModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Import Harga Designator
                </h2>
                <p class="text-sm text-gray-500">
                    Pilih paket lalu upload CSV harganya
                </p>
            </div>

            <button type="button"
                    onclick="closeImportPriceModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form method="POST"
              action="{{ route('designator-prices.import') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="p-5 space-y-4">

                <!-- KATEGORI PROJECT (TOGGLE) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Kategori Project <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="flex items-center gap-6 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/50">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="import_price_type" value="internal" checked onchange="toggleImportPriceCustomer()" 
                                   class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">TIF (Internal)</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="import_price_type" value="external" onchange="toggleImportPriceCustomer()" 
                                   class="w-4 h-4 text-amber-500 focus:ring-amber-500 border-gray-300">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Eksternal Bisnis</span>
                        </label>
                    </div>
                </div>

                <!-- HIDDEN INPUT: Dibaca oleh Controller untuk Customer ID -->
                <input type="hidden" name="customer_id" id="import_price_hidden_customer_id" value="1">

                <!-- DROPDOWN EXTERNAL CUSTOMER (Hidden by Default) -->
                <div id="import_price_external_wrapper" class="hidden">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 block">
                        Pilih Customer Exbis <span class="text-red-500">*</span>
                    </label>
                    <select id="import_price_customer_id_select" onchange="updateImportPriceHiddenId()"
                            class="w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Customer --</option>
                        @foreach(\App\Models\Customer::where('id_customer', '!=', 1)->active()->get() as $c)
                            <option value="{{ $c->id_customer }}">{{ $c->customer_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- DROPDOWN PACKAGE -->
                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 block">
                        Pilih Package <span class="text-red-500">*</span>
                    </label>
                    <select name="package_id" id="import_package_id" required
                            class="w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Package --</option>
                        <!-- Opsi akan dirender via JS -->
                    </select>
                    <p class="text-[10px] text-gray-500 mt-1">Seluruh harga di CSV akan diikat ke paket ini.</p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        File CSV / TXT <span class="text-red-500">*</span>
                    </label>
                    <input type="file"
                           name="file"
                           accept=".csv,.txt"
                           required
                           class="mt-1 block w-full text-sm border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer bg-white dark:bg-gray-950 dark:text-gray-300
                                  file:mr-3 file:py-2.5 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-bold
                                  file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div class="rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 p-3">
                    <p class="text-xs text-gray-500 leading-relaxed font-semibold">
                        Header CSV yang didukung:
                    </p>
                    <p class="font-mono text-[11px] font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                        designator,price,type
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">* Kolom <span class="font-mono">type</span> opsional (isi: material/jasa).</p>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                <button type="button"
                        onclick="closeImportPriceModal()"
                        class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold">
                    Batal
                </button>
                <button class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                    Upload
                </button>
            </div>

        </form>

    </div>

</div>

<!-- SCRIPT JS UNTUK MODAL IMPORT HARGA -->
<script>
    function loadImportPackages(customerId) {
        const pkgSelect = document.getElementById('import_package_id');
        pkgSelect.innerHTML = '<option value="">-- Pilih Package --</option>';

        if (!customerId) return;

        // allPackages sudah dideklarasikan sebelumnya di index.blade.php
        const filteredPkgs = allPackages.filter(p => p.customer_id == customerId);
        filteredPkgs.forEach(p => {
            let opt = new Option(`${p.package_code} - ${p.package_name}`, p.id_package);
            pkgSelect.appendChild(opt);
        });
    }

    function toggleImportPriceCustomer() {
        const type = document.querySelector('input[name="import_price_type"]:checked').value;
        const wrapper = document.getElementById('import_price_external_wrapper');
        const select = document.getElementById('import_price_customer_id_select');
        const hiddenInput = document.getElementById('import_price_hidden_customer_id');

        if (type === 'external') {
            wrapper.classList.remove('hidden');
            select.required = true;
            hiddenInput.value = select.value; 
            loadImportPackages(select.value); // Load package sesuai dropdown
        } else {
            wrapper.classList.add('hidden');
            select.required = false;
            select.value = ""; 
            hiddenInput.value = "1"; // TIF
            loadImportPackages(1); // Load package TIF
        }
    }

    function updateImportPriceHiddenId() {
        const select = document.getElementById('import_price_customer_id_select');
        const hiddenInput = document.getElementById('import_price_hidden_customer_id');
        hiddenInput.value = select.value;
        loadImportPackages(select.value); // Reload package saat customer exbis diganti
    }

    function openImportPriceModal()
    {
        document.getElementById('importPriceModal').classList.remove('hidden');
        document.getElementById('importPriceModal').classList.add('flex');
        
        // Reset toggle ke posisi awal (TIF) setiap modal dibuka
        const internalRadio = document.querySelector('input[name="import_price_type"][value="internal"]');
        if(internalRadio) {
            internalRadio.checked = true;
            toggleImportPriceCustomer();
        }
    }

    function closeImportPriceModal()
    {
        document.getElementById('importPriceModal').classList.add('hidden');
        document.getElementById('importPriceModal').classList.remove('flex');
    }
</script>

<script>
// Load data master dari backend untuk JS logic
const allDesignators = @json($designators);
const allPackages = @json($packages);

function initDesignatorSelect()
{
    if ($('#designator_id').hasClass('select2-hidden-accessible')) {
        return;
    }

    $('#designator_id').select2({
        width: '100%',
        dropdownParent: $('#priceModal'),
        placeholder: 'Pilih Designator...',
        allowClear: true,
        containerCssClass: 'mt-1'
    });
}

function updateDependentDropdowns(selectedDesig = '', selectedPkg = '') {
    const customerId = document.getElementById('modal_customer_id').value;
    const pkgSelect = document.getElementById('package_id');
    const desigSelect = $('#designator_id');

    pkgSelect.innerHTML = '<option value="">Pilih Package</option>';
    desigSelect.empty().append('<option value="">Pilih Designator</option>');

    if (!customerId) {
        pkgSelect.disabled = true;
        desigSelect.prop('disabled', true);
        desigSelect.trigger('change');
        return;
    }

    pkgSelect.disabled = false;
    desigSelect.prop('disabled', false);

    // Filter packages
    const filteredPkgs = allPackages.filter(p => p.customer_id == customerId);
    filteredPkgs.forEach(p => {
        let opt = new Option(`${p.package_code} - ${p.package_name}`, p.id_package, false, p.id_package == selectedPkg);
        pkgSelect.appendChild(opt);
    });

    // Filter designators
    const filteredDesigs = allDesignators.filter(d => d.customer_id == customerId);
    filteredDesigs.forEach(d => {
        let typeBadge = d.type ? ` (${d.type.toUpperCase()})` : '';
        let opt = new Option(`${d.designator} - ${d.item_name}${typeBadge}`, d.id_designator, false, d.id_designator == selectedDesig);
        desigSelect.append(opt);
    });

    desigSelect.trigger('change');
}

function openPriceModal()
{
    document.getElementById('priceModal').classList.remove('hidden');
    document.getElementById('priceModal').classList.add('flex');

    document.getElementById('priceModalTitle').innerText = 'Tambah Harga';
    document.getElementById('priceForm').action = "{{ route('designator-prices.store') }}";
    document.getElementById('priceMethod').value = 'POST';

    document.getElementById('priceForm').reset();
    document.getElementById('modal_customer_id').value = "";
    updateDependentDropdowns();

    initDesignatorSelect();
}

function openEditPriceModal(item)
{
    document.getElementById('priceModal').classList.remove('hidden');
    document.getElementById('priceModal').classList.add('flex');

    document.getElementById('priceModalTitle').innerText = 'Edit Harga';
    // Sesuaikan penamaan route update (misal: /designator-prices/{id} atau update/{id})
    document.getElementById('priceForm').action = `/designator-prices/update/${item.id}`;
    document.getElementById('priceMethod').value = 'PUT';

    document.getElementById('modal_customer_id').value = item.customer_id;
    
    initDesignatorSelect();
    
    // Trigger update dropdown dengan select values
    updateDependentDropdowns(item.designator_id, item.package_id);

    document.getElementById('price').value = item.price ?? '';
}
</script>

@endsection