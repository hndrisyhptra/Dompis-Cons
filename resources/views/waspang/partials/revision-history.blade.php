{{-- HISTORI REVISI --}}
@if(isset($histories) && $histories->count())

    <div class="mb-3 rounded-xl border border-gray-200 bg-gray-50 p-3">

        <p class="text-[11px] font-bold text-gray-700 mb-2">
            Histori Revisi
        </p>

        <div class="space-y-2">

            @foreach($histories->sortByDesc('created_at') as $history)

                <div class="border-l-2 border-red-400 pl-3">

                    <p class="text-[11px] text-gray-500">
                        {{ $history->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                    </p>

                    <p class="text-xs text-gray-700 mt-1 leading-relaxed">
                        {{ $history->review_note }}
                    </p>

                </div>

            @endforeach

        </div>

    </div>

@endif