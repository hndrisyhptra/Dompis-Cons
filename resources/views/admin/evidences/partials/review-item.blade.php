@php
    $status = $items->first()->status ?? 'pending';
    $firstItem = $items->first();

    $photoCount = $items->count();

    $statusClass = match ($status) {
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        default => 'bg-yellow-100 text-yellow-700',
    };

    $iconClass = match ($status) {
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        default => 'bg-yellow-100 text-yellow-700',
    };

    $iconText = match ($status) {
        'approved' => '✓',
        'rejected' => '×',
        default => $number,
    };
@endphp

<div x-data="{ open: false }"
     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

    {{-- BADGE CARD HEADER --}}
    <button type="button"
            @click="open = !open"
            class="w-full p-4 flex items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition">

        <div class="flex items-center gap-3 min-w-0">

            <div class="w-8 h-8 rounded-xl {{ $iconClass }} flex items-center justify-center text-xs font-bold shrink-0">
                {{ $iconText }}
            </div>

            <div class="text-left min-w-0">

                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                    {{ $title }}
                </h3>

                <p class="text-xs text-gray-500 truncate">
                    {{ $photoCount }} foto
                    @if($firstItem?->description)
                        · Note Waspang
                    @endif
                </p>

                

            </div>

        </div>

        <div class="flex items-center gap-2 shrink-0">

            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold {{ $statusClass }}">
                {{ ucfirst($status) }}
            </span>

            <span class="text-gray-400 text-xs" x-text="open ? '▲' : '▼'"></span>

        </div>

    </button>

    {{-- DETAIL DROPDOWN --}}
    <div x-show="open"
         x-transition
         class="border-t border-gray-100 dark:border-gray-800">

        <div class="p-4">

            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                {{ $description }}
            </p>

            {{-- NOTE FROM WASPANG --}}
            @if($firstItem?->description)

                <div class="mt-3 rounded-xl bg-blue-50 border border-blue-100 p-3">

                    <p class="text-[11px] font-bold text-blue-700 mb-1">
                        Note Waspang
                    </p>

                    <p class="text-xs text-blue-900 leading-relaxed">
                        {{ $firstItem->description }}
                    </p>

                </div>

            @endif

            {{-- REJECT NOTE --}}
            @if($status == 'rejected' && $firstItem?->review_note)

                <div class="mt-3 rounded-xl bg-red-50 border border-red-100 p-3">

                    <p class="text-[11px] font-bold text-red-700 mb-1">
                        Note Reject Admin
                    </p>

                    <p class="text-xs text-red-900 leading-relaxed">
                        {{ $firstItem->review_note }}
                    </p>

                </div>

            @endif

            {{-- FOTO --}}
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 mt-4">

                @forelse($items as $evidence)

                    <a href="{{ asset('storage/' . $evidence->file_path) }}"
                       target="_blank"
                       class="aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800">

                        <img src="{{ asset('storage/' . $evidence->file_path) }}"
                             class="w-full h-full object-cover hover:scale-105 transition-all duration-200">

                    </a>

                @empty

                    <div class="col-span-3 text-xs text-gray-500">
                        Belum ada foto diupload.
                    </div>

                @endforelse

            </div>

        </div>

        {{-- ACTION --}}
        @if($firstItem)

            <div class="border-t border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950 p-4">

                @if($status == 'approved')

                    <div class="flex items-center justify-between gap-3">

                        <div>
                            <p class="text-xs font-bold text-green-700">
                                Eviden disetujui
                            </p>

                            <p class="text-[11px] text-gray-500 mt-0.5">
                                Status eviden sudah approved.
                            </p>
                        </div>

                        <form method="POST"
                              action="{{ route('admin.evidences.reset', $firstItem->id_evidence) }}">
                            @csrf

                            <button class="h-9 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                                Atur Ulang
                            </button>
                        </form>

                    </div>

                @elseif($status == 'rejected')

                    <div class="flex items-center justify-between gap-3">

                        <div>
                            <p class="text-xs font-bold text-red-700">
                                Eviden ditolak
                            </p>

                            <p class="text-[11px] text-gray-500 mt-0.5">
                                Perlu Upload Ulang / Perbaikan Eviden.
                            </p>
                        </div>

                        <form method="POST"
                              action="{{ route('admin.evidences.reset', $firstItem->id_evidence) }}">
                            @csrf

                            <button class="h-9 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                                Atur Ulang
                            </button>
                        </form>

                    </div>

                @else

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        {{-- REJECT FORM --}}
                        <form method="POST"
                              action="{{ route('admin.evidences.reject', $firstItem->id_evidence) }}"
                              class="md:col-span-2">
                            @csrf

                            <textarea name="review_note"
                                      rows="2"
                                      required
                                      placeholder="Tuliskan reason reject ..."
                                      class="w-full h-20 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm resize-none"></textarea>

                            <button class="mt-2 h-9 w-full rounded-xl border border-red-300 text-red-600 text-xs font-bold hover:bg-red-50">
                                × Reject
                            </button>

                        </form>

                        {{-- APPROVE FORM --}}
                        <form method="POST"
                              action="{{ route('admin.evidences.approve', $firstItem->id_evidence) }}">
                            @csrf

                            <button class="w-full h-[116px] rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold">
                                ✓ Approve
                            </button>

                        </form>

                    </div>

                @endif

            </div>

        @endif

    </div>

</div>