<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::withCount('products')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('name')->paginate(10)->withQueryString();

        return view('backoffice.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('backoffice.categories.form', ['category' => new Category]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($request->validate(['name' => ['required', 'string', 'max:100', 'unique:categories']]));

        return to_route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        return view('backoffice.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($request->validate(['name' => ['required', 'string', 'max:100', 'unique:categories,name,'.$category->id]]));

        return to_route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->withTrashed()->exists()) {
            return back()->withErrors(['category' => 'Kategori yang masih memiliki produk tidak dapat dihapus.']);
        }
        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
