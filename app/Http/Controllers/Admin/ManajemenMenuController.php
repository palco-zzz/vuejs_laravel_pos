<?php

namespace App\Http\Controllers\Admin;

use App\Models\Menu;
use App\Models\Category;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ManajemenMenuController extends Controller
{
    /**
     * Display menu and categories listing
     */
    public function index()
    {
        $menus = Menu::with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        $categories = Category::withCount('menus')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/ManajemenMenu/Index', [
            'menus' => $menus,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created menu
     */
    public function storeMenu(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'nama' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        Menu::create($validated);

        return redirect()->route('menu.index')->with('success', 'Menu berhasil ditambahkan.');
    }

    /**
     * Update the specified menu
     */
    public function updateMenu(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'nama' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        $menu->update($validated);

        return redirect()->route('menu.index')->with('success', 'Menu berhasil diperbarui.');
    }

    /**
     * Remove the specified menu
     */
    public function destroyMenu(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('menu.index')->with('success', 'Menu berhasil dihapus.');
    }

    /**
     * Store a newly created category
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        Category::create($validated);

        return redirect()->route('menu.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Update the specified category
     */
    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        $category->update($validated);

        return redirect()->route('menu.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified category
     */
    public function destroyCategory(Category $category)
    {
        // Check if category has menus
        if ($category->menus()->count() > 0) {
            return redirect()->route('menu.index')->with('error', 'Tidak dapat menghapus kategori yang masih memiliki menu.');
        }

        $category->delete();

        return redirect()->route('menu.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
