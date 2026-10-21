<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelTransactionRequest;
use App\Http\Requests\TransactionFilterRequest;
use App\Http\Requests\TransactionRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\TransactionSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(TransactionFilterRequest $request): View
    {
        $filters = $request->validated();
        $purchases = Purchase::with(['supplier', 'user'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('purchase_number', 'like', '%'.$term.'%'))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('purchased_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('purchased_at', '<=', $date))
            ->latest('purchased_at')->paginate(12)->withQueryString();

        return view('backoffice.transactions.index', ['records' => $purchases, 'type' => 'purchase']);
    }

    public function create(): View
    {
        return view('backoffice.transactions.form', ['type' => 'purchase', 'products' => Product::orderBy('name')->get(), 'suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $items = collect($data['items'])->filter(fn ($item) => ! empty($item['product_id']));
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Tambahkan minimal satu produk.']);
        }
        $created = TransactionSubmission::run($request, function () use ($data, $items, $request) {
            $purchase = Purchase::create(['supplier_id' => $data['supplier_id'], 'user_id' => $request->user()->id, 'purchase_number' => 'PUR-'.Str::ulid(), 'total' => 0, 'purchased_at' => now()]);
            $total = 0;
            foreach ($items->sortBy('product_id') as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $subtotal = $product->purchase_price * $item['quantity'];
                $purchase->items()->create(['product_id' => $product->id, 'quantity' => $item['quantity'], 'unit_cost' => $product->purchase_price, 'subtotal' => $subtotal]);
                $product->increment('stock', $item['quantity']);
                $total += $subtotal;
            }
            $purchase->update(['total' => $total]);
        });

        return to_route('purchases.index')->with('success', $created ? 'Stock-in berhasil dicatat dan stok bertambah.' : 'Permintaan ini sudah diproses. Periksa riwayat Stock In.');
    }

    public function show(Purchase $purchase): View
    {
        return view('backoffice.transactions.show', ['record' => $purchase->load(['supplier', 'user', 'items.product', 'cancelledBy']), 'type' => 'purchase', 'layout' => 'layouts.backoffice']);
    }

    public function cancel(CancelTransactionRequest $request, Purchase $purchase): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        DB::transaction(function () use ($purchase, $request) {
            $purchase = Purchase::lockForUpdate()->with('items')->findOrFail($purchase->id);
            if ($purchase->status !== 'completed') {
                throw ValidationException::withMessages(['purchase' => 'Transaksi sudah dibatalkan.']);
            }
            foreach ($purchase->items->sortBy('product_id') as $item) {
                $product = Product::withTrashed()->lockForUpdate()->findOrFail($item->product_id);
                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages(['purchase' => "Stok {$product->name} tidak cukup untuk membatalkan stock-in."]);
                }
                $product->decrement('stock', $item->quantity);
            }
            $purchase->update(['status' => 'cancelled', 'cancelled_by' => $request->user()->id, 'cancelled_at' => now(), 'cancellation_reason' => $request->validated('cancellation_reason')]);
        });

        return back()->with('success', 'Purchase dibatalkan dan stok ditarik kembali.');
    }
}
