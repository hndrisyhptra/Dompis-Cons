<?php

namespace App\Http\Controllers;

use App\Models\PermitCategory;
use Illuminate\Http\Request;

/**
 * Kelola master daftar kategori/jenis perizinan -- dipilih waspang saat LOP
 * berada di status_progress 'perizinan' (Stage 4, belum dibangun). Stage 3
 * refactor: dulu daftar ini cuma di-seed lewat migration, sekarang bisa
 * dikelola admin tanpa perlu migration/ubah kode lagi.
 *
 * lops.permit_category_id => ON DELETE SET NULL, jadi hapus kategori yang
 * sudah pernah dipakai TIDAK menyebabkan error FK -- LOP lama cuma
 * kehilangan kategori perizinannya (jadi NULL). Cukup diberi peringatan di
 * UI, tidak perlu diblokir keras.
 */
class PermitCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $categories = PermitCategory::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.permit-categories.index', compact('categories', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:permit_categories,name',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        PermitCategory::create([
            'name' => strtoupper(trim($request->name)),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Kategori perizinan berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $category = PermitCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:150|unique:permit_categories,name,' . $category->id,
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => strtoupper(trim($request->name)),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Kategori perizinan berhasil diperbarui');
    }

    /**
     * Toggle cepat aktif/nonaktif tanpa buka modal edit -- dipakai tombol
     * switch di kolom status tabel.
     */
    public function toggleActive($id)
    {
        $category = PermitCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        return back()->with('success', 'Status kategori perizinan berhasil diubah');
    }

    public function destroy($id)
    {
        $category = PermitCategory::findOrFail($id);
        $category->delete();

        return back()->with('success', 'Kategori perizinan berhasil dihapus');
    }
}
