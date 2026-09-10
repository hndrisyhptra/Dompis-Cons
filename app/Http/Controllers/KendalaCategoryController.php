<?php

namespace App\Http\Controllers;

use App\Models\KendalaCategory;
use Illuminate\Http\Request;

/**
 * Kelola master daftar flagging kendala (dipakai di semua step, bukan cuma
 * Persiapan -- lihat ProjectIssue::kendala_category_id). Stage 3 refactor:
 * dulu daftar ini cuma di-seed lewat migration, sekarang bisa dikelola admin
 * tanpa perlu migration/ubah kode lagi.
 *
 * project_issues.kendala_category_id => ON DELETE SET NULL, jadi hapus
 * kategori yang sudah pernah dipakai TIDAK menyebabkan error FK -- baris
 * kendala lama cuma kehilangan kategorinya (jadi NULL), datanya sendiri
 * tetap ada. Cukup diberi peringatan di UI, tidak perlu diblokir keras.
 */
class KendalaCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $categories = KendalaCategory::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.kendala-categories.index', compact('categories', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:kendala_categories,name',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        KendalaCategory::create([
            'name' => strtoupper(trim($request->name)),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Kategori kendala berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $category = KendalaCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:150|unique:kendala_categories,name,' . $category->id,
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => strtoupper(trim($request->name)),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Kategori kendala berhasil diperbarui');
    }

    /**
     * Toggle cepat aktif/nonaktif tanpa buka modal edit -- dipakai tombol
     * switch di kolom status tabel.
     */
    public function toggleActive($id)
    {
        $category = KendalaCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        return back()->with('success', 'Status kategori kendala berhasil diubah');
    }

    public function destroy($id)
    {
        $category = KendalaCategory::findOrFail($id);
        $category->delete();

        return back()->with('success', 'Kategori kendala berhasil dihapus');
    }
}
