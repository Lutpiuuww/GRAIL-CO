<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_sale_purchase_and_checkout_are_processed_once(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::create(['category_id' => Category::create(['name' => 'Archive'])->id, 'sku' => 'DEDUPE', 'name' => 'Watch', 'stock' => 5, 'minimum_stock' => 1, 'purchase_price' => 100, 'selling_price' => 250]);
        foreach (['sales', 'purchases'] as $endpoint) {
            $data = ['submission_token' => (string) Str::uuid(), 'items' => [['product_id' => $product->id, 'quantity' => 1]]];
            if ($endpoint === 'purchases') {
                $data['supplier_id'] = Supplier::create(['name' => 'Vendor'])->id;
            }
            $this->post('/'.$endpoint, $data)->assertSessionHasNoErrors();
            $stock = $product->fresh()->stock;
            $this->post('/'.$endpoint, $data)->assertSessionHasNoErrors();
            $this->assertSame($stock, $product->fresh()->stock);
            $this->assertDatabaseCount($endpoint, 1);
        }
        $token = ['submission_token' => $this->prepareCheckout([$product->id => 1])];
        $this->post('/checkout', $token)->assertSessionHasNoErrors();
        $this->assertSame(4, $product->fresh()->stock);
        $this->withSession(['cart' => [$product->id => 1]])->post('/checkout', $token)->assertSessionHasNoErrors();
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('transaction_submissions', 3);
        $this->assertSame([$product->id => 1], session('cart'));
    }

    public function test_failed_transaction_does_not_consume_token_and_retry_can_succeed(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::create(['category_id' => Category::create(['name' => 'Archive'])->id, 'sku' => 'RETRY', 'name' => 'Watch', 'stock' => 0, 'purchase_price' => 100, 'selling_price' => 250]);
        $data = ['submission_token' => (string) Str::uuid(), 'items' => [['product_id' => $product->id, 'quantity' => 1]]];
        $this->post('/sales', $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('transaction_submissions', 0);
        $product->update(['stock' => 1]);
        $this->post('/sales', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('transaction_submissions', 1);
        $this->assertSame(0, $product->fresh()->stock);
    }
}
