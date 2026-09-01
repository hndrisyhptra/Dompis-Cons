<?php

namespace App\Http\Controllers;

use App\Models\GisCadExport;
use App\Models\SiteSurvey;
use App\Models\User;
use App\Services\Gis\GisToCadExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fitur "GIS to CAD Generator" (KML/KMZ atau data Survey Lapangan
 * existing -> DXF AutoCAD). Menu turunan dari Survey Lapangan, jadi
 * aturan akses & pola kontrol yang dipakai sengaja disamakan persis
 * dengan SurveyorController.
 */
class GisCadController extends Controller
{
    private const ALLOWED_ROLES = ['sdi_surveyor', 'admin', 'sdi', 'waspang', 'superadmin'];

    private function guardAccess(): User
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, self::ALLOWED_ROLES, true)) {
            abort(403, 'Anda tidak memiliki akses ke fitur GIS to CAD Generator.');
        }

        return $user;
    }

    /**
     * Pastikan export yang diakses memang milik user tsb (kecuali admin/sdi).
     */
    private function findExportOrFail(string $uuid, User $user): GisCadExport
    {
        $query = GisCadExport::query()->where('uuid', $uuid);

        if (!in_array($user->role, ['admin', 'sdi', 'superadmin'], true)) {
            $query->where('requested_by', $user->id_user);
        }

        return $query->firstOrFail();
    }

    /**
     * Fitur ini punya 2 tampilan berbeda:
     * - resources/views/gis-cad/*        -> mobile, dipakai SDI Surveyor/Waspang (route prefix gis-cad.*)
     * - resources/views/admin/gis-cad/*  -> desktop, dipakai dari sidebar Admin (route prefix admin.gis-cad.*)
     *
     * Business logic (controller & service) SAMA PERSIS untuk keduanya - yang
     * beda cuma template mana yang dirender & ke route mana redirect
     * mengarah. Jadi tidak ada logic yang di-duplikasi.
     */
    private function isAdminContext(): bool
    {
        return request()->routeIs('admin.gis-cad.*');
    }

    private function viewName(string $suffix): string
    {
        return ($this->isAdminContext() ? 'admin.gis-cad.' : 'gis-cad.') . $suffix;
    }

    private function routeName(string $suffix): string
    {
        return ($this->isAdminContext() ? 'admin.gis-cad.' : 'gis-cad.') . $suffix;
    }

    /*
    |--------------------------------------------------------------------------
    | LIST & FORM
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $user = $this->guardAccess();

        $query = GisCadExport::with(['survey', 'project', 'requester']);

        if (!in_array($user->role, ['admin', 'sdi', 'superadmin'], true)) {
            $query->where('requested_by', $user->id_user);
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_file_name', 'like', "%{$search}%")
                    ->orWhereHas('survey', function ($s) use ($search) {
                        $s->where('title', 'like', "%{$search}%")
                          ->orWhere('project_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('requester', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $exports = $query->latest('updated_at')->paginate($this->isAdminContext() ? 15 : 10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'processing' => (clone $query)->whereIn('status', ['draft', 'queued', 'processing'])->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
        ];

        return view($this->viewName('index'), compact('exports', 'stats'));
    }

    public function create()
    {
        $user = $this->guardAccess();

        $surveysQuery = SiteSurvey::with('project')->orderByDesc('id_site_surveys');

        if (!in_array($user->role, ['admin', 'sdi', 'superadmin'], true)) {
            $surveysQuery->where('surveyor_id', $user->id_user);
        }

        $surveys = $surveysQuery->limit(200)->get([
            'id_site_surveys', 'project_id', 'project_name', 'title', 'status', 'surveyor_id',
        ]);

        $projects = \App\Models\Project::orderByDesc('id_project')->limit(300)->get(['id_project', 'project_name', 'pid']);

        return view($this->viewName('create'), compact('surveys', 'projects'));
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 1: INGEST
    |--------------------------------------------------------------------------
    */

    public function storeUpload(Request $request, GisToCadExportService $service)
    {
        $user = $this->guardAccess();

        $validated = $request->validate([
            'file' => 'required|file|max:20480', // 20 MB
            'project_id' => 'nullable|exists:projects,id_project',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: '');

        if (!in_array($extension, ['kml', 'kmz'], true)) {
            return back()
                ->withInput()
                ->with('error', 'Format file tidak didukung. Hanya file .kml atau .kmz yang bisa diupload.');
        }

        try {
            $export = $service->ingestUpload($file, $user, $validated['project_id'] ?? null);
        } catch (RuntimeException $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Gagal membaca file KML/KMZ: ' . $e->getMessage());
        }

        return redirect()
            ->route($this->routeName('review'), $export->uuid)
            ->with('success', 'File berhasil dibaca. Silakan cek klasifikasi tiap titik sebelum generate DXF.');
    }

    public function storeFromSurvey(Request $request, $surveyId, GisToCadExportService $service)
    {
        $user = $this->guardAccess();

        $surveyQuery = SiteSurvey::query();
        if (!in_array($user->role, ['admin', 'sdi', 'superadmin'], true)) {
            $surveyQuery->where('surveyor_id', $user->id_user);
        }
        $survey = $surveyQuery->findOrFail($surveyId);

        try {
            $export = $service->ingestFromSurvey($survey, $user);
        } catch (RuntimeException $e) {
            report($e);

            return back()->with('error', 'Gagal menyiapkan data survey: ' . $e->getMessage());
        }

        return redirect()
            ->route($this->routeName('review'), $export->uuid)
            ->with('success', 'Data survey berhasil disiapkan. Silakan pilih template export.');
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 2: REVIEW
    |--------------------------------------------------------------------------
    */

    public function review($uuid, GisToCadExportService $service)
    {
        $user = $this->guardAccess();
        $export = $this->findExportOrFail($uuid, $user);

        if (!$export->isDraft()) {
            return redirect()->route($this->routeName('show'), $export->uuid);
        }

        $dataset = $service->readDataset($export);

        return view($this->viewName('review'), compact('export', 'dataset'));
    }

    public function updateReview(Request $request, $uuid, GisToCadExportService $service)
    {
        $user = $this->guardAccess();
        $export = $this->findExportOrFail($uuid, $user);

        $validated = $request->validate([
            'overrides' => 'nullable|array',
            'overrides.*.ref' => 'required|string',
            'overrides.*.type' => 'required|string|in:tiang,odp,odc,otb,jc,ending_site',
            'overrides.*.name' => 'nullable|string|max:150',
            'remove' => 'nullable|array',
            'remove.*' => 'string',
        ]);

        try {
            $service->updateDataset(
                $export,
                $validated['overrides'] ?? [],
                $validated['remove'] ?? []
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Koreksi klasifikasi titik berhasil disimpan.');
    }

    public function confirm(Request $request, $uuid, GisToCadExportService $service)
    {
        $user = $this->guardAccess();
        $export = $this->findExportOrFail($uuid, $user);

        $validated = $request->validate([
            'template' => 'required|in:standard_fttx,custom',
        ]);

        try {
            $service->confirmAndQueue($export, $validated['template']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route($this->routeName('show'), $export->uuid)
            ->with('success', 'Export masuk antrean. Halaman ini akan otomatis update begitu DXF selesai digenerate.');
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3: STATUS & HASIL
    |--------------------------------------------------------------------------
    */

    public function show($uuid)
    {
        $user = $this->guardAccess();
        $export = $this->findExportOrFail($uuid, $user);

        return view($this->viewName('show'), compact('export'));
    }

    /**
     * Endpoint JSON untuk polling status (dipanggil dari halaman show via
     * JS setInterval), pola sama seperti ImportController::importPidStatus().
     */
    public function status($uuid)
    {
        $user = $this->guardAccess();
        $export = $this->findExportOrFail($uuid, $user);

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $export->uuid,
                'status' => $export->status,
                'stage' => $export->current_stage,
                'points_count' => (int) $export->points_count,
                'polylines_count' => (int) $export->polylines_count,
                'utm_zone' => $export->utm_zone,
                'error_message' => $export->error_message,
                'has_dxf' => (bool) $export->dxf_path,
                'has_bom' => (bool) $export->bom_path,
                'started_at' => optional($export->started_at)?->format('Y-m-d H:i:s'),
                'finished_at' => optional($export->finished_at)?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD
    |--------------------------------------------------------------------------
    */

    public function downloadDxf($uuid)
    {
        $user = $this->guardAccess();
        $export = $this->findExportOrFail($uuid, $user);

        if (!$export->dxf_path || !Storage::disk($export->disk)->exists($export->dxf_path)) {
            abort(404, 'File DXF belum tersedia.');
        }

        return Storage::disk($export->disk)->download($export->dxf_path, $this->downloadFileName($export, 'dxf'));
    }

    public function downloadBom($uuid)
    {
        $user = $this->guardAccess();
        $export = $this->findExportOrFail($uuid, $user);

        if (!$export->bom_path || !Storage::disk($export->disk)->exists($export->bom_path)) {
            abort(404, 'File BOM belum tersedia.');
        }

        return Storage::disk($export->disk)->download($export->bom_path, $this->downloadFileName($export, 'xlsx', '-bom'));
    }

    private function downloadFileName(GisCadExport $export, string $extension, string $suffix = ''): string
    {
        $base = $export->original_file_name
            ? pathinfo($export->original_file_name, PATHINFO_FILENAME)
            : ($export->survey?->displayTitle() ?? 'gis-cad-export');

        $slug = Str::slug($base) ?: 'gis-cad-export';

        return $slug . $suffix . '-' . substr($export->uuid, 0, 8) . '.' . $extension;
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy($uuid)
    {
        $user = $this->guardAccess();
        $export = $this->findExportOrFail($uuid, $user);

        Storage::disk($export->disk)->deleteDirectory('gis-cad/' . $export->uuid);

        $export->delete();

        return redirect()->route($this->routeName('index'))->with('success', 'Riwayat export berhasil dihapus.');
    }
}
