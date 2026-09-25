<?php

namespace App\Http\Controllers;

use App\Services\ApprovalCenterService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ApprovalCenterController extends Controller
{
    public function index(Request $request, ApprovalCenterService $service)
    {
        $user = $request->user();
        $isAdmin = $user?->role === 'admin';
        $scope = $isAdmin ? $request->input('scope', 'mine') : 'all';

        if (! in_array($scope, ['all', 'mine'], true) || ($scope === 'mine' && ! $isAdmin)) {
            $scope = 'all';
        }

        $allQueue = $service->queue($scope === 'mine' ? (int) $user->id_user : null);
        $availableBranches = $allQueue->pluck('branch')->filter(fn ($branch) => $branch !== '-')
            ->unique()->sort()->values();
        $availableStages = $allQueue->flatMap(fn (array $item) => $item['stages'])
            ->unique('code')->sortBy('label')->values();

        $filtered = $this->applyFilters($allQueue, $request);
        $summary = [
            'lop_count' => $filtered->count(),
            'evidence_count' => $filtered->sum('pending_count'),
            'overdue_count' => $filtered->where('aging_bucket', 'overdue')->count(),
            'branch_count' => $filtered->pluck('branch')->filter(fn ($branch) => $branch !== '-')->unique()->count(),
        ];

        $stageSummary = $filtered->flatMap(fn (array $item) => $item['stages'])
            ->groupBy('code')
            ->map(fn (Collection $stages) => [
                'label' => $stages->first()['label'],
                'count' => $stages->sum('count'),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 12;
        $items = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('approval-center.index', [
            'layout' => $this->layoutForRole((string) $user?->role),
            'items' => $items,
            'summary' => $summary,
            'stageSummary' => $stageSummary,
            'availableBranches' => $availableBranches,
            'availableStages' => $availableStages,
            'scope' => $scope,
            'isAdmin' => $isAdmin,
            'canReviewPt3' => in_array($user?->role, ['admin', 'superadmin', 'super_tif'], true),
            'canReviewPt2' => in_array($user?->role, ['admin', 'superadmin', 'super_tif', 'officer'], true),
        ]);
    }

    private function applyFilters(Collection $queue, Request $request): Collection
    {
        $source = $request->input('source', 'all');
        $branch = trim((string) $request->input('branch'));
        $stage = trim((string) $request->input('stage'));
        $aging = $request->input('aging', 'all');
        $search = mb_strtolower(trim((string) $request->input('search')));

        return $queue
            ->when(in_array($source, ['pt2', 'pt3'], true), fn (Collection $items) => $items
                ->where('source', $source))
            ->when($branch !== '', fn (Collection $items) => $items
                ->filter(fn (array $item) => strcasecmp((string) $item['branch'], $branch) === 0))
            ->when($stage !== '', fn (Collection $items) => $items
                ->filter(fn (array $item) => $item['stages']->contains('code', $stage)))
            ->when(in_array($aging, ['fresh', 'warning', 'overdue'], true), fn (Collection $items) => $items
                ->where('aging_bucket', $aging))
            ->when($search !== '', fn (Collection $items) => $items->filter(function (array $item) use ($search): bool {
                $haystack = mb_strtolower(implode(' ', [
                    $item['lop_name'], $item['project_name'], $item['pid'], $item['branch'],
                    $item['sto'], $item['program'], $item['admin_name'], $item['uploaders']->implode(' '),
                ]));

                return str_contains($haystack, $search);
            }))
            ->values();
    }

    private function layoutForRole(string $role): string
    {
        return match ($role) {
            'pm', 'tif' => 'layouts.pm',
            'sdi' => 'layouts.sdi',
            'waspang' => 'layouts.waspang',
            'teknisi' => 'layouts.teknisi',
            'sdi_surveyor' => 'layouts.surveyor',
            default => 'layouts.admin',
        };
    }
}
