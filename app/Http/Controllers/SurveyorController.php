<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\SiteSurvey;
use App\Models\SiteSurveyPoint;
use App\Models\SiteSurveyRoute;
use App\Models\User;
use App\Services\ProjectActivityService;
use App\Services\SiteSurveyKmlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SurveyorController extends Controller
{
    /**
     * Role yang boleh mengakses fitur Surveyor.
     * Fitur Survey Lapangan sekarang menyatu ke role Waspang (tidak perlu
     * lagi role terpisah sdi_surveyor untuk akun baru); 'sdi_surveyor' tetap
     * dipertahankan di sini agar akun lama yang masih memakai role tsb tidak
     * kehilangan akses.
     */
    // FIX (2026-09-08): 'officer' ditambahkan -- sidebar officer sendiri
    // sudah link ke admin.site-surveys.index (menu turunan yang sama dgn
    // GisCadController), tapi constant ini belum pernah diupdate saat role
    // officer ditambahkan.
    private const ALLOWED_ROLES = ['sdi_surveyor', 'admin', 'sdi', 'waspang', 'superadmin', 'super_tif', 'officer'];

    private function guardAccess(): User
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, self::ALLOWED_ROLES, true)) {
            abort(403, 'Anda tidak memiliki akses ke fitur Survey Lapangan.');
        }

        return $user;
    }

    /**
     * Pastikan survey yang diakses memang milik surveyor tsb (kecuali admin).
     */
    private function findSurveyOrFail($id, User $user): SiteSurvey
    {
        $query = SiteSurvey::query();

        if ($user->role === 'waspang') {
            $query->where(function ($surveyQuery) use ($user) {
                $surveyQuery->where('surveyor_id', $user->id_user)
                    ->orWhereHas('project.assignments', function ($assignmentQuery) use ($user) {
                        $assignmentQuery->where('waspang_id', $user->id_user);
                    });
            });
        } elseif (! in_array($user->role, ['admin', 'sdi', 'superadmin', 'super_tif'], true)) {
            $query->where('surveyor_id', $user->id_user);
        }

        return $query->findOrFail($id);
    }

    private function ensureSurveyIsEditable(SiteSurvey $survey): void
    {
        abort_if($survey->isCompleted(), 409, 'Survey yang sudah selesai dikunci sebagai histori dan tidak dapat diubah.');
    }

    /*
    |--------------------------------------------------------------------------
    | LIST & FORM
    |--------------------------------------------------------------------------
    */

    /**
     * Query dasar site survey, dibatasi ke milik sendiri kecuali admin/sdi.
     */
    private function baseSurveyQuery(User $user)
    {
        $query = SiteSurvey::with(['project', 'surveyor'])
            ->withCount(['points', 'routes']);

        if (! in_array($user->role, ['admin', 'sdi', 'superadmin', 'super_tif'], true)) {
            $query->where('surveyor_id', $user->id_user);
        }

        return $query;
    }

    /**
     * Halaman List & Search Survey Milik Surveyor.
     */
    public function index(Request $request)
    {
        $user = $this->guardAccess();

        $query = $this->baseSurveyQuery($user);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($p) use ($search) {
                        $p->where('project_name', 'like', "%{$search}%")
                            ->orWhere('pid', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        $surveys = $query->latest('updated_at')->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
        ];

        return view('surveyor.index', compact('surveys', 'stats'));
    }

    /*
    |--------------------------------------------------------------------------
    | TAMPILAN ADMIN / SDI (DESKTOP) - HASIL SURVEY
    |--------------------------------------------------------------------------
    */

    public function adminIndex(Request $request)
    {
        $user = $this->guardAccess();

        $query = SiteSurvey::with(['project', 'surveyor'])
            ->withCount(['points', 'routes']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhereHas('surveyor', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('project', function ($p) use ($search) {
                        $p->where('project_name', 'like', "%{$search}%")
                            ->orWhere('pid', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        $surveys = $query->latest('updated_at')->paginate(15)->withQueryString();

        $stats = [
            'total' => SiteSurvey::count(),
            'draft' => SiteSurvey::where('status', 'draft')->count(),
            'completed' => SiteSurvey::where('status', 'completed')->count(),
        ];

        return view('admin.site-surveys.index', compact('surveys', 'stats'));
    }

    public function adminShow($id)
    {
        $user = $this->guardAccess();
        $survey = $this->findSurveyOrFail($id, $user);

        $survey->load(['points', 'routes', 'surveyor', 'project']);

        return view('admin.site-surveys.show', compact('survey'));
    }

    public function create()
    {
        $this->guardAccess();

        $projects = Project::orderByDesc('id_project')->limit(300)->get(['id_project', 'project_name', 'pid']);

        return view('surveyor.create', compact('projects'));
    }

    /**
     * Halaman Profil (detail akun + ganti password) untuk role sdi_surveyor.
     */
    public function profile()
    {
        $this->guardAccess();

        return view('surveyor.profile');
    }

    public function store(Request $request)
    {
        $user = $this->guardAccess();

        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id_project',
            'project_name' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($user->role === 'waspang' && ! empty($validated['project_id'])) {
            $isAssigned = ProjectAssignment::query()
                ->where('project_id', $validated['project_id'])
                ->where('waspang_id', $user->id_user)
                ->exists();

            abort_unless($isAssigned, 403, 'Project ini tidak ditugaskan kepada Anda.');
        }

        $survey = SiteSurvey::create([
            'project_id' => $validated['project_id'] ?? null,
            'project_name' => $validated['project_name'] ?? null,
            'title' => $validated['title'],
            'surveyor_id' => $user->id_user,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('surveyor.show', $survey->id)
            ->with('success', 'Survey baru berhasil dibuat. Silakan mulai tagging titik di lapangan.');
    }

    /*
    |--------------------------------------------------------------------------
    | EKSEKUSI SURVEY (PETA)
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $user = $this->guardAccess();
        $survey = $this->findSurveyOrFail($id, $user);

        $survey->load(['points' => function ($q) {
            $q->orderBy('order_index')->orderBy('id_site_survey_points');
        }, 'routes' => function ($q) {
            $q->orderBy('order_index')->orderBy('id_site_survey_routes');
        }, 'project', 'surveyor']);

        $referenceSurvey = $survey->project_id
            ? SiteSurvey::query()
                ->where('project_id', $survey->project_id)
                ->where('status', 'completed')
                ->where('id_site_surveys', '!=', $survey->id)
                ->latest('completed_at')
                ->latest('id_site_surveys')
                ->first()
            : null;

        $referenceMapUrl = $referenceSurvey
            ? route('surveyor.kml', $referenceSurvey->id)
            : ($survey->project?->kml_file ? Storage::url($survey->project->kml_file) : null);

        return view('surveyor.show', compact('survey', 'referenceMapUrl'));
    }

    /*
    |--------------------------------------------------------------------------
    | POINTS (TIANG EKSISTING & CATUAN)
    |--------------------------------------------------------------------------
    */

    public function storePoint(Request $request, $id)
    {
        $user = $this->guardAccess();
        $survey = $this->findSurveyOrFail($id, $user);
        $this->ensureSurveyIsEditable($survey);

        $validated = $request->validate([
            'type' => 'required|in:tiang_eksisting,catuan',
            'catuan_type' => 'required_if:type,catuan|nullable|in:ODC,ODP,OTB,JC',
            'name' => 'nullable|string|max:150',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('site-surveys/'.$survey->id.'/points', 'public');
        }

        $nextOrder = (int) $survey->points()->max('order_index') + 1;

        $point = SiteSurveyPoint::create([
            'site_survey_id' => $survey->id,
            'type' => $validated['type'],
            'catuan_type' => $validated['type'] === 'catuan' ? $validated['catuan_type'] : null,
            'name' => $validated['name'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'notes' => $validated['notes'] ?? null,
            'photo_path' => $photoPath,
            'order_index' => $nextOrder,
            'created_by' => $user->id_user,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'point' => $point,
                'photo_url' => $photoPath ? Storage::url($photoPath) : null,
            ]);
        }

        return back()->with('success', 'Titik berhasil ditandai.');
    }

    public function updatePoint(Request $request, $pointId)
    {
        $user = $this->guardAccess();
        $point = SiteSurveyPoint::with('survey')->findOrFail($pointId);

        if (! in_array($user->role, ['admin', 'sdi', 'superadmin', 'super_tif'], true) && $point->survey->surveyor_id !== $user->id_user) {
            abort(404);
        }
        $this->ensureSurveyIsEditable($point->survey);

        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
        ]);

        $point->update($validated);

        return response()->json(['success' => true, 'point' => $point]);
    }

    public function destroyPoint($pointId)
    {
        $user = $this->guardAccess();
        $point = SiteSurveyPoint::with('survey')->findOrFail($pointId);

        if (! in_array($user->role, ['admin', 'sdi', 'superadmin', 'super_tif'], true) && $point->survey->surveyor_id !== $user->id_user) {
            abort(404);
        }
        $this->ensureSurveyIsEditable($point->survey);

        if ($point->photo_path) {
            Storage::disk('public')->delete($point->photo_path);
        }

        $point->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Titik berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | RUTE KABEL
    |--------------------------------------------------------------------------
    */

    public function storeRoute(Request $request, $id)
    {
        $user = $this->guardAccess();
        $survey = $this->findSurveyOrFail($id, $user);
        $this->ensureSurveyIsEditable($survey);

        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'path' => 'required|array|min:2',
            'path.*' => 'required|array|size:2',
            'path.*.0' => 'required|numeric|between:-90,90',
            'path.*.1' => 'required|numeric|between:-180,180',
        ]);

        $nextOrder = (int) $survey->routes()->max('order_index') + 1;

        $route = SiteSurveyRoute::create([
            'site_survey_id' => $survey->id,
            'name' => $validated['name'] ?: ('Rute Kabel '.$nextOrder),
            'path' => $validated['path'],
            'distance_meters' => SiteSurveyRoute::calculateDistanceMeters($validated['path']),
            'order_index' => $nextOrder,
        ]);

        return response()->json(['success' => true, 'route' => $route]);
    }

    public function updateRoute(Request $request, $routeId)
    {
        $user = $this->guardAccess();
        $route = SiteSurveyRoute::with('survey')->findOrFail($routeId);

        if (! in_array($user->role, ['admin', 'sdi', 'superadmin', 'super_tif'], true) && $route->survey->surveyor_id !== $user->id_user) {
            abort(404);
        }
        $this->ensureSurveyIsEditable($route->survey);

        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'path' => 'required|array|min:2',
            'path.*' => 'required|array|size:2',
            'path.*.0' => 'required|numeric|between:-90,90',
            'path.*.1' => 'required|numeric|between:-180,180',
        ]);

        $route->update([
            'name' => $validated['name'] ?: $route->name,
            'path' => $validated['path'],
            'distance_meters' => SiteSurveyRoute::calculateDistanceMeters($validated['path']),
        ]);

        return response()->json(['success' => true, 'route' => $route]);
    }

    public function destroyRoute($routeId)
    {
        $user = $this->guardAccess();
        $route = SiteSurveyRoute::with('survey')->findOrFail($routeId);

        if (! in_array($user->role, ['admin', 'sdi', 'superadmin', 'super_tif'], true) && $route->survey->surveyor_id !== $user->id_user) {
            abort(404);
        }
        $this->ensureSurveyIsEditable($route->survey);

        $route->delete();

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | ENDING SITE
    |--------------------------------------------------------------------------
    */

    public function setEndingSite(Request $request, $id)
    {
        $user = $this->guardAccess();
        $survey = $this->findSurveyOrFail($id, $user);
        $this->ensureSurveyIsEditable($survey);

        $validated = $request->validate([
            'ending_site_lat' => 'required|numeric|between:-90,90',
            'ending_site_lng' => 'required|numeric|between:-180,180',
            'ending_site_name' => 'nullable|string|max:150',
        ]);

        $survey->update($validated);

        return response()->json(['success' => true, 'survey' => $survey]);
    }

    /*
    |--------------------------------------------------------------------------
    | SELESAIKAN & HAPUS SURVEY
    |--------------------------------------------------------------------------
    */

    public function complete($id, SiteSurveyKmlService $kmlService)
    {
        $user = $this->guardAccess();
        $survey = $this->findSurveyOrFail($id, $user);
        $this->ensureSurveyIsEditable($survey);

        $survey->load(['points', 'routes', 'surveyor', 'project']);

        if ($survey->points->isEmpty() || $survey->routes->isEmpty()) {
            return back()->with('error', 'Tag minimal satu titik dan buat minimal satu rute kabel sebelum menyelesaikan redesign.');
        }

        if ($user->role === 'waspang' && $survey->project_id) {
            $isAssigned = ProjectAssignment::query()
                ->where('project_id', $survey->project_id)
                ->where('waspang_id', $user->id_user)
                ->exists();
            abort_unless($isAssigned, 403, 'Project ini tidak lagi ditugaskan kepada Anda.');
        }

        $kmlContent = $kmlService->build($survey);
        $fileName = $kmlService->fileName($survey);
        $path = 'site-surveys/'.$survey->id.'/'.$fileName;

        Storage::disk('public')->put($path, $kmlContent);

        $survey->update([
            'status' => 'completed',
            'completed_at' => now(),
            'kml_path' => $path,
        ]);

        if ($survey->project_id) {
            $projectLops = $survey->project?->lops()->limit(2)->get() ?? collect();
            $lopId = $projectLops->count() === 1 ? $projectLops->first()->id_lop : null;

            ProjectActivityService::log([
                'project_id' => $survey->project_id,
                'lop_id' => $lopId,
                'activity_type' => 'survey_redesign_completed',
                'title' => 'Redesign Survey Disimpan',
                'description' => 'Waspang menyelesaikan redesign peta. Versi sebelumnya tetap disimpan sebagai histori.',
                'stage' => 'survey',
                'meta' => [
                    'site_survey_id' => $survey->id,
                    'kml_path' => $path,
                ],
            ]);
        }

        if ($user->role === 'waspang' && $survey->project_id) {
            return redirect()->route('waspang.projects.persiapan', $survey->project_id)
                ->with('success', 'Redesign berhasil disimpan. Peta terbaru sudah aktif; konfirmasi dengan tombol Sesuai.');
        }

        return back()->with('success', 'Survey berhasil diselesaikan! File KML sudah siap diunduh.');
    }

    public function destroy($id)
    {
        $user = $this->guardAccess();
        $survey = $this->findSurveyOrFail($id, $user);
        $this->ensureSurveyIsEditable($survey);

        Storage::disk('public')->deleteDirectory('site-surveys/'.$survey->id);

        $survey->delete();

        return redirect()->route('surveyor.index')->with('success', 'Survey berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD KML
    |--------------------------------------------------------------------------
    */

    public function downloadKml($id, SiteSurveyKmlService $kmlService)
    {
        $user = $this->guardAccess();
        $survey = $this->findSurveyOrFail($id, $user);

        $survey->load(['points', 'routes', 'surveyor']);

        $kmlContent = $kmlService->build($survey);
        $fileName = $kmlService->fileName($survey);

        return response($kmlContent, 200, [
            'Content-Type' => 'application/vnd.google-earth.kml+xml',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }
}
