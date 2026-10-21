<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckoutQuote
{
    public static function cart(array $cart): array
    {
        Validator::make(['items' => $cart], ['items' => ['required', 'array', 'min:1', 'max:100'], 'items.*' => ['required', 'integer', 'min:1', 'max:100000']])->validate();
        $normalized = [];
        foreach ($cart as $id => $quantity) {
            if (! ctype_digit((string) $id) || (int) $id < 1) {
                throw ValidationException::withMessages(['cart' => 'Isi cart tidak valid. Perbarui cart sebelum checkout.']);
            }
            $normalized[(int) $id] = (int) $quantity;
        }
        ksort($normalized, SORT_NUMERIC);

        return $normalized;
    }

    public static function make(array $cart, string $delivery, bool $lock = false): array
    {
        $cart = self::cart($cart);
        $query = Product::withTrashed()->whereIn('id', array_keys($cart))->orderBy('id');
        $products = ($lock ? $query->lockForUpdate() : $query)->get()->keyBy('id');
        $items = [];
        $subtotal = 0;
        foreach ($cart as $id => $quantity) {
            $product = $products->get($id);
            if (! $product || $product->trashed() || $product->stock < $quantity) {
                throw ValidationException::withMessages(['cart' => 'Ada produk yang tidak tersedia atau stoknya kurang. Perbarui cart sebelum checkout.']);
            }
            $price = (int) round((float) $product->selling_price * 100);
            $line = $price * $quantity;
            $items[] = ['id' => $id, 'name' => $product->name, 'sku' => $product->sku, 'image_path' => $product->image_path, 'quantity' => $quantity, 'unit_cents' => $price, 'line_cents' => $line];
            $subtotal += $line;
        }
        $shipping = config('checkout.delivery.'.$delivery.'.fee_cents');
        if (! is_int($shipping)) {
            throw ValidationException::withMessages(['delivery_method' => 'Pilih metode pengiriman yang tersedia.']);
        }

        return ['items' => $items, 'subtotal_cents' => $subtotal, 'shipping_cents' => $shipping, 'total_cents' => $subtotal + $shipping];
    }

    public static function signature(array $quote): string
    {
        return hash('sha256', json_encode([array_map(fn ($item) => [$item['id'], $item['quantity'], $item['unit_cents']], $quote['items']), $quote['shipping_cents']]));
    }

    public static function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
