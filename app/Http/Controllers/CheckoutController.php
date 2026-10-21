<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutDetailsRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CheckoutQuote;
use App\Services\TransactionSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        try {
            $quote = CheckoutQuote::make($request->session()->get('cart', []), 'pickup');
        } catch (ValidationException $exception) {
            return to_route('cart')->withErrors(['cart' => 'Cart kosong atau ada item yang tidak tersedia. Perbarui cart sebelum checkout.']);
        }

        return view('checkout.form', ['quote' => $quote, 'details' => $request->session()->get('checkout.draft.details', [])]);
    }

    public function prepare(CheckoutDetailsRequest $request): RedirectResponse
    {
        $details = $request->validated();
        $cart = CheckoutQuote::cart($request->session()->get('cart', []));
        $quote = CheckoutQuote::make($cart, $details['delivery_method']);
        $details['email'] = $request->user()->email;
        $request->session()->put('checkout.draft', ['details' => $details, 'cart' => $cart, 'quote' => $quote, 'token' => (string) Str::uuid()]);

        return to_route('checkout.review');
    }

    public function review(Request $request): View|RedirectResponse
    {
        $draft = $request->session()->get('checkout.draft');
        if (! $draft) {
            return to_route('checkout.create');
        }
        try {
            $cart = CheckoutQuote::cart($request->session()->get('cart', []));
            if ($cart !== $draft['cart']) {
                return to_route('checkout.create')->withErrors(['checkout' => 'Isi cart berubah. Periksa kembali data checkout.']);
            }
            $quote = CheckoutQuote::make($cart, $draft['details']['delivery_method']);
        } catch (ValidationException $exception) {
            return to_route('cart')->withErrors(['cart' => 'Stok berubah atau produk tidak tersedia. Perbarui cart terlebih dahulu.']);
        }
        $changed = CheckoutQuote::signature($quote) !== CheckoutQuote::signature($draft['quote']);
        if ($changed) {
            $draft['token'] = (string) Str::uuid();
        }
        $draft['quote'] = $quote;
        $request->session()->put('checkout.draft', $draft);

        return view('checkout.review', ['draft' => $draft, 'quote' => $quote, 'changed' => $changed]);
    }

    public function store(Request $request): RedirectResponse
    {
        $token = $request->validate(['submission_token' => ['required', 'uuid']])['submission_token'];
        $existing = Sale::where('checkout_token', $token)->where('user_id', $request->user()->id)->first();
        if ($existing) {
            return to_route('checkout.success', $existing);
        }
        $draft = $request->session()->get('checkout.draft');
        if (! $draft || ! hash_equals($draft['token'], $token)) {
            return to_route('checkout.create')->withErrors(['checkout' => 'Tinjau pesanan terlebih dahulu sebelum konfirmasi.']);
        }
        $cart = CheckoutQuote::cart($request->session()->get('cart', []));
        if ($cart !== $draft['cart']) {
            return to_route('checkout.create')->withErrors(['checkout' => 'Isi cart berubah. Tinjau kembali pesananmu.']);
        }
        try {
            $created = TransactionSubmission::run($request, function () use ($request, $draft, $cart, $token) {
                $quote = CheckoutQuote::make($cart, $draft['details']['delivery_method'], true);
                if (CheckoutQuote::signature($quote) !== CheckoutQuote::signature($draft['quote'])) {
                    throw ValidationException::withMessages(['checkout' => 'Harga atau ongkir berubah. Tinjau total terbaru sebelum membuat pesanan.']);
                }
                $sale = Sale::create([
                    'user_id' => $request->user()->id, 'sale_number' => 'SAL-'.Str::ulid(), 'sold_at' => now(),
                    'total' => CheckoutQuote::money($quote['total_cents']), 'checkout_token' => $token,
                    'delivery_details' => $draft['details'], 'delivery_method' => $draft['details']['delivery_method'],
                    'shipping_cost' => CheckoutQuote::money($quote['shipping_cents']),
                    'payment_method' => $draft['details']['payment_method'], 'payment_status' => 'pending', 'fulfillment_status' => 'pending',
                ]);
                foreach ($quote['items'] as $item) {
                    $sale->items()->create(['product_id' => $item['id'], 'quantity' => $item['quantity'], 'unit_price' => CheckoutQuote::money($item['unit_cents']), 'subtotal' => CheckoutQuote::money($item['line_cents'])]);
                    Product::whereKey($item['id'])->decrement('stock', $item['quantity']);
                }
            });
        } catch (ValidationException $exception) {
            return to_route(isset($exception->errors()['cart']) ? 'cart' : 'checkout.review')->withErrors($exception->errors());
        }
        if ($created) {
            $request->session()->forget(['cart', 'checkout.draft']);
        }
        $sale = Sale::where('checkout_token', $token)->where('user_id', $request->user()->id)->firstOrFail();

        return to_route('checkout.success', $sale);
    }

    public function success(Request $request, Sale $sale): View
    {
        abort_unless($sale->user_id === $request->user()->id && $sale->checkout_token, 404);

        return view('checkout.success', ['sale' => $sale->load(['items.product', 'events.user:id,name'])]);
    }
}
