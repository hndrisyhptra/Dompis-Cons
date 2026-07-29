@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 px-4 py-4 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- HEADER & ACTION BAR --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase tracking-wider">Khusus PT2</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Approval Eviden PT2
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Tinjau kelengkapan Survey, Redaman, Dismantle, dan Mancore dari Teknisi.
            </p>
        </div>

        {{-- SEARCH BOX --}}
        <div class="w-full md:w-80 relative">
            <form method="GET" action="{{ route('admin.pt2.approval') }}">
                <input type="hidden" name="status_filter" value="{{ request('status_filter', 'waiting_ut') }}">
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari LOP / Project PT2..."
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 pl-10 text-xs font-semibold shadow-sm focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</div>
            </form>
        </div>
    </div>

    {{-- SYSTEM TABS FILTER STATUS --}}
    <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-px overflow-x-auto">
        @php $currentFilter = request('status_filter', 'pending'); @endphp
        
        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'pending']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'pending' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Menunggu Review
        </a>
        
        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'active']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'active' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            On Progress (Semua)
        </a>

        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'completed']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'completed' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Approved (Golive)
        </a>
    </div>

    {{-- LIST TABLE VIEW --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/50 border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="p-4 font-black w-2/6">Project PT2 Info</th>
                        <th class="p-4 font-black w-1/6">Teknisi / Waspang</th>
                        <th class="p-4 font-black w-1/4">Kelengkapan 5 Step</th>
                        <th class="p-4 font-black text-center w-1/12">Eviden</th>
                        <th class="p-4 font-black text-right w-1/6">Status & Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($projects as $project)
                        @php
                            $evidences = $project->evidences ?? collect();
                            $teknisi = optional($project->assignment)->teknisi;

                            // LOGIKA PENGECEKAN 5 STEP PT2
                            $hasSurvey = $project->pt2Survey ? 1 : 0;
                            $hasEvSurvey = $evidences->where('stage', 'persiapan')->count() > 0 ? 1 : 0;
                            $step1 = ($hasSurvey && $hasEvSurvey) ? 1 : 0;
                            
                            $step2 = $evidences->where('stage', 'instalasi')->count() > 0 ? 1 : 0;
                            
                            $step3 = $evidences->where('stage', 'finishing')->where('evidence_type', 'redaman_port')->count() > 0 ? 1 : 0;
                            
                            $step4 = \Illuminate\Support\Facades\DB::table('dismantles')->where('project_id', $project->id_project)->exists() ? 1 : 0;
                            
                            $mancore = \Illuminate\Support\Facades\DB::table('pt2_mancores')->where('project_id', $project->id_project)->first();
                            $step5 = $mancore ? 1 : 0;

                            $progress = $step1 + $step2 + $step3 + $step4 + $step5;
                            $progressPercent = ($progress / 5) * 100;

                            // Hitung status file foto (Pending/Approve/Reject)
                            $pendingCount = $evidences->where('status', 'pending')->count();
                            $approvedCount = $evidences->where('status', 'approved')->count();
                            $rejectedCount = $evidences->where('status', 'rejected')->count();
                        @endphp

                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group">
                            
                            {{-- KOLOM 1: Project Info --}}
                            <td class="p-4 align-top">
                                <div class="min-w-0">
                                    <h2 class="text-sm font-black text-slate-800 dark:text-white line-clamp-2 leading-tight">
                                        {{ $project->project_name }}
                                    </h2>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center text-[10px] font-bold font-mono text-indigo-600 bg-indigo-50 border border-indigo-100 px-1.5 py-0.5 rounded">
                                            {{ $project->lop?->id_ihld ?? '-' }}
                                        </span>
                                        <p class="text-[11px] text-slate-400 font-bold flex items-center gap-1.5">
                                            {{ $project->lop?->sto ?? '-' }}
                                        </p>
                                    </div>        
                                </div>
                            </td>

                            {{-- KOLOM 2: Teknisi --}}
                            <td class="p-4 align-top">
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase">Assigned To</p>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate mt-0.5">
                                        {{ $teknisi->name ?? 'Belum ada Teknisi' }}
                                    </p>
                                </div>
                            </td>

                            {{-- KOLOM 3: Progress 5 Step PT2 --}}
                            <td class="p-4 align-top">
                                <div class="space-y-2.5">
                                    {{-- Progress Bar --}}
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-indigo-600 rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-slate-700 w-9 text-right">{{ $progress }}/5</span>
                                    </div>
                                    
                                    {{-- Detail Angka Progress (Mini Badges) --}}
                                    <div class="flex items-center gap-1.5 text-[9px] font-bold">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 1">
                                            S1: <span class="{{ $step1 ? 'text-indigo-600' : 'text-slate-400' }}">{{ $step1 ? '✓' : '✗' }}</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 2">
                                            S2: <span class="{{ $step2 ? 'text-indigo-600' : 'text-slate-400' }}">{{ $step2 ? '✓' : '✗' }}</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 3">
                                            S3: <span class="{{ $step3 ? 'text-indigo-600' : 'text-slate-400' }}">{{ $step3 ? '✓' : '✗' }}</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 4">
                                            S4: <span class="{{ $step4 ? 'text-indigo-600' : 'text-slate-400' }}">{{ $step4 ? '✓' : '✗' }}</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 5">
                                            S5: <span class="{{ $step5 ? 'text-indigo-600' : 'text-slate-400' }}">{{ $step5 ? '✓' : '✗' }}</span>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- KOLOM 4: Status Foto --}}
                            <td class="p-4 align-top text-center">
                                <div class="flex flex-col gap-1 items-center justify-center font-mono text-[10px] font-black">
                                    <span class="w-16 py-0.5 rounded bg-amber-50 text-amber-600 border border-amber-200/40">
                                        P: {{ $pendingCount }}
                                    </span>
                                    <span class="w-16 py-0.5 rounded bg-emerald-50 text-emerald-600 border border-emerald-200/40">
                                        A: {{ $approvedCount }}
                                    </span>
                                </div>
                            </td>

                            {{-- KOLOM 5: Aksi Review --}}
                            <td class="p-4 align-top text-right">
                                <div class="flex flex-col items-end gap-2.5">
                                    <span class="inline-flex px-2.5 py-1 rounded-md text-[9px] font-extrabold tracking-widest uppercase bg-amber-100 text-amber-700">
                                        {{ $project->status == 'waiting_ut' ? 'NEED REVIEW' : $project->status }}
                                    </span>
                                    
                                    {{-- Mengarah ke halaman khusus Review PT2 --}}
                                    <a href="{{ route('admin.pt2.review', $project->id_project) }}"
                                       class="h-8 px-4 rounded-xl bg-indigo-50 border border-indigo-200 hover:bg-indigo-600 hover:text-white text-indigo-700 inline-flex items-center justify-center gap-1.5 text-xs font-black transition-all shadow-sm group">
                                        Review PT2
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-xs text-slate-400 font-medium">
                                <div class="text-3xl mb-3 opacity-50">📁</div>
                                <p>Tidak ada permohonan Approval PT2 saat ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">
        {{ $projects->links() }}
    </div>
</div>
@endsection