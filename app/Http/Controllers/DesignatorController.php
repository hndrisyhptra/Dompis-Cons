<?php

namespace App\Http\Controllers;

use App\Models\Designator;
use Illuminate\Http\Request;

class DesignatorController extends Controller
{
    public function index(Request $request)
    {
        $query = Designator::query();

        if ($request->search) {
            $query->where('designator', 'like', '%' . $request->search . '%')
                ->orWhere('item_name', 'like', '%' . $request->search . '%')
                ->orWhere('unit', 'like', '%' . $request->search . '%');
        }

        $designators = $query
            ->orderBy('designator')
            ->paginate(10)
            ->withQueryString();

        return view('admin.designators.index', compact('designators'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'designator' => 'required|string|max:100',
            'item_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
        ]);

        Designator::create([
            'designator' => strtoupper($request->designator),
            'item_name' => $request->item_name,
            'unit' => $request->unit,
        ]);

        return back()->with('success', 'Designator berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $designator = Designator::findOrFail($id);

        $request->validate([
            'designator' => 'required|string|max:100',
            'item_name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
        ]);

        $designator->update([
            'designator' => strtoupper($request->designator),
            'item_name' => $request->item_name,
            'unit' => $request->unit,
        ]);

        return back()->with('success', 'Designator berhasil diperbarui');
    }

    public function destroy($id)
    {
        $designator = Designator::findOrFail($id);
        $designator->delete();

        return back()->with('success', 'Designator berhasil dihapus');
    }
}