@php
    $activeTab = $activeTab ?? 'report';
    $summaryDateValue = isset($summaryDate) ? $summaryDate->toDateString() : now()->toDateString();
@endphp

<div class="inline-flex w-full sm:w-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-100/80 dark:bg-gray-950/70 p-1">
    <a href="{{ route($reportRouteName, ['tab' => 'report']) }}"
       class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-bold transition
              {{ $activeTab === 'report'
                    ? 'bg-white dark:bg-gray-800 text-blue-700 dark:text-blue-300 shadow-sm'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 16 4-4 4 4 5-6"/></svg>
        Report Deployment
    </a>

    <a href="{{ route($reportRouteName, ['tab' => 'summary', 'date' => $summaryDateValue]) }}"
       class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-bold transition
              {{ $activeTab === 'summary'
                    ? 'bg-white dark:bg-gray-800 text-blue-700 dark:text-blue-300 shadow-sm'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-summary preview-icon"><path d="M15 4H7"/><path d="m18 16 3 3-3 3"/><path d="M3 4v13a2 2 0 0 0 2 2h16"/><path d="M7 14h7"/><path d="M7 9h12"/></svg>
        Summary Deployment
    </a>
</div>
