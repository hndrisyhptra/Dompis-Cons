{{-- ADD ITEM DESIGNATOR MODAL --}}
<div id="boqModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-3 sm:p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-4xl max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">

        <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-gray-200 dark:border-gray-800">

            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Tambah Item Designator
                </h2>

                <p id="boqProjectName" class="text-sm text-gray-500 mt-1">
                    Pilih item designator tambahan
                </p>
            </div>

            <button type="button"
                    onclick="closeBoqModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 text-xl hover:bg-gray-100 dark:hover:bg-gray-800">
                ×
            </button>

        </div>

        <form method="POST"
              action="{{ route('projects.boq.store') }}"
              class="flex flex-col min-h-0">
            @csrf

            <input type="hidden" name="project_id" id="boq_project_id">

            <div class="p-5 overflow-y-auto space-y-5">

                {{-- EXISTING BOQ --}}
                <div>

                    <div class="flex items-center justify-between mb-3">

                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Item Designator Saat Ini
                            </h3>
                            <p class="text-xs text-gray-500">
                                Item BOQ yang sudah terinput pada project ini
                            </p>
                        </div>

                        <span id="existingBoqCount"
                              class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[11px] font-bold">
                            0 item
                        </span>

                    </div>

                    <div id="existingBoqList"
                         class="rounded-2xl border border-gray-200 dark:border-gray-800 divide-y divide-gray-200 dark:divide-gray-800 overflow-hidden">

                        <div class="p-4 text-sm text-gray-500 text-center">
                            Pilih project terlebih dahulu.
                        </div>

                    </div>

                </div>

                {{-- NEW BOQ --}}
                <div class="border-t border-gray-200 dark:border-gray-800 pt-5">

                    <div class="flex items-center justify-between gap-3 mb-3">

                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Tambah Item Designator Baru
                            </h3>
                            <p class="text-xs text-gray-500">
                                Cari designator, item dan satuan otomatis terisi
                            </p>
                        </div>

                        <button type="button"
                                onclick="addBoqRow()"
                                class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                            + Item
                        </button>

                    </div>

                    <div id="boqContainer" class="space-y-3">

                        <div class="grid grid-cols-12 gap-2 boq-row">

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
                                   class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                            <input type="number"
                                   step="0.01"
                                   name="boq_qty[]"
                                   placeholder="0"
                                   class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

                            <button type="button"
                                    onclick="removeBoqRow(this)"
                                    class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl">
                                ×
                            </button>

                        </div>

                    </div>

                </div>

            </div>

            <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">

                <button type="button"
                        onclick="closeBoqModal()"
                        class="h-10 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                    Batal
                </button>

                <button type="submit"
                        class="h-10 rounded-xl bg-gray-900 hover:bg-black text-white text-sm font-semibold">
                    Simpan Item Baru
                </button>

            </div>

        </form>

    </div>

</div>