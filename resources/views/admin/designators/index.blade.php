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
                Kelola master designator, item pekerjaan, dan satuan
            </p>
        </div>

        <button type="button"
                onclick="openDesignatorModal()"
                class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
            + Tambah Designator
        </button>

    </div>

    {{-- Search --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <form method="GET" action="{{ route('designators.index') }}"
              class="flex flex-col sm:flex-row gap-3">

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
                            Designator
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Item Pekerjaan
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Satuan
                        </th>
                        <th class="px-4 py-3 text-right font-bold text-gray-600 dark:text-gray-300">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($designators as $item)

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">

                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                {{ $item->designator }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $item->item_name }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $item->unit }}
                            </td>

                            <td class="px-4 py-3">

                                <div class="flex justify-end gap-2">

                                    <button type="button"
                                            onclick="openEditDesignatorModal({
                                                id: '{{ $item->id_designator }}',
                                                designator: @js($item->designator),
                                                item_name: @js($item->item_name),
                                                unit: @js($item->unit)
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
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                Belum ada data designator.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            {{ $designators->links() }}
        </div>

    </div>

</div>

{{-- MODAL --}}
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

            <div class="p-5 space-y-4">

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Designator
                    </label>
                    <input type="text"
                           name="designator"
                           id="designator"
                           required
                           placeholder="contoh: KU-FO-001"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Item Pekerjaan
                    </label>
                    <input type="text"
                           name="item_name"
                           id="item_name"
                           required
                           placeholder="contoh: Kabel Fiber Optik"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Satuan
                    </label>
                    <input type="text"
                           name="unit"
                           id="unit"
                           required
                           placeholder="contoh: m / unit / titik"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
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

<script>
    function openDesignatorModal()
    {
        document.getElementById('designatorModal').classList.remove('hidden');
        document.getElementById('designatorModal').classList.add('flex');

        document.getElementById('designatorModalTitle').innerText = 'Tambah Designator';
        document.getElementById('designatorForm').action = "{{ route('designators.store') }}";
        document.getElementById('designatorMethod').value = 'POST';

        document.getElementById('designatorForm').reset();
    }

    function openEditDesignatorModal(item)
    {
        document.getElementById('designatorModal').classList.remove('hidden');
        document.getElementById('designatorModal').classList.add('flex');

        document.getElementById('designatorModalTitle').innerText = 'Edit Designator';
        document.getElementById('designatorForm').action = `/designators/update/${item.id}`;
        document.getElementById('designatorMethod').value = 'PUT';

        document.getElementById('designator').value = item.designator ?? '';
        document.getElementById('item_name').value = item.item_name ?? '';
        document.getElementById('unit').value = item.unit ?? '';
    }

    function closeDesignatorModal()
    {
        document.getElementById('designatorModal').classList.add('hidden');
        document.getElementById('designatorModal').classList.remove('flex');
    }
    </script>

    @endsection