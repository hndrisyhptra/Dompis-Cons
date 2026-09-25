@if(auth()->user()?->role === 'admin' && session('admin_approval_alert'))
    <style>[x-cloak] { display: none !important; }</style>
    @php $approvalAlert = session('admin_approval_alert'); @endphp

    <div x-data="{ open: true }"
         x-show="open"
         x-cloak
         @keydown.escape.window="open = false"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="open = false"></div>

        <section x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="relative w-full max-w-lg overflow-hidden rounded-lg border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Inbox Approval</p>
                        <h2 class="mt-1 text-base font-bold text-slate-950 dark:text-white">Eviden menunggu review Anda</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Hanya menampilkan project dan LOP yang di-assign kepada Anda.
                        </p>
                    </div>
                </div>
                <button type="button" @click="open = false" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-lg text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800" aria-label="Tutup">&times;</button>
            </header>

            <div class="space-y-4 p-5">
                <div class="grid grid-cols-3 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                    <div class="bg-white px-3 py-3 text-center dark:bg-slate-900">
                        <p class="text-xl font-bold text-slate-950 dark:text-white">{{ $approvalAlert['lop_count'] }}</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-400">LOP</p>
                    </div>
                    <div class="border-x border-slate-200 bg-white px-3 py-3 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-xl font-bold text-slate-950 dark:text-white">{{ $approvalAlert['evidence_count'] }}</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-400">Eviden</p>
                    </div>
                    <div class="bg-white px-3 py-3 text-center dark:bg-slate-900">
                        <p class="text-xl font-bold text-slate-950 dark:text-white">{{ $approvalAlert['urgent_count'] }}</p>
                        <p class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-400">Lewat 48 Jam</p>
                    </div>
                </div>

                @if(!empty($approvalAlert['previews']))
                    <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                        <div class="border-b border-slate-200 bg-slate-50 px-3 py-2 text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:bg-slate-800">Antrean Terlama</div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($approvalAlert['previews'] as $preview)
                                <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-slate-800 dark:text-white">{{ $preview['lop_name'] }}</p>
                                        <p class="mt-0.5 text-[10px] text-slate-400">{{ $preview['source_label'] }} &middot; {{ $preview['age_label'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded border border-slate-200 bg-slate-50 px-2 py-1 text-[9px] font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $preview['pending_count'] }} file</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="open = false" class="h-10 rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">Nanti</button>
                    <a href="{{ route('approval-center.index', ['scope' => 'mine']) }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-900 px-4 text-xs font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                        Buka Inbox Approval
                    </a>
                </div>
            </div>
        </section>
    </div>
@endif
