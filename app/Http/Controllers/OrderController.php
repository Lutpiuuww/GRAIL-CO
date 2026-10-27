<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Request $request, Sale $sale): View
    {
        abort_unless($sale->user_id === $request->user()->id, 404);

        return view('backoffice.transactions.show', ['record' => $sale->load(['user', 'items.product', 'cancelledBy', 'events.user:id,name']), 'type' => 'sale', 'layout' => 'layouts.app']);
    }

    public function __invoke(): View
    {
        return view('orders.index', ['orders' => auth()->user()->sales()->with('items.product')->latest('sold_at')->paginate(10)]);
    }

    public function cancel(Request $request, Sale $sale, OrderWorkflow $workflow): RedirectResponse
    {
        $version = $request->validate(['version' => ['required', 'integer', 'min:0']])['version'];
        abort_unless($sale->user_id === $request->user()->id && $sale->checkout_token, 404);

        $workflow->cancelByCustomer($request->user(), $sale, (int) $version);

        return to_route('orders.show', $sale)->with('success', 'Pesanan dibatalkan. Stok barang telah dikembalikan.');
    }
}
