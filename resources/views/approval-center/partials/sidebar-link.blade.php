@php
    $approvalCenterActive = request()->routeIs('approval-center.*');
    $approvalBadge = auth()->user()?->role === 'admin' ? ($adminApprovalInboxCount ?? 0) : 0;
@endphp
<a href="{{ route('approval-center.index') }}"
   class="relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition
   {{ $approvalCenterActive ? 'bg-slate-100 text-slate-950 dark:bg-slate-800 dark:text-white' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
    @if($approvalCenterActive)
        <span class="absolute bottom-2 left-0 top-2 w-1 rounded-r-full bg-slate-800 dark:bg-slate-200"></span>
    @endif
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $approvalCenterActive ? 'bg-blue-100 dark:bg-blue-600/60' : 'bg-gray-100 dark:bg-gray-800' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    </div>
    <span class="min-w-0 flex-1">Approval Center</span>
    @if($approvalBadge > 0)
        <span class="rounded bg-red-600 px-2 py-0.5 text-[10px] font-black text-white dark:bg-red-400 dark:text-white-900">{{ $approvalBadge > 99 ? '99+' : $approvalBadge }}</span>
    @endif
</a>
