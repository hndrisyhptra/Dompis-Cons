@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Master Designator
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kelola master designator, item pekerjaan, satuan, dan customer
            </p>
        </div>

       <div class="flex items-center gap-2">

            <button type="button"
                    onclick="openImportDesignatorModal()"
                    class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                Import CSV
            </button>

            <button type="button"
                    onclick="openDesignatorModal()"
                    class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                + Tambah Designator
            </button>

        </div>

    </div>

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

        <form method="GET" action="{{ route('designators.index') }}"
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
                   value="{{ request('search') }}"
                   placeholder="Cari designator, item pekerjaan, satuan..."
                   class="flex-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

            <button class="h-10 px-4 rounded-xl bg-gray-900 text-white text-sm font-semibold">
                Cari
            </button>

            <a href="{{ route('designators.index') }}"
               class="h-10 px-4 inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold">
                Reset
            </a>

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
                            Uraian Pekerjaan
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Satuan
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Type
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Pair Code
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Kategori
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($designators as $item)

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">

                            <td class="px-4 py-3 font-bold text-indigo-700 dark:text-indigo-400">
                                {{ $item->customer->customer_code ?? '-' }}
                            </td>

                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                {{ $item->designator }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                <div class="w-48 truncate" title="{{ $item->item_name }}">
                                    {{ $item->item_name }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $item->unit }}
                            </td>
                            
                            <td class="px-4 py-3">
                                @if($item->type == 'material')
                                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">
                                        Material
                                    </span>
                                @elseif($item->type == 'jasa')
                                    <span class="px-2.5 py-1 rounded-lg bg-green-50 text-green-700 text-xs font-bold">
                                        Jasa
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-xs font-bold">
                                        -
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $item->pair_code ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-bold text-xs">
                                {{ $item->progress_category ?? 'OTHER' }}
                            </td>

                            <td class="px-4 py-3">

                                <div class="flex justify-end gap-2">

                                    <button type="button"
                                            onclick="openEditDesignatorModal({
                                                id: '{{ $item->id_designator }}',
                                                customer_id: @js($item->customer_id),
                                                designator: @js($item->designator),
                                                item_name: @js($item->item_name),
                                                unit: @js($item->unit),
                                                type: @js($item->type),
                                                pair_code: @js($item->pair_code),
                                                progress_category: @js($item->progress_category)
                                            })"
                                            class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Edit
                                    </button>

                                    <form method="POST"
                                          action="{{ route('designators.destroy', $item->id_designator) }}"
                                          onsubmit="return confirm('Hapus designator ini?')">
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
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                Belum ada data designator.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($designators->hasPages())
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-800">

            <div class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                <span class="font-semibold">{{ $designators->firstItem() }}</span>
                -
                <span class="font-semibold">{{ $designators->lastItem() }}</span>
                dari
                <span class="font-semibold">{{ $designators->total() }}</span>
                data
            </div>

            <div class="flex items-center gap-1">

                {{-- Previous --}}
                @if ($designators->onFirstPage())
                    <span class="px-3 py-2 rounded-lg border text-gray-400 cursor-not-allowed">
                        ←
                    </span>
                @else
                    <a href="{{ $designators->previousPageUrl() }}"
                    class="px-3 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                        ←
                    </a>
                @endif

                {{-- Page Numbers --}}
                @foreach ($designators->getUrlRange(
                    max(1, $designators->currentPage()-1),
                    min($designators->lastPage(), $designators->currentPage()+1)
                ) as $page => $url)

                    @if ($page == $designators->currentPage())
                        <span class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                        class="px-4 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                            {{ $page }}
                        </a>
                    @endif

                @endforeach

                {{-- Next --}}
                @if ($designators->hasMorePages())
                    <a href="{{ $designators->nextPageUrl() }}"
                    class="px-3 py-2 rounded-lg border hover:bg-gray-100 dark:hover:bg-gray-800">
                        →
                    </a>
                @else
                    <span class="px-3 py-2 rounded-lg border text-gray-400 cursor-not-allowed">
                        →
                    </span>
                @endif

            </div>

        </div>
        @endif

    </div>

</div>

