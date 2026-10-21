<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderFulfillmentRequest;
use App\Http\Requests\OrderPaymentRequest;
use App\Models\Sale;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;

class OrderManagementController extends Controller
{
    public function payment(OrderPaymentRequest $request, Sale $sale, OrderWorkflow $workflow): RedirectResponse
    {
        $workflow->payment($request->user(), $sale, $request->validated());

        return to_route('sales.show', $sale)->with('success', 'Status pembayaran berhasil dicatat.');
    }

    public function fulfillment(OrderFulfillmentRequest $request, Sale $sale, OrderWorkflow $workflow): RedirectResponse
    {
        $workflow->fulfillment($request->user(), $sale, $request->validated());

        return to_route('sales.show', $sale)->with('success', 'Status pesanan diperbarui dan tercatat di riwayat customer.');
    }
}
