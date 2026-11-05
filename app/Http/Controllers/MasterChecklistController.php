<?php

namespace App\Http\Controllers;

use App\Models\MasterChecklist;
use Illuminate\Http\Request;

class MasterChecklistController extends Controller
{
    /**
     * Tampilkan daftar master checklist (View Utama Single-Page).
     */
    public function index()
    {
        $checklists = MasterChecklist::orderBy('category')
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('admin.checklist.index', compact('checklists'));
    }

    /**
     * Simpan item master checklist baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'category'   => ['required', 'in:condition,completeness'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active']  = $request->boolean('is_active', true);

        MasterChecklist::create($validated);

        return redirect()->route('master-checklists.index')->with('success', 'Item master checklist berhasil ditambahkan.');
    }

    /**
     * Perbarui item master checklist.
     */
    public function update(Request $request, MasterChecklist $masterChecklist)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'category'   => ['required', 'in:condition,completeness'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active']  = $request->boolean('is_active');

        $masterChecklist->update($validated);

        return redirect()->route('master-checklists.index')->with('success', 'Item master checklist berhasil diperbarui.');
    }

    /**
     * Hapus item master checklist.
     */
    public function destroy(MasterChecklist $masterChecklist)
    {
        $masterChecklist->delete();

        return redirect()->route('master-checklists.index')->with('success', 'Item master checklist berhasil dihapus.');
    }
}