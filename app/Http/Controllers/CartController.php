<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $this->hydrate($request->session()->get('cart', []));

        return view('cart.index', ['cart' => $cart, 'total' => $cart->sum('subtotal')]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['sometimes', 'required', 'integer', 'min:1', 'max:100000']]);
        $cart = $request->session()->get('cart', []);
        $quantity = (int) ($data['quantity'] ?? 1);
        if ($product->stock < (($cart[$product->id] ?? 0) + $quantity)) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah di cart melebihi stok yang tersedia. Periksa jumlah di cart atau kurangi pilihanmu.']);
        }
        $cart[$product->id] = ($cart[$product->id] ?? 0) + $quantity;
        $request->session()->put('cart', $cart);

        return back()->with('success', $product->name.' ditambahkan ke cart.');
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Item dihapus dari cart.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100000']]);
        $cart = $request->session()->get('cart', []);
        if (! array_key_exists($product->id, $cart)) {
            throw ValidationException::withMessages(['cart' => 'Produk belum ada dalam cart.']);
        }
        if ($product->trashed() || $product->stock < $data['quantity']) {
            throw ValidationException::withMessages(['quantity' => 'Produk tidak tersedia atau jumlah melebihi stok saat ini.']);
        }
        $cart[$product->id] = (int) $data['quantity'];
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Jumlah barang berhasil diperbarui.');
    }

    private function hydrate(array $cart)
    {
        $products = Product::withTrashed()->whereIn('id', array_keys($cart))->get()->keyBy('id');

        return collect($cart)->map(fn ($qty, $id) => (object) ['product' => $products->get($id), 'quantity' => $qty, 'subtotal' => $products->get($id)?->selling_price * $qty])->filter(fn ($row) => $row->product);
    }
}
