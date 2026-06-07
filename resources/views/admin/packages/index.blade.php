@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Master Package
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kelola paket harga designator
            </p>
        </div>

        <div class="flex items-center gap-2">

            <button type="button"
                    onclick="openImportPackageModal()"
                    class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                Import CSV
            </button>

            <button type="button"
                    onclick="openPackageModal()"
                    class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                + Tambah Package
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
              action="{{ route('packages.index') }}"
              class="flex flex-col sm:flex-row gap-3">

            <input type="text"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Cari package code, nama package..."
                   class="flex-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

            <button class="h-10 px-4 rounded-xl bg-gray-900 text-white text-sm font-semibold">
                Cari
            </button>

            @if(!empty($search))
                <a href="{{ route('packages.index') }}"
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
                            Package Code
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Package Name
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Description
                        </th>
                        <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($packages as $package)

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">

                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                {{ $package->package_code }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $package->package_name }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $package->description ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">

                                    <button type="button"
                                            onclick="openEditPackageModal({
                                                id: '{{ $package->id_package }}',
                                                package_code: @js($package->package_code),
                                                package_name: @js($package->package_name),
                                                description: @js($package->description)
                                            })"
                                            class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Edit
                                    </button>

                                    <form method="POST"
                                          action="{{ route('packages.destroy', $package->id_package) }}"
                                          onsubmit="return confirm('Hapus package ini?')">
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
                                Belum ada data package.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            {{ $packages->links() }}
        </div>

    </div>

</div>

{{-- MODAL PACKAGE --}}
<div id="packageModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 id="packageModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">
                    Tambah Package
                </h2>
                <p class="text-sm text-gray-500">
                    Isi master paket harga
                </p>
            </div>

            <button type="button"
                    onclick="closePackageModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form id="packageForm" method="POST" action="{{ route('packages.store') }}">
            @csrf
            <input type="hidden" name="_method" id="packageMethod" value="POST">

            <div class="p-5 space-y-4">

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Package Code
                    </label>
                    <input type="text"
                           name="package_code"
                           id="package_code"
                           required
                           placeholder="contoh: PKG5"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Package Name
                    </label>
                    <input type="text"
                           name="package_name"
                           id="package_name"
                           required
                           placeholder="contoh: Paket 5"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Description
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              placeholder="Keterangan paket"
                              class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm"></textarea>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">

                <button type="button"
                        onclick="closePackageModal()"
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
<div id="importPackageModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Import Package
                </h2>
                <p class="text-sm text-gray-500">
                    Upload CSV package
                </p>
            </div>

            <button type="button"
                    onclick="closeImportPackageModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form method="POST"
              action="{{ route('packages.import') }}"
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
                            package_code,package_name,description
                        </span>
                    </p>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">

                <button type="button"
                        onclick="closeImportPackageModal()"
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
function openPackageModal()
{
    document.getElementById('packageModal').classList.remove('hidden');
    document.getElementById('packageModal').classList.add('flex');

    document.getElementById('packageModalTitle').innerText = 'Tambah Package';
    document.getElementById('packageForm').action = "{{ route('packages.store') }}";
    document.getElementById('packageMethod').value = 'POST';

    document.getElementById('packageForm').reset();
}

function openEditPackageModal(item)
{
    document.getElementById('packageModal').classList.remove('hidden');
    document.getElementById('packageModal').classList.add('flex');

    document.getElementById('packageModalTitle').innerText = 'Edit Package';
    document.getElementById('packageForm').action = `/packages/update/${item.id}`;
    document.getElementById('packageMethod').value = 'PUT';

    document.getElementById('package_code').value = item.package_code ?? '';
    document.getElementById('package_name').value = item.package_name ?? '';
    document.getElementById('description').value = item.description ?? '';
}

function closePackageModal()
{
    document.getElementById('packageModal').classList.add('hidden');
    document.getElementById('packageModal').classList.remove('flex');
}

function openImportPackageModal()
{
    document.getElementById('importPackageModal').classList.remove('hidden');
    document.getElementById('importPackageModal').classList.add('flex');
}

function closeImportPackageModal()
{
    document.getElementById('importPackageModal').classList.add('hidden');
    document.getElementById('importPackageModal').classList.remove('flex');
}
</script>

@endsection