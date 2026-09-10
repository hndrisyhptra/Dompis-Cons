@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Master Kategori Kendala
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Daftar flagging kendala yang bisa dipilih di semua tahap (Persiapan, Instalasi, dst)
            </p>
        </div>

        <div class="flex items-center gap-2">

            <button type="button"
                    onclick="openKendalaModal()"
                    class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                + Tambah Kategori
            </button>

        </div>

    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="rounded-2xl bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm font-semibold">
            {{ session('error') }}
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
              action="{{ route('admin.kendala-categories.index') }}"
              class="flex flex-col sm:flex-row gap-3">

            <input type="text"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Cari nama kategori kendala..."
                   class="flex-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

            <button class="h-10 px-4 rounded-xl bg-gray-900 text-white text-sm font-semibold">
                Cari
            </button>

            @if(!empty($search))
                <a href="{{ route('admin.kendala-categories.index') }}"
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
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300 w-20">
                            Urutan
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Nama Kategori
                        </th>
                        <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-32">
                            Status
                        </th>
                        <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-48">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($categories as $category)

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">

                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                {{ $category->sort_order }}
                            </td>

                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                {{ $category->name }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                <form method="POST"
                                      action="{{ route('admin.kendala-categories.toggle-active', $category->id) }}"
                                      class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-3 py-1 rounded-full text-xs font-bold
                                            {{ $category->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-200 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">

                                    <button type="button"
                                            onclick="openEditKendalaModal({
                                                id: '{{ $category->id }}',
                                                name: @js($category->name),
                                                sort_order: {{ (int) $category->sort_order }},
                                                is_active: {{ $category->is_active ? 'true' : 'false' }}
                                            })"
                                            class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Edit
                                    </button>

                                    <form method="POST"
                                          action="{{ route('admin.kendala-categories.destroy', $category->id) }}"
                                          onsubmit="return confirm('Hapus kategori kendala ini? Kendala lama yang sudah pakai kategori ini akan kehilangan kategorinya (bukan ikut terhapus).')">
                                        @csrf
                                        @method('DELETE')

                                        <button class="h-9 px-3 rounded-xl border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                Belum ada kategori kendala.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            {{ $categories->links() }}
        </div>

    </div>

</div>

{{-- MODAL KENDALA CATEGORY --}}
<div id="kendalaModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 id="kendalaModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">
                    Tambah Kategori Kendala
                </h2>
                <p class="text-sm text-gray-500">
                    Isi master kategori kendala
                </p>
            </div>

            <button type="button"
                    onclick="closeKendalaModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form id="kendalaForm" method="POST" action="{{ route('admin.kendala-categories.store') }}">
            @csrf
            <input type="hidden" name="_method" id="kendalaMethod" value="POST">

            <div class="p-5 space-y-4">

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Nama Kategori
                    </label>
                    <input type="text"
                           name="name"
                           id="kendala_name"
                           required
                           placeholder="contoh: MATERIAL"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Urutan Tampil
                    </label>
                    <input type="number"
                           name="sort_order"
                           id="kendala_sort_order"
                           min="0"
                           placeholder="0"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div id="kendala_is_active_wrap" class="hidden items-center gap-2">
                    <input type="checkbox" name="is_active" id="kendala_is_active" value="1" class="rounded">
                    <label for="kendala_is_active" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Aktif
                    </label>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">

                <button type="button"
                        onclick="closeKendalaModal()"
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
function openKendalaModal()
{
    document.getElementById('kendalaModal').classList.remove('hidden');
    document.getElementById('kendalaModal').classList.add('flex');

    document.getElementById('kendalaModalTitle').innerText = 'Tambah Kategori Kendala';
    document.getElementById('kendalaForm').action = "{{ route('admin.kendala-categories.store') }}";
    document.getElementById('kendalaMethod').value = 'POST';
    document.getElementById('kendala_is_active_wrap').classList.add('hidden');
    document.getElementById('kendala_is_active_wrap').classList.remove('flex');

    document.getElementById('kendalaForm').reset();
}

function openEditKendalaModal(item)
{
    document.getElementById('kendalaModal').classList.remove('hidden');
    document.getElementById('kendalaModal').classList.add('flex');

    document.getElementById('kendalaModalTitle').innerText = 'Edit Kategori Kendala';
    document.getElementById('kendalaForm').action = `/admin/kendala-categories/${item.id}`;
    document.getElementById('kendalaMethod').value = 'PUT';

    document.getElementById('kendala_name').value = item.name ?? '';
    document.getElementById('kendala_sort_order').value = item.sort_order ?? 0;
    document.getElementById('kendala_is_active').checked = !!item.is_active;

    document.getElementById('kendala_is_active_wrap').classList.remove('hidden');
    document.getElementById('kendala_is_active_wrap').classList.add('flex');
}

function closeKendalaModal()
{
    document.getElementById('kendalaModal').classList.add('hidden');
    document.getElementById('kendalaModal').classList.remove('flex');
}
</script>

@endsection
