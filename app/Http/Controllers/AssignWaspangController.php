<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AssignWaspangController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $waspangs = User::where('role', 'waspang')
            ->where('status', 'active')
            ->with([
                'assignments.project.evidences',
                'assignments.project.boqItems',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->get()
            ->map(function ($waspang) {
                $activeAssignments = $waspang->assignments
                    ->unique('project_id')
                    ->filter(function ($assignment) {
                        $project = $assignment->project;

                        if (!$project) {
                            return false;
                        }

                        return !$this->isProjectReadyUt($project);
                    })
                    ->values();

                $waspang->active_assignments = $activeAssignments;
                $waspang->active_project_count = $activeAssignments->count();

                return $waspang;
            })
            ->sortByDesc('active_project_count')
            ->values();

        $page = request()->get('page', 1);
        $perPage = 10;

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $waspangs->forPage($page, $perPage),
            $waspangs->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('admin.assign-waspang.index', [
            'waspangs' => $paginated,
            'search' => $search,
        ]);
    }
    

    public function history(Request $request, $id)
    {
        $search = $request->search;

        $waspang = User::where('role', 'waspang')
            ->where('id_user', $id)
            ->with([
                'assignments.project.lop',
                'assignments.project.evidences',
                'assignments.project.boqItems',
            ])
            ->firstOrFail();

        $assignments = $waspang->assignments
            ->unique('project_id')
            ->filter(function ($assignment) use ($search) {
                if (!$search) {
                    return true;
                }

                $project = $assignment->project;

                if (!$project) {
                    return false;
                }

                return str_contains(strtolower($project->project_name ?? ''), strtolower($search))
                    || str_contains(strtolower($project->pid ?? ''), strtolower($search))
                    || str_contains(strtolower($project->pid_sap ?? ''), strtolower($search))
                    || str_contains(strtolower($project->lop?->sto ?? ''), strtolower($search))
                    || str_contains(strtolower($project->lop?->branch ?? ''), strtolower($search))
                    || str_contains(strtolower($project->lop?->mitra_name ?? ''), strtolower($search));
            })
            ->sortByDesc('created_at')
            ->values();

        return view('admin.assign-waspang.history', compact(
            'waspang',
            'assignments',
            'search'
        ));
    }

    private function isProjectReadyUt($project): bool
    {
        $evidences = $project->evidences ?? collect();
        $boqItems = $project->boqItems ?? collect();

        $persiapanDone =
            $evidences->where('stage', 'persiapan')
                ->where('evidence_type', 'barang_tiba')
                ->where('status', 'approved')
                ->count() > 0
            &&
            $evidences->where('stage', 'persiapan')
                ->where('evidence_type', 'perizinan')
                ->where('status', 'approved')
                ->count() > 0;

        $materialBoqItems = $boqItems->filter(function ($boq) {
            return str_starts_with($boq->designator, 'M-');
        });

        $boqTotal = $materialBoqItems->count();

        $boqApproved = $materialBoqItems->filter(function ($boq) use ($evidences) {
            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->where('status', 'approved')
                ->count() > 0;
        })->count();

        $instalasiDone = $boqTotal > 0 && $boqApproved >= $boqTotal;

        $finishingDone =
            $evidences->where('stage', 'finishing')
                ->where('status', 'approved')
                ->count() > 0;

        return $persiapanDone && $instalasiDone && $finishingDone;
    }
}

