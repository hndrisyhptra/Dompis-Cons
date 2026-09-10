<?php

namespace App\Http\Controllers;

use App\Models\Lop;
use App\Models\ProjectStage;
use Illuminate\Http\Request;

/**
 * Kelola master tahapan alur LOP reguler (project_stages) -- pengganti enum
 * hardcode 'preparation'/'instalasi'/'finishing' lama. `code` adalah nilai
 * yang SUNGGUHAN disimpan & di-FK-kan dari lops.status_progress &
 * lops.status_progress_before_hold (lihat migration
 * 2026_09_08_090300_convert_lops_status_progress_to_project_stages.php),
 * jadi ditangani lebih hati-hati dibanding master data biasa (Package/
 * Designator dkk):
 *
 * - `code` HANYA bisa diisi saat membuat baris baru, TIDAK BISA diubah lagi
 *   sesudahnya (field read-only di form edit) -- mengubah code akan
 *   membuat LOP yang sudah memakai code lama jadi tidak sinkron, dan
 *   sebenarnya juga akan ditolak MySQL sendiri (FK RESTRICT) kalau code
 *   itu sedang dipakai.
 * - `hold` & `drop` adalah 2 baris ISTIMEWA yang dipakai LANGSUNG oleh kode
 *   PHP (Project::progressSummary(), ProjectController::approveEvidence())
 *   lewat nama code-nya persis -- baris ini TIDAK BOLEH dihapus.
 * - Baris apapun yang MASIH DIPAKAI minimal 1 LOP (baik lewat
 *   status_progress aktif maupun status_progress_before_hold saat
 *   HOLD/DROP) tidak boleh dihapus -- selain karena FK RESTRICT akan
 *   menolaknya juga, pesan errornya dibuat lebih ramah di sini.
 */
class ProjectStageController extends Controller
{
    /**
     * Code yang dipakai langsung oleh business logic (bukan cuma data),
     * jadi tidak boleh dihapus dari UI ini sama sekali.
     */
    private const PROTECTED_CODES = ['hold', 'drop'];

    /**
     * Kode yang sudah tidak boleh dipakai untuk alur baru, tetapi barisnya
     * harus tetap ada selama masih direferensikan data historis.
     */
    private const LEGACY_CODES = ['drm'];

    public function index(Request $request)
    {
        $search = $request->search;

        $stages = ProjectStage::query()
            ->where('code', '!=', 'drm')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('label', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('phase_group', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('sequence IS NULL, sequence ASC')
            ->orderBy('label')
            ->get();

        return view('admin.project-stages.index', compact('stages', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|alpha_dash|not_in:drm|unique:project_stages,code',
            'label' => 'required|string|max:100',
            'phase_group' => 'nullable|string|max:50',
            'sequence' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:30',
            'is_pause_type' => 'nullable|boolean',
            'is_terminal' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        ProjectStage::create([
            'code' => strtolower(trim($validated['code'])),
            'label' => $validated['label'],
            'phase_group' => $validated['phase_group'] ?? null,
            'sequence' => $validated['sequence'] ?? null,
            'color' => $validated['color'] ?? null,
            'is_pause_type' => $request->boolean('is_pause_type'),
            'is_terminal' => $request->boolean('is_terminal'),
            'is_active' => true,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Tahapan baru berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $stage = ProjectStage::findOrFail($id);

        if (in_array($stage->code, self::LEGACY_CODES, true)) {
            return back()->with('error', 'Tahap DRM adalah data historis dan tidak dapat diubah.');
        }

        // `code` SENGAJA tidak divalidasi/diupdate di sini -- read-only
        // sesudah dibuat, lihat penjelasan di docblock class.
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'phase_group' => 'nullable|string|max:50',
            'sequence' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:30',
            'is_pause_type' => 'nullable|boolean',
            'is_terminal' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $stage->update([
            'label' => $validated['label'],
            'phase_group' => $validated['phase_group'] ?? null,
            'sequence' => $validated['sequence'] ?? null,
            'color' => $validated['color'] ?? null,
            'is_pause_type' => $request->boolean('is_pause_type'),
            'is_terminal' => $request->boolean('is_terminal'),
            'is_active' => $request->boolean('is_active'),
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Tahapan berhasil diperbarui');
    }

    public function toggleActive($id)
    {
        $stage = ProjectStage::findOrFail($id);

        if (in_array($stage->code, self::LEGACY_CODES, true)) {
            return back()->with('error', 'Tahap DRM sudah dinonaktifkan dari alur baru.');
        }

        if (in_array($stage->code, self::PROTECTED_CODES, true) && $stage->is_active) {
            return back()->with('error', "Tahapan \"{$stage->label}\" adalah bagian inti alur (dipakai langsung oleh sistem) dan tidak boleh dinonaktifkan.");
        }

        $stage->update(['is_active' => ! $stage->is_active]);

        return back()->with('success', 'Status tahapan berhasil diubah');
    }

    public function destroy($id)
    {
        $stage = ProjectStage::findOrFail($id);

        if (in_array($stage->code, self::LEGACY_CODES, true)) {
            return back()->with('error', 'Tahap DRM masih disimpan untuk menjaga referensi dan histori LOP lama.');
        }

        if (in_array($stage->code, self::PROTECTED_CODES, true)) {
            return back()->with('error', "Tahapan \"{$stage->label}\" (kode: {$stage->code}) dipakai langsung oleh sistem dan tidak boleh dihapus.");
        }

        $inUse = Lop::where('status_progress', $stage->code)
            ->orWhere('status_progress_before_hold', $stage->code)
            ->exists();

        if ($inUse) {
            return back()->with('error', "Tahapan \"{$stage->label}\" masih dipakai oleh minimal 1 LOP dan tidak bisa dihapus. Nonaktifkan saja kalau tidak ingin dipakai lagi untuk LOP baru.");
        }

        $stage->delete();

        return back()->with('success', 'Tahapan berhasil dihapus');
    }
}
