<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function show(Product $product): View
    {
        $product->load('category');
        $related = Product::with('category')->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)->where('stock', '>', 0)->latest()->limit(4)->get();

        return view('catalog.show', compact('product', 'related'));
    }

    public function __invoke(Request $request): View
    {
        $products = Product::with('category')->where('stock', '>', 0)
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('sku', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->latest()->paginate(12)->withQueryString();

        return view('catalog.index', ['products' => $products, 'categories' => Category::orderBy('name')->get()]);
    }
}