{{-- MODAL TAMBAH/EDIT --}}
<div id="designatorModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 id="designatorModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">
                    Tambah Designator
                </h2>
                <p class="text-sm text-gray-500">
                    Isi master item pekerjaan
                </p>
            </div>

            <button type="button"
                    onclick="closeDesignatorModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form id="designatorForm" method="POST" action="{{ route('designators.store') }}">
            @csrf
            <input type="hidden" name="_method" id="designatorMethod" value="POST">

            <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Customer <span class="text-red-500">*</span>
                    </label>
                    <select name="customer_id" id="modal_customer_id" required
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
                    <input type="text" name="designator" id="designator" required
                           placeholder="contoh: DC-OF-SM-12D"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Item Pekerjaan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="item_name" id="item_name" required
                           placeholder="contoh: Kabel Fiber Optik"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Satuan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="unit" id="unit" required
                           placeholder="contoh: meter / lot / titik"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Type
                    </label>
                    <select name="type" id="type"
                            class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        <option value="">Auto Detect (Material & Jasa)</option>
                        <option value="material">Material Saja</option>
                        <option value="jasa">Jasa Saja</option>
                    </select>
                    <p class="text-[10px] text-gray-500 mt-1">Kosongkan agar sistem membaca otomatis (atau memecah jadi 2 jika Mitratel).</p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Pair Code
                    </label>
                    <input type="text" name="pair_code" id="pair_code"
                           placeholder="contoh: DC-OF-SM-12D"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Progress Category
                    </label>
                    <select name="progress_category" id="progress_category"
                            class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        @foreach($progressCategories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                <button type="button"
                        onclick="closeDesignatorModal()"
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

{{-- MODAL IMPORT --}}
<div id="importDesignatorModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Import CSV Designator
                </h2>
                <p class="text-sm text-gray-500">
                    Sistem mendukung Auto Virtual Split
                </p>
            </div>

            <button type="button"
                    onclick="closeImportDesignatorModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form method="POST"
              action="{{ route('designators.import') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="p-5 space-y-4">
                
                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Pilih Customer <span class="text-red-500">*</span>
                    </label>
                    <select name="customer_id" required
                            class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        <option value="">-- Pilih Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id_customer }}">{{ $c->customer_name }}</option>
                        @endforeach
                    </select>
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
                    <p class="font-mono text-[10px] text-gray-700 dark:text-gray-300 mt-1">
                        designator,item_name,unit,type,pair_code,progress_category
                    </p>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">

                <button type="button"
                        onclick="closeImportDesignatorModal()"
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

<script>
    function openDesignatorModal()
    {
        document.getElementById('designatorModal').classList.remove('hidden');
        document.getElementById('designatorModal').classList.add('flex');

        document.getElementById('designatorModalTitle').innerText = 'Tambah Designator';
        document.getElementById('designatorForm').action = "{{ route('designators.store') }}";
        document.getElementById('designatorMethod').value = 'POST';
        
        document.getElementById('designatorForm').reset();
        
        // Reset default dropdowns
        document.getElementById('modal_customer_id').value = "";
        document.getElementById('progress_category').value = "OTHER";
    }

    function openEditDesignatorModal(item)
    {
        document.getElementById('designatorModal').classList.remove('hidden');
        document.getElementById('designatorModal').classList.add('flex');

        document.getElementById('designatorModalTitle').innerText = 'Edit Designator';
        // Sesuaikan dengan penamaan parameter route update di web.php Anda, biasanya id_designator atau id
        document.getElementById('designatorForm').action = `/designators/${item.id}`; 
        document.getElementById('designatorMethod').value = 'PUT';

        document.getElementById('modal_customer_id').value = item.customer_id ?? '';
        document.getElementById('designator').value = item.designator ?? '';
        document.getElementById('item_name').value = item.item_name ?? '';
        document.getElementById('unit').value = item.unit ?? '';
        document.getElementById('type').value = item.type ?? '';
        document.getElementById('pair_code').value = item.pair_code ?? '';
        document.getElementById('progress_category').value = item.progress_category ?? 'OTHER';
    }

    function closeDesignatorModal()
    {
        document.getElementById('designatorModal').classList.add('hidden');
        document.getElementById('designatorModal').classList.remove('flex');
    }

    function openImportDesignatorModal()
    {
        document.getElementById('importDesignatorModal').classList.remove('hidden');
        document.getElementById('importDesignatorModal').classList.add('flex');
    }

    function closeImportDesignatorModal()
    {
        document.getElementById('importDesignatorModal').classList.add('hidden');
        document.getElementById('importDesignatorModal').classList.remove('flex');
    }
</script>

@endsection