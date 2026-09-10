@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Master Tahapan Alur LOP
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Daftar tahapan (status_progress) alur LOP reguler PT3, dari Inisiasi sampai Golive
            </p>
        </div>

        <div class="flex items-center gap-2">

            <button type="button"
                    onclick="openStageModal()"
                    class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                + Tambah Tahapan
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

    <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 text-amber-800 dark:text-amber-300 px-4 py-3 text-xs leading-relaxed">
        <strong>Perhatian:</strong> kolom <strong>Kode</strong> tidak bisa diubah lagi setelah tahapan dibuat (dipakai langsung oleh sistem sebagai acuan status LOP). Tahapan <strong>Hold</strong> &amp; <strong>Drop</strong> juga tidak boleh dihapus/dinonaktifkan karena dipakai langsung oleh logic aplikasi. Tahapan yang masih dipakai minimal 1 LOP tidak bisa dihapus -- nonaktifkan saja kalau tidak ingin dipakai lagi untuk LOP baru.
    </div>

    {{-- Search --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <form method="GET"
              action="{{ route('admin.project-stages.index') }}"
              class="flex flex-col sm:flex-row gap-3">

            <input type="text"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Cari kode, label, atau kelompok tahap..."
                   class="flex-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

            <button class="h-10 px-4 rounded-xl bg-gray-900 text-white text-sm font-semibold">
                Cari
            </button>

            @if(!empty($search))
                <a href="{{ route('admin.project-stages.index') }}"
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
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300 w-16">
                            Urutan
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Kode
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Label
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Kelompok
                        </th>
                        <th class="px-4 py-3 text-left font-bold text-gray-600 dark:text-gray-300">
                            Sifat
                        </th>
                        <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-28">
                            Status
                        </th>
                        <th class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-300 w-48">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($stages as $stage)

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">

                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                {{ $stage->sequence ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="font-mono text-xs px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    {{ $stage->code }}
                                </span>
                            </td>

                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                <span class="inline-flex items-center gap-2">
                                    @if($stage->color)
                                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $stage->color }}"></span>
                                    @endif
                                    {{ $stage->label }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $stage->phase_group ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                @if($stage->is_pause_type)
                                    <span class="text-xs font-bold text-orange-600 dark:text-orange-400">Jeda (Hold)</span>
                                @elseif($stage->is_terminal)
                                    <span class="text-xs font-bold text-red-600 dark:text-red-400">Batal (Drop)</span>
                                @else
                                    <span class="text-xs text-gray-400">Alur normal</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center">
                                <form method="POST"
                                      action="{{ route('admin.project-stages.toggle-active', $stage->id) }}"
                                      class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-3 py-1 rounded-full text-xs font-bold
                                            {{ $stage->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-200 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $stage->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">

                                    <button type="button"
                                            onclick="openEditStageModal({
                                                id: '{{ $stage->id }}',
                                                code: @js($stage->code),
                                                label: @js($stage->label),
                                                phase_group: @js($stage->phase_group),
                                                sequence: {{ $stage->sequence !== null ? (int) $stage->sequence : 'null' }},
                                                color: @js($stage->color),
                                                is_pause_type: {{ $stage->is_pause_type ? 'true' : 'false' }},
                                                is_terminal: {{ $stage->is_terminal ? 'true' : 'false' }},
                                                is_active: {{ $stage->is_active ? 'true' : 'false' }},
                                                description: @js($stage->description)
                                            })"
                                            class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Edit
                                    </button>

                                    @if(!in_array($stage->code, ['hold', 'drop'], true))
                                        <form method="POST"
                                              action="{{ route('admin.project-stages.destroy', $stage->id) }}"
                                              onsubmit="return confirm('Hapus tahapan ini? Hanya bisa dihapus kalau tidak sedang dipakai LOP manapun.')">
                                            @csrf
                                            @method('DELETE')

                                            <button class="h-9 px-3 rounded-xl border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                Belum ada tahapan.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

{{-- MODAL PROJECT STAGE --}}
<div id="stageModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 overflow-y-auto">

    <div class="bg-white dark:bg-gray-900 w-full max-w-xl rounded-2xl overflow-hidden my-8">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 id="stageModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">
                    Tambah Tahapan
                </h2>
                <p class="text-sm text-gray-500">
                    Isi master tahapan alur LOP
                </p>
            </div>

            <button type="button"
                    onclick="closeStageModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form id="stageForm" method="POST" action="{{ route('admin.project-stages.store') }}">
            @csrf
            <input type="hidden" name="_method" id="stageMethod" value="POST">

            <div class="p-5 space-y-4 max-h-[60vh] overflow-y-auto">

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Kode <span id="stage_code_lock_note" class="hidden text-xs font-normal text-gray-400">(tidak bisa diubah)</span>
                    </label>
                    <input type="text"
                           name="code"
                           id="stage_code"
                           required
                           placeholder="contoh: survey_ulang (huruf kecil, tanpa spasi)"
                           pattern="[a-z0-9_\-]+"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm font-mono">
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Label
                    </label>
                    <input type="text"
                           name="label"
                           id="stage_label"
                           required
                           placeholder="contoh: Survey Ulang"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Kelompok (phase_group)
                        </label>
                        <input type="text"
                               name="phase_group"
                               id="stage_phase_group"
                               placeholder="contoh: persiapan"
                               class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Urutan (sequence)
                        </label>
                        <input type="number"
                               name="sequence"
                               id="stage_sequence"
                               min="1"
                               placeholder="kosongkan utk Hold/Drop"
                               class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Warna (nama warna Tailwind, opsional)
                    </label>
                    <input type="text"
                           name="color"
                           id="stage_color"
                           placeholder="contoh: blue, emerald, amber"
                           class="mt-1 w-full h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div class="flex flex-wrap gap-5">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_pause_type" id="stage_is_pause_type" value="1" class="rounded">
                        Tipe Jeda (Hold)
                    </label>

                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_terminal" id="stage_is_terminal" value="1" class="rounded">
                        Tipe Batal (Drop)
                    </label>

                    <label id="stage_is_active_wrap" class="hidden items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_active" id="stage_is_active" value="1" class="rounded">
                        Aktif
                    </label>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Deskripsi
                    </label>
                    <textarea name="description"
                              id="stage_description"
                              rows="3"
                              placeholder="Keterangan tahapan (opsional)"
                              class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm"></textarea>
                </div>

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">

                <button type="button"
                        onclick="closeStageModal()"
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
function openStageModal()
{
    document.getElementById('stageModal').classList.remove('hidden');
    document.getElementById('stageModal').classList.add('flex');

    document.getElementById('stageModalTitle').innerText = 'Tambah Tahapan';
    document.getElementById('stageForm').action = "{{ route('admin.project-stages.store') }}";
    document.getElementById('stageMethod').value = 'POST';

    document.getElementById('stageForm').reset();

    document.getElementById('stage_code').readOnly = false;
    document.getElementById('stage_code_lock_note').classList.add('hidden');
    document.getElementById('stage_is_active_wrap').classList.add('hidden');
    document.getElementById('stage_is_active_wrap').classList.remove('flex');
}

function openEditStageModal(item)
{
    document.getElementById('stageModal').classList.remove('hidden');
    document.getElementById('stageModal').classList.add('flex');

    document.getElementById('stageModalTitle').innerText = 'Edit Tahapan';
    document.getElementById('stageForm').action = `/admin/project-stages/${item.id}`;
    document.getElementById('stageMethod').value = 'PUT';

    document.getElementById('stage_code').value = item.code ?? '';
    document.getElementById('stage_code').readOnly = true;
    document.getElementById('stage_code_lock_note').classList.remove('hidden');

    document.getElementById('stage_label').value = item.label ?? '';
    document.getElementById('stage_phase_group').value = item.phase_group ?? '';
    document.getElementById('stage_sequence').value = item.sequence ?? '';
    document.getElementById('stage_color').value = item.color ?? '';
    document.getElementById('stage_is_pause_type').checked = !!item.is_pause_type;
    document.getElementById('stage_is_terminal').checked = !!item.is_terminal;
    document.getElementById('stage_description').value = item.description ?? '';

    document.getElementById('stage_is_active').checked = !!item.is_active;
    document.getElementById('stage_is_active_wrap').classList.remove('hidden');
    document.getElementById('stage_is_active_wrap').classList.add('flex');
}

function closeStageModal()
{
    document.getElementById('stageModal').classList.add('hidden');
    document.getElementById('stageModal').classList.remove('flex');
}
</script>

@endsection
