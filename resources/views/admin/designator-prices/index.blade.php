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
                Kelola harga designator berdasarkan package
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

    {{-- Search --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <form method="GET"
              action="{{ route('designator-prices.index') }}"
              class="flex flex-col sm:flex-row gap-3">

            <input type="text"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Cari designator, item, package..."
                   class="flex-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

            <button class="h-10 px-4 rounded-xl bg-gray-900 text-white text-sm font-semibold">
                Cari
            </button>

            @if(!empty($search))
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
                                {{ $price->price }}
                            </td>

                            <td class="px-4 py-3">

                                <div class="flex justify-end gap-2">

                                    <button type="button"
                                            onclick="openEditPriceModal({
                                                id: '{{ $price->id_price }}',
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
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
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

{{-- MODAL PRICE --}}
<div id="priceModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 id="priceModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">
                    Tambah Harga
                </h2>
                <p class="text-sm text-gray-500">
                    Pilih designator dan package
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
                        Designator
                    </label>

                    <select name="designator_id"
                            id="designator_id"
                            required
                            class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        <option value="">Pilih Designator</option>

                        @foreach($designators as $designator)
                            <option value="{{ $designator->id_designator }}">
                                {{ $designator->designator }} - {{ $designator->item_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Package
                    </label>

                    <select name="package_id"
                            id="package_id"
                            required
                            class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        <option value="">Pilih Package</option>

                        @foreach($packages as $package)
                            <option value="{{ $package->id_package }}">
                                {{ $package->package_code }} - {{ $package->package_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Price
                    </label>

                    <input type="number"
                           name="price"
                           id="price"
                           required
                           min="0"
                           step="0.01"
                           placeholder="contoh: 7098"
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

{{-- MODAL IMPORT --}}
<div id="importPriceModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Import Harga Designator
                </h2>
                <p class="text-sm text-gray-500">
                    Upload CSV harga per package
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

                <input type="file"
                       name="file"
                       accept=".csv,.txt"
                       required
                       class="block w-full text-sm border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer bg-white dark:bg-gray-950 dark:text-gray-300
                              file:mr-3 file:py-2.5 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-bold
                              file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

                <div class="rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 p-3">
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Header CSV:
                        <span class="font-mono text-gray-700 dark:text-gray-300">
                            designator,package_code,price
                        </span>
                    </p>
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

<script>
function openPriceModal()
{
    document.getElementById('priceModal').classList.remove('hidden');
    document.getElementById('priceModal').classList.add('flex');

    document.getElementById('priceModalTitle').innerText = 'Tambah Harga';
    document.getElementById('priceForm').action = "{{ route('designator-prices.store') }}";
    document.getElementById('priceMethod').value = 'POST';

    document.getElementById('priceForm').reset();
}

function openEditPriceModal(item)
{
    document.getElementById('priceModal').classList.remove('hidden');
    document.getElementById('priceModal').classList.add('flex');

    document.getElementById('priceModalTitle').innerText = 'Edit Harga';
    document.getElementById('priceForm').action = `/designator-prices/update/${item.id}`;
    document.getElementById('priceMethod').value = 'PUT';

    document.getElementById('designator_id').value = item.designator_id ?? '';
    document.getElementById('package_id').value = item.package_id ?? '';
    document.getElementById('price').value = item.price ?? '';
}

function closePriceModal()
{
    document.getElementById('priceModal').classList.add('hidden');
    document.getElementById('priceModal').classList.remove('flex');
}

function openImportPriceModal()
{
    document.getElementById('importPriceModal').classList.remove('hidden');
    document.getElementById('importPriceModal').classList.add('flex');
}

function closeImportPriceModal()
{
    document.getElementById('importPriceModal').classList.add('hidden');
    document.getElementById('importPriceModal').classList.remove('flex');
}
</script>

@endsection