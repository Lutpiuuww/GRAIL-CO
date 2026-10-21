<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($sub) => $sub->where('name', 'like', '%'.$request->string('q').'%')->orWhere('sku', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->status === 'low', fn ($query) => $query->whereColumn('stock', '<=', 'minimum_stock'))
            ->latest()->paginate(10)->withQueryString();

        return view('backoffice.products.index', ['products' => $products, 'categories' => Category::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('backoffice.products.form', ['product' => new Product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request));

        return to_route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        return view('backoffice.products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return to_route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Produk dipindahkan ke arsip. Riwayat transaksi tetap aman.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($product)],
            'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:1000'],
            'purchase_price' => ['required', 'numeric', 'min:0'], 'selling_price' => ['required', 'numeric', 'gte:purchase_price'],
            'stock' => ['prohibited'], 'minimum_stock' => ['required', 'integer', 'min:0'],
        ]);
    }
}
