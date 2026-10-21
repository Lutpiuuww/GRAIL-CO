<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function product(): Product
    {
        return Product::create(['category_id' => Category::create(['name' => 'Archive'])->id, 'sku' => 'AUDIT-01', 'name' => 'Archive Watch', 'purchase_price' => 100, 'selling_price' => 250, 'stock' => 5, 'minimum_stock' => 1]);
    }

    public function test_sale_requires_reason_records_actor_and_cannot_be_voided_twice(): void
    {
        $product = $this->product();
        $this->post('/sales', ['submission_token' => (string) Str::uuid(), 'items' => [['product_id' => $product->id, 'quantity' => 2]]]);
        $sale = Sale::firstOrFail();
        $this->patch('/sales/'.$sale->id.'/cancel')->assertSessionHasErrors('cancellation_reason');
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame('completed', $sale->fresh()->status);
        $this->patch('/sales/'.$sale->id.'/cancel', ['cancellation_reason' => 'Pesanan ganda dari customer', 'cancelled_by' => 999])->assertSessionHasNoErrors();
        $sale->refresh();
        $this->assertSame(auth()->id(), $sale->cancelled_by);
        $this->assertNotNull($sale->cancelled_at);
        $this->assertSame('Pesanan ganda dari customer', $sale->cancellation_reason);
        $this->assertSame(5, $product->fresh()->stock);
        $this->patch('/sales/'.$sale->id.'/cancel', ['cancellation_reason' => 'Alasan baru tidak boleh menimpa'])->assertSessionHasErrors('sale');
        $this->assertSame('Pesanan ganda dari customer', $sale->fresh()->cancellation_reason);
        $this->assertSame(5, $product->fresh()->stock);
        $this->get('/sales/'.$sale->id)->assertOk()->assertSee('Pesanan ganda dari customer');
    }

    public function test_failed_purchase_void_leaves_audit_and_stock_untouched(): void
    {
        $product = $this->product();
        $this->post('/purchases', ['submission_token' => (string) Str::uuid(), 'supplier_id' => Supplier::create(['name' => 'Vendor'])->id, 'items' => [['product_id' => $product->id, 'quantity' => 2]]]);
        $purchase = Purchase::firstOrFail();
        $product->update(['stock' => 1]);
        $reason = ['cancellation_reason' => 'Barang akan dikembalikan ke vendor'];
        $this->patch('/purchases/'.$purchase->id.'/cancel', $reason)->assertSessionHasErrors('purchase');
        $this->assertNull($purchase->fresh()->cancelled_by);
        $this->assertNull($purchase->fresh()->cancelled_at);
        $this->assertNull($purchase->fresh()->cancellation_reason);
        $this->assertSame(1, $product->fresh()->stock);
        $product->update(['stock' => 2]);
        $this->patch('/purchases/'.$purchase->id.'/cancel', $reason)->assertSessionHasNoErrors();
        $this->assertSame(auth()->id(), $purchase->fresh()->cancelled_by);
        $this->get('/purchases/'.$purchase->id)->assertOk()->assertSee($reason['cancellation_reason']);
    }

    public function test_customers_can_only_view_own_receipts_and_archived_items_still_render(): void
    {
        $product = $this->product();
        $owner = User::factory()->create(['role' => 'customer']);
        $this->actingAs($owner);
        $token = $this->prepareCheckout([$product->id => 1]);
        $this->post('/checkout', ['submission_token' => $token])->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $product->update(['selling_price' => 900]);
        $product->delete();
        $this->get('/my-orders/'.$sale->id)->assertOk()->assertSee('Archive Watch')->assertSee('Rp250')->assertDontSee('Rp900');
        $this->get('/sales/'.$sale->id)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/my-orders/'.$sale->id)->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $this->get('/sales/'.$sale->id)->assertOk()->assertDontSee('Konfirmasi pembatalan');
        $this->patch('/sales/'.$sale->id.'/cancel', ['cancellation_reason' => 'Unauthorized void attempt'])->assertForbidden();
    }

    public function test_transaction_search_date_filters_and_validation(): void
    {
        $id = auth()->id();
        Sale::create(['user_id' => $id, 'sale_number' => 'SAL-MATCH', 'total' => 0, 'sold_at' => '2026-10-10 23:59:59']);
        Sale::create(['user_id' => $id, 'sale_number' => 'SAL-OUTSIDE', 'total' => 0, 'sold_at' => '2026-10-11 00:00:00']);
        $this->get('/sales?q=MATCH&status=completed&from=2026-10-10&to=2026-10-10')->assertOk()->assertSee('SAL-MATCH')->assertDontSee('SAL-OUTSIDE');
        $this->get('/sales?from=2026-10-11&to=2026-10-10')->assertSessionHasErrors('to');
        $this->get('/purchases?status=invalid')->assertSessionHasErrors('status');
    }
}
