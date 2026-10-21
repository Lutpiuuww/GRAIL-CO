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

class InventoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function product(int $stock = 5): Product
    {
        return Product::create([
            'category_id' => Category::firstOrCreate(['name' => 'Archive'])->id,
            'sku' => 'TEST-'.Product::withTrashed()->count(),
            'name' => 'Archive item', 'purchase_price' => 100, 'selling_price' => 250,
            'stock' => $stock, 'minimum_stock' => 1,
        ]);
    }

    public function test_half_filled_rows_are_rejected_and_blank_rows_are_ignored(): void
    {
        $product = $this->product();
        foreach (['sales', 'purchases'] as $endpoint) {
            $extra = $endpoint === 'purchases'
                ? ['supplier_id' => Supplier::firstOrCreate(['name' => 'Supplier'])->id] : [];
            $this->post("/$endpoint", $extra + ['submission_token' => (string) Str::uuid(), 'items' => [
                ['product_id' => $product->id, 'quantity' => null],
            ]])->assertSessionHasErrors('items.0.quantity');
            $this->post("/$endpoint", $extra + ['items' => [
                ['product_id' => null, 'quantity' => 2],
            ]])->assertSessionHasErrors('items.0.product_id');
        }
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('purchases', 0);
        $this->post('/sales', ['submission_token' => (string) Str::uuid(), 'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
            ['product_id' => null, 'quantity' => null],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_archived_product_history_and_void_remain_available(): void
    {
        $product = $this->product();
        $this->post('/sales', ['submission_token' => (string) Str::uuid(), 'items' => [['product_id' => $product->id, 'quantity' => 2]]])
            ->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $product->delete();
        $this->get('/my-orders')->assertOk()->assertSee('Archive item');
        $this->patch("/sales/{$sale->id}/cancel", ['cancellation_reason' => 'Koreksi transaksi pengujian'])->assertSessionHasNoErrors();
        $this->assertSame(5, $product->fresh()->stock);
        $this->patch("/sales/{$sale->id}/cancel", ['cancellation_reason' => 'Koreksi transaksi pengujian'])->assertSessionHasErrors('sale');
        $this->assertSame(5, $product->fresh()->stock);
        $this->delete('/categories/'.$product->category_id)->assertSessionHasErrors('category');
    }

    public function test_sale_failure_rolls_back_every_item_and_header(): void
    {
        $first = $this->product(5);
        $second = $this->product(1);
        $this->post('/sales', ['submission_token' => (string) Str::uuid(), 'items' => [
            ['product_id' => $first->id, 'quantity' => 2],
            ['product_id' => $second->id, 'quantity' => 2],
        ]])->assertSessionHasErrors('items');
        $this->assertSame(5, $first->fresh()->stock);
        $this->assertSame(1, $second->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
    }

    public function test_purchase_void_failure_rolls_back_and_archived_items_can_be_voided(): void
    {
        $first = $this->product(0);
        $second = $this->product(0);
        $this->post('/purchases', ['submission_token' => (string) Str::uuid(),
            'supplier_id' => Supplier::create(['name' => 'Vendor'])->id,
            'items' => [
                ['product_id' => $first->id, 'quantity' => 2],
                ['product_id' => $second->id, 'quantity' => 2],
            ],
        ])->assertSessionHasNoErrors();
        $purchase = Purchase::firstOrFail();
        $second->update(['stock' => 1]);
        $this->patch("/purchases/{$purchase->id}/cancel", ['cancellation_reason' => 'Koreksi transaksi pengujian'])->assertSessionHasErrors('purchase');
        $this->assertSame(2, $first->fresh()->stock);
        $this->assertSame('completed', $purchase->fresh()->status);
        $second->update(['stock' => 2]);
        $first->delete();
        $this->patch("/purchases/{$purchase->id}/cancel", ['cancellation_reason' => 'Koreksi transaksi pengujian'])->assertSessionHasNoErrors();
        $this->assertSame(0, $first->fresh()->stock);
        $this->assertSame(0, $second->fresh()->stock);
    }

    public function test_product_form_cannot_overwrite_stock(): void
    {
        $product = $this->product();
        $data = $product->only(['category_id', 'sku', 'name', 'purchase_price', 'selling_price', 'minimum_stock']);
        $this->put("/products/{$product->id}", $data + ['stock' => 99])->assertSessionHasErrors('stock');
        $this->assertSame(5, $product->fresh()->stock);
        $this->put("/products/{$product->id}", $data)->assertSessionHasNoErrors();
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_archived_items_cannot_enter_new_transactions(): void
    {
        $product = $this->product();
        $product->delete();
        $this->post('/sales', ['submission_token' => (string) Str::uuid(), 'items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertSessionHasErrors('items.0.product_id');
        $this->withSession(['cart' => [$product->id => 1]])->post('/checkout/review', $this->checkoutData())
            ->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_archived_cart_item_remains_visible_and_removable(): void
    {
        $product = $this->product();
        $product->delete();
        $this->withSession(['cart' => [$product->id => 2]])
            ->get('/cart')->assertOk()->assertSee('Archive item');
        $this->delete('/cart/'.$product->id)->assertSessionHasNoErrors();
        $this->assertSame([], session('cart'));
    }

    public function test_cart_quantity_changes_are_validated_without_mutating_stock(): void
    {
        $product = $this->product(5);
        $this->withSession(['cart' => [$product->id => 1]])
            ->patch('/cart/'.$product->id, ['quantity' => 3])->assertSessionHasNoErrors();
        $this->assertSame(3, session('cart')[$product->id]);
        foreach ([0, -1, 1.5, 6] as $quantity) {
            $this->patch('/cart/'.$product->id, ['quantity' => $quantity])->assertSessionHasErrors('quantity');
            $this->assertSame(3, session('cart')[$product->id]);
        }
        $this->assertSame(5, $product->fresh()->stock);
        $product->delete();
        $this->patch('/cart/'.$product->id, ['quantity' => 1])->assertSessionHasErrors('quantity');
        $this->assertSame(3, session('cart')[$product->id]);
    }
}
