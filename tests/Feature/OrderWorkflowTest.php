<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $staff;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->product = Product::create(['category_id' => Category::create(['name' => 'Archive'])->id, 'sku' => 'ORDER-01', 'name' => 'Archive Watch', 'purchase_price' => 100, 'selling_price' => 250, 'stock' => 10, 'minimum_stock' => 1]);
    }

    private function order(string $delivery = 'standard', string $payment = 'bank_transfer'): Sale
    {
        $this->actingAs($this->customer);
        $token = $this->prepareCheckout([$this->product->id => 1], ['delivery_method' => $delivery, 'payment_method' => $payment, 'address' => 'Jalan Simulasi UTS 123', 'city' => 'Kota Bandung', 'province' => 'Jawa Barat', 'postal_code' => '40111']);
        $this->post('/checkout', ['submission_token' => $token])->assertSessionHasNoErrors();
        $sale = Sale::latest('id')->firstOrFail();
        $this->actingAs($this->staff);

        return $sale;
    }

    private function payment(Sale $sale, array $extra = []): void
    {
        $this->patch('/sales/'.$sale->id.'/payment', $extra + ['version' => $sale->fresh()->order_version, 'reference' => 'DEMO-001'])->assertSessionHasNoErrors();
    }

    private function advance(Sale $sale, string $action, array $extra = []): void
    {
        $this->patch('/sales/'.$sale->id.'/fulfillment', $extra + ['version' => $sale->fresh()->order_version, 'action' => $action])->assertSessionHasNoErrors();
    }

    public function test_transfer_requires_payment_then_follows_shipping_sequence_with_audit(): void
    {
        $sale = $this->order();
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 0, 'action' => 'processing'])->assertSessionHasErrors('payment');
        $this->payment($sale, ['note' => 'Pembayaran demo diperiksa.']);
        $this->assertSame('paid', $sale->fresh()->payment_status);
        $this->assertSame($this->staff->id, $sale->fresh()->paid_by);
        $this->assertNotNull($sale->fresh()->paid_at);
        $this->advance($sale, 'processing');
        $this->advance($sale, 'shipped', ['carrier' => 'Kurir UTS', 'tracking_number' => 'DEMO-TRACK-001', 'note' => 'Paket demo diserahkan.']);
        $this->assertSame('DEMO-TRACK-001', $sale->fresh()->tracking_number);
        $this->advance($sale, 'completed');
        $this->assertSame('completed', $sale->fresh()->fulfillment_status);
        $this->assertNotNull($sale->fresh()->completed_at);
        $this->assertSame(4, $sale->fresh()->order_version);
        $this->assertSame(['payment_recorded', 'processing', 'shipped', 'completed'], $sale->events()->pluck('type')->all());
        $this->assertSame([$this->staff->id], $sale->events()->pluck('user_id')->unique()->values()->all());
        $this->assertSame(9, $this->product->fresh()->stock);
        $this->actingAs($this->customer)->get('/my-orders/'.$sale->id)->assertOk()->assertSee('DEMO-TRACK-001')->assertSee('Kurir UTS')->assertSee('Paket demo diserahkan.')->assertSee('Tercatat lunas')->assertDontSee('Kelola pesanan customer');
        $this->get('/checkout/success/'.$sale->id)->assertOk()->assertSee('Selesai')->assertDontSee('Pembayaran belum dikonfirmasi');
    }

    public function test_cod_can_ship_unpaid_but_cannot_finish_until_payment_is_recorded(): void
    {
        $sale = $this->order('express', 'cod');
        $this->advance($sale, 'processing');
        $this->advance($sale, 'shipped', ['carrier' => 'Demo Express', 'tracking_number' => 'COD-001']);
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 2, 'action' => 'completed'])->assertSessionHasErrors('payment');
        $this->assertSame('shipped', $sale->fresh()->fulfillment_status);
        $this->payment($sale);
        $this->advance($sale, 'completed');
        $this->assertSame(9, $this->product->fresh()->stock);
    }

    public function test_pickup_requires_location_future_schedule_and_uses_wib(): void
    {
        $this->travelTo(now()->startOfMinute());
        $sale = $this->order('pickup', 'cod');
        $this->advance($sale, 'processing');
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 1, 'action' => 'ready_for_pickup'])->assertSessionHasErrors(['pickup_location', 'pickup_at']);
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 1, 'action' => 'ready_for_pickup', 'pickup_location' => 'Showroom Demo Bandung', 'pickup_at' => now('Asia/Jakarta')->subDay()->format('Y-m-d\TH:i')])->assertSessionHasErrors('pickup_at');
        $pickup = now('Asia/Jakarta')->addDay()->setTime(14, 30);
        $this->advance($sale, 'ready_for_pickup', ['pickup_location' => 'Showroom Demo Bandung', 'pickup_at' => $pickup->format('Y-m-d\TH:i')]);
        $this->assertSame($pickup->timestamp, $sale->fresh()->pickup_at->timestamp);
        $this->get('/sales/'.$sale->id)->assertOk()->assertSee('Showroom Demo Bandung')->assertSee('14:30')->assertSee('WIB');
        $this->payment($sale);
        $this->advance($sale, 'completed');
        $this->assertNull($sale->fresh()->tracking_number);
    }

    public function test_stale_forms_and_duplicate_payment_cannot_overwrite_or_duplicate_events(): void
    {
        $sale = $this->order('standard', 'cod');
        $this->payment($sale);
        $this->patch('/sales/'.$sale->id.'/payment', ['version' => 0, 'reference' => 'DUPLICATE'])->assertSessionHasErrors('order');
        $this->patch('/sales/'.$sale->id.'/payment', ['version' => 1, 'reference' => 'DUPLICATE'])->assertSessionHasErrors('payment');
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 0, 'action' => 'processing'])->assertSessionHasErrors('order');
        $this->assertSame('DEMO-001', $sale->fresh()->payment_reference);
        $this->assertSame(1, $sale->events()->count());
        $this->assertSame(1, $sale->fresh()->order_version);
    }

    public function test_customer_cannot_mutate_order_state_or_access_other_customers_tracking(): void
    {
        $sale = $this->order();
        $this->actingAs($this->customer);
        $this->patch('/sales/'.$sale->id.'/payment', ['version' => 0, 'reference' => 'INJECTED'])->assertForbidden();
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 0, 'action' => 'completed'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/my-orders/'.$sale->id)->assertNotFound();
        $this->assertSame('pending', $sale->fresh()->fulfillment_status);
        $this->assertSame(0, $sale->events()->count());
    }

    public function test_customer_can_cancel_unconfirmed_order_and_restore_stock_once(): void
    {
        $sale = $this->order();
        $this->actingAs($this->customer);
        $this->get('/checkout/success/'.$sale->id)->assertOk()->assertSee('Batalkan pesanan');
        $this->get('/my-orders')->assertOk()->assertSee('Batalkan pesanan');
        $this->get('/my-orders/'.$sale->id)->assertOk()->assertSee('Batalkan pesanan');

        $this->patch('/my-orders/'.$sale->id.'/cancel', ['version' => 0])->assertRedirect('/my-orders/'.$sale->id)->assertSessionHasNoErrors();
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertSame('cancelled', $sale->fresh()->fulfillment_status);
        $this->assertSame('pending', $sale->fresh()->payment_status);
        $this->assertSame($this->customer->id, $sale->fresh()->cancelled_by);
        $this->assertSame(1, $sale->events()->where('type', 'cancelled')->count());
        $this->get('/my-orders/'.$sale->id)->assertOk()->assertDontSee('Batalkan pesanan');

        $this->patch('/my-orders/'.$sale->id.'/cancel', ['version' => 1])->assertSessionHasErrors('order');
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame(1, $sale->events()->count());
    }

    public function test_customer_cancel_requires_ownership_and_seller_has_not_confirmed(): void
    {
        $sale = $this->order();
        $other = User::factory()->create(['role' => 'customer']);
        $this->actingAs($other)->patch('/my-orders/'.$sale->id.'/cancel', ['version' => 0])->assertNotFound();
        $this->actingAs($this->customer)->patch('/my-orders/'.$sale->id.'/cancel', ['version' => 1])->assertSessionHasErrors('order');
        $this->assertSame(9, $this->product->fresh()->stock);

        $this->actingAs($this->staff);
        $this->payment($sale);
        $this->actingAs($this->customer)->get('/checkout/success/'.$sale->id)->assertOk()->assertDontSee('Batalkan pesanan');
        $this->patch('/my-orders/'.$sale->id.'/cancel', ['version' => 1])->assertSessionHasErrors('order');
        $this->assertSame('completed', $sale->fresh()->status);
        $this->assertSame(9, $this->product->fresh()->stock);

        $cod = $this->order('pickup', 'cod');
        $this->advance($cod, 'processing');
        $this->actingAs($this->customer)->patch('/my-orders/'.$cod->id.'/cancel', ['version' => 1])->assertSessionHasErrors('order');
        $this->assertSame('completed', $cod->fresh()->status);
    }

    public function test_wrong_method_skipped_steps_and_missing_tracking_are_rejected(): void
    {
        $sale = $this->order('pickup', 'cod');
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 0, 'action' => 'completed'])->assertSessionHasErrors('order');
        $this->advance($sale, 'processing');
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 1, 'action' => 'shipped', 'carrier' => 'Wrong courier', 'tracking_number' => 'WRONG-001'])->assertSessionHasErrors('order');
        $delivery = $this->order('standard', 'cod');
        $this->advance($delivery, 'processing');
        $this->patch('/sales/'.$delivery->id.'/fulfillment', ['version' => 1, 'action' => 'shipped'])->assertSessionHasErrors(['carrier', 'tracking_number']);
        $this->assertSame(1, $delivery->events()->count());
    }

    public function test_admin_void_before_dispatch_restores_stock_once_and_marks_simulated_refund(): void
    {
        $sale = $this->order();
        $this->payment($sale);
        $this->advance($sale, 'processing');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch('/sales/'.$sale->id.'/cancel', ['cancellation_reason' => 'Customer membatalkan pesanan demo.'])->assertSessionHasNoErrors();
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame('refunded', $sale->fresh()->payment_status);
        $this->assertSame('cancelled', $sale->fresh()->fulfillment_status);
        $this->assertNotNull($sale->fresh()->refunded_at);
        $this->assertSame($admin->id, $sale->events()->where('type', 'cancelled')->first()->user_id);
        $this->patch('/sales/'.$sale->id.'/cancel', ['cancellation_reason' => 'Permintaan pembatalan ulang.'])->assertSessionHasErrors('sale');
        $this->patch('/sales/'.$sale->id.'/payment', ['version' => 3, 'reference' => 'AFTER-CANCEL'])->assertSessionHasErrors('order');
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame(3, $sale->events()->count());
    }

    public function test_dispatched_and_ready_orders_cannot_be_voided_or_put_back_into_stock(): void
    {
        foreach (['standard', 'pickup'] as $method) {
            $sale = $this->order($method, 'cod');
            $this->advance($sale, 'processing');
            if ($method === 'standard') {
                $this->advance($sale, 'shipped', ['carrier' => 'Kurir Demo', 'tracking_number' => 'NO-VOID']);
            } else {
                $this->advance($sale, 'ready_for_pickup', ['pickup_location' => 'Showroom Demo', 'pickup_at' => now('Asia/Jakarta')->addDay()->format('Y-m-d\TH:i')]);
            }
            $stock = $this->product->fresh()->stock;
            $this->actingAs(User::factory()->create(['role' => 'admin']))->patch('/sales/'.$sale->id.'/cancel', ['cancellation_reason' => 'Void tidak boleh mengembalikan barang terkirim.'])->assertSessionHasErrors('sale');
            $this->assertSame($stock, $this->product->fresh()->stock);
            $this->assertSame('completed', $sale->fresh()->status);
        }
    }

    public function test_order_filters_and_admin_paid_revenue_follow_recorded_payment(): void
    {
        $sale = $this->order();
        $this->get('/sales?fulfillment=pending&payment=pending')->assertOk()->assertSee($sale->sale_number);
        $this->get('/sales?payment=paid')->assertOk()->assertDontSee($sale->sale_number);
        $this->payment($sale);
        $this->get('/sales?payment=paid')->assertOk()->assertSee($sale->sale_number);
        $this->get('/sales?fulfillment=invalid')->assertSessionHasErrors('fulfillment');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/dashboard')->assertOk()->assertViewHas('metrics', fn ($metrics) => $metrics[1][1] === 'Rp25.250');
    }

    public function test_pos_sale_is_not_changed_into_a_checkout_order(): void
    {
        $sale = Sale::create(['user_id' => $this->staff->id, 'sale_number' => 'POS-LEGACY', 'total' => 250, 'sold_at' => now()]);
        $this->actingAs($this->staff)->patch('/sales/'.$sale->id.'/payment', ['version' => 0, 'reference' => 'INVALID-POS'])->assertSessionHasErrors('order');
        $this->patch('/sales/'.$sale->id.'/fulfillment', ['version' => 0, 'action' => 'processing'])->assertSessionHasErrors('order');
        $this->assertNull($sale->fresh()->fulfillment_status);
        $this->assertNull($sale->fresh()->payment_status);
    }

    public function test_back_office_live_sales_summary_tracks_new_and_processed_orders(): void
    {
        $this->actingAs($this->staff)->getJson('/sales/updates')
            ->assertOk()->assertJsonPath('pending_count', 0)->assertJsonPath('latest_order_id', 0);

        $sale = $this->order();
        $this->get('/sales')->assertOk()->assertSee('data-sales-live', false)->assertSee('data-sales-results', false);
        $before = $this->getJson('/sales/updates')->assertOk()
            ->assertJsonPath('pending_count', 1)
            ->assertJsonPath('latest_order_id', $sale->id)
            ->json('revision');
        $this->actingAs($this->customer)->getJson('/sales/updates')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->getJson('/sales/updates')->assertOk();

        $this->actingAs($this->staff);
        $this->payment($sale);
        $this->advance($sale, 'processing');
        $after = $this->getJson('/sales/updates')->assertOk()->assertJsonPath('pending_count', 0)->json('revision');
        $this->assertNotSame($before, $after);
    }
}
