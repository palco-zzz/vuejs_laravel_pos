<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ManajemenCabangController extends Controller
{
    /**
     * Display a listing of branches.
     */
    public function index()
    {
        $branches = Branch::withCount('users')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/ManajemenCabang/Index', [
            'branches' => $branches,
        ]);
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        Branch::create($validated);

        return redirect()->route('branch.index')->with('success', 'Cabang berhasil ditambahkan.');
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        $branch->update($validated);

        return redirect()->route('branch.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch)
    {
        // Check if branch has users
        if ($branch->users()->count() > 0) {
            return redirect()->route('branch.index')->with('error', 'Tidak dapat menghapus cabang yang masih memiliki karyawan.');
        }

        $branch->delete();

        return redirect()->route('branch.index')->with('success', 'Cabang berhasil dihapus.');
    }
}
