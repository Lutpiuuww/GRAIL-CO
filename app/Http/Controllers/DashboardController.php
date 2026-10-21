<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $lowStock = Product::whereColumn('stock', '<=', 'minimum_stock')->orderBy('stock')->get();
        $metrics = $request->user()->role === 'admin'
            ? [
                ['Total valuation', 'Rp'.number_format(Product::selectRaw('SUM(stock * purchase_price) as total')->value('total') ?? 0, 0, ',', '.'), 'Inventory cost'],
                ['Revenue', 'Rp'.number_format(Sale::where('status', 'completed')->where(fn ($q) => $q->whereNull('payment_status')->orWhere('payment_status', 'paid'))->sum('total'), 0, ',', '.'), 'Tidak termasuk pembayaran tertunda'],
                ['Transactions', number_format(Sale::count()), 'All recorded sales'],
            ]
            : [
                ['Produk aktif', number_format(Product::count()), 'Produk dalam katalog gudang'],
                ['Unit tersedia', number_format(Product::sum('stock')), 'Total unit di gudang'],
                ['Penjualan hari ini', number_format(Sale::where('status', 'completed')->whereDate('sold_at', today())->count()), 'Transaksi selesai hari ini'],
            ];
        $metrics[] = ['Low stock', $lowStock->count(), 'Requires attention'];

        return view('dashboard', [
            'metrics' => $metrics,
            'lowStock' => $lowStock,
            'products' => Product::with('category')->orderBy('stock')->limit(8)->get(),
        ]);
    }
}
