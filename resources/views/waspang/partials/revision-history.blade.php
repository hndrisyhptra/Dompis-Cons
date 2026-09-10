{{-- HISTORI REVISI --}}
@if(isset($histories) && $histories->count())

    <div class="mb-3 rounded-2xl border border-red-100 bg-red-50/50 p-3">

        <p class="text-[11px] font-black text-red-700 mb-2 flex items-center gap-1.5">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Histori Revisi
        </p>

        <div class="space-y-2.5">

            @foreach($histories->sortByDesc('created_at') as $history)

                <div class="border-l-2 border-red-300 pl-3">

                    <p class="text-[11px] text-gray-500 font-semibold">
                        {{ $history->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                    </p>

                    <p class="text-xs text-gray-700 mt-0.5 leading-relaxed">
                        {{ $history->review_note }}
                    </p>

                </div>

            @endforeach

        </div>

    </div>

@endif