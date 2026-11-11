<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterChecklist;
use Illuminate\Http\Request;

class MasterChecklistController extends Controller
{
    public function index()
    {
        $checklists = MasterChecklist::orderBy('sort_order', 'asc')
            ->get()
            ->groupBy('category');

        return view('admin.checklist.index', compact('checklists'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:condition,completeness'],
        ]);

        // Auto-assign ke urutan paling akhir
        $maxSort = MasterChecklist::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSort + 1;
        $validated['is_active']  = $request->boolean('is_active', true);

        MasterChecklist::create($validated);

        return redirect()->route('master-checklists.index')->with('success', 'Item master checklist berhasil ditambahkan.');
    }

    public function update(Request $request, MasterChecklist $masterChecklist)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'category'  => ['required', 'in:condition,completeness'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $masterChecklist->update($validated);

        return redirect()->route('master-checklists.index')->with('success', 'Item master checklist berhasil diperbarui.');
    }

    /**
     * Endpoint AJAX untuk Reorder Drag & Drop
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'orders'   => ['required', 'array'],
            'orders.*' => ['integer', 'exists:master_checklists,id'],
        ]);

        foreach ($request->orders as $index => $id) {
            MasterChecklist::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['status' => 'success', 'message' => 'Urutan berhasil diperbarui.']);
    }

    public function destroy(MasterChecklist $masterChecklist)
    {
        $masterChecklist->delete();

        return redirect()->route('master-checklists.index')->with('success', 'Item master checklist berhasil dihapus.');
    }
}