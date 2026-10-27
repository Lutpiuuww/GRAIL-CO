<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function archive(Request $request): View
    {
        $products = Product::onlyTrashed()->with('category')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($sub) => $sub->where('name', 'like', '%'.$request->string('q').'%')->orWhere('sku', 'like', '%'.$request->string('q').'%')))
            ->latest('deleted_at')->paginate(10)->withQueryString();

        return view('backoffice.products.archive', ['products' => $products]);
    }

    public function create(): View
    {
        return view('backoffice.products.form', ['product' => new Product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['image'], $data['remove_image']);
        $path = null;
        try {
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('', 'product_images');
                $data['uploaded_image_path'] = $path;
            }
            Product::create($data);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('product_images')->delete($path);
            }
            throw $exception;
        }

        return to_route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        return view('backoffice.products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        unset($data['image'], $data['remove_image']);
        $oldPath = $product->uploaded_image_path;
        $newPath = null;
        try {
            if ($request->hasFile('image')) {
                $newPath = $request->file('image')->store('', 'product_images');
                $data['uploaded_image_path'] = $newPath;
            } elseif ($request->boolean('remove_image')) {
                $data['uploaded_image_path'] = null;
            }
            $product->update($data);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('product_images')->delete($newPath);
            }
            throw $exception;
        }
        if ($oldPath && ($newPath || $request->boolean('remove_image'))) {
            Storage::disk('product_images')->delete($oldPath);
        }

        return to_route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Produk dipindahkan ke arsip. Riwayat transaksi tetap aman.');
    }

    public function restore(Product $product): RedirectResponse
    {
        abort_unless($product->trashed(), 404);
        $product->restore();

        return to_route('products.archive')->with('success', 'Produk dipulihkan. Stok dan riwayat transaksi tetap sama.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($product)],
            'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:1000'],
            'purchase_price' => ['required', 'numeric', 'min:0'], 'selling_price' => ['required', 'numeric', 'gte:purchase_price'],
            'stock' => ['prohibited'], 'minimum_stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:min_width=1,min_height=1'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);
    }
}
