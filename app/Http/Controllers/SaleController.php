<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelTransactionRequest;
use App\Http\Requests\TransactionFilterRequest;
use App\Http\Requests\TransactionRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Services\OrderWorkflow;
use App\Services\TransactionSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(TransactionFilterRequest $request): View
    {
        $filters = $request->validated();
        $sales = Sale::with('user')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('sale_number', 'like', '%'.$term.'%'))
            ->when($filters['fulfillment'] ?? null, fn ($q, $status) => $q->where('fulfillment_status', $status))
            ->when($filters['payment'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('sold_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('sold_at', '<=', $date))
            ->latest('sold_at')->paginate(12)->withQueryString();

        return view('backoffice.transactions.index', ['records' => $sales, 'type' => 'sale']);
    }

    public function create(): View
    {
        return view('backoffice.transactions.form', ['type' => 'sale', 'products' => Product::where('stock', '>', 0)->orderBy('name')->get(), 'suppliers' => collect()]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $items = collect($data['items'])->filter(fn ($item) => ! empty($item['product_id']));
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Tambahkan minimal satu produk.']);
        }
        $created = TransactionSubmission::run($request, function () use ($items, $request) {
            $sale = Sale::create(['user_id' => $request->user()->id, 'sale_number' => 'SAL-'.Str::ulid(), 'total' => 0, 'sold_at' => now()]);
            $total = 0;
            foreach ($items->sortBy('product_id') as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => "Stok {$product->name} tidak mencukupi."]);
                }
                $subtotal = $product->selling_price * $item['quantity'];
                $sale->items()->create(['product_id' => $product->id, 'quantity' => $item['quantity'], 'unit_price' => $product->selling_price, 'subtotal' => $subtotal]);
                $product->decrement('stock', $item['quantity']);
                $total += $subtotal;
            }
            $sale->update(['total' => $total]);
        });

        return to_route('sales.index')->with('success', $created ? 'Penjualan kasir berhasil disimpan.' : 'Permintaan ini sudah diproses. Periksa riwayat penjualan.');
    }

    public function show(Sale $sale): View
    {
        return view('backoffice.transactions.show', ['record' => $sale->load(['user', 'items.product', 'cancelledBy', 'events.user:id,name']), 'type' => 'sale', 'layout' => 'layouts.backoffice']);
    }

    public function cancel(CancelTransactionRequest $request, Sale $sale, OrderWorkflow $workflow): RedirectResponse
    {
        $workflow->cancel($request->user(), $sale, $request->validated('cancellation_reason'));

        return back()->with('success', 'Sale dibatalkan dan stok dikembalikan. Periksa pengembalian pembayaran yang sudah tercatat.');
    }
}
