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

class GrailFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_is_publicly_accessible(): void
    {
        $this->get('/catalog')->assertOk()->assertSee('Objects of desire.');
    }

    public function test_seeded_product_image_is_available_to_catalog(): void
    {
        $category = Category::create(['name' => 'Outerwear']);
        Product::create(['category_id' => $category->id, 'sku' => 'GRL-OW-01', 'name' => 'Luxury Hoodie', 'purchase_price' => 100, 'selling_price' => 250, 'stock' => 2, 'minimum_stock' => 1]);

        $this->get('/catalog')->assertOk()->assertSee('images/products/grl-ow-01.webp');
    }

    public function test_customer_cannot_access_back_office(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/dashboard')->assertForbidden();
    }

    public function test_admin_can_access_back_office(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    public function test_checkout_uses_server_price_and_decrements_stock(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::create(['name' => 'Archive']);
        $product = Product::create(['category_id' => $category->id, 'sku' => 'TEST-01', 'name' => 'Test Grail', 'purchase_price' => 100, 'selling_price' => 250, 'stock' => 2, 'minimum_stock' => 1]);

        $this->actingAs($customer);
        $token = $this->prepareCheckout([$product->id => 1]);
        $this->post('/checkout', ['submission_token' => $token])->assertSessionHasNoErrors()->assertRedirect(route('checkout.success', Sale::firstOrFail()));

        $this->assertDatabaseHas('sales', ['user_id' => $customer->id, 'total' => 250]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
    }

    public function test_admin_can_manage_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/categories', ['name' => 'Rare Accessories'])
            ->assertRedirect('/categories');

        $this->assertDatabaseHas('categories', ['name' => 'Rare Accessories']);
    }

    public function test_staff_has_read_only_master_data_access(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/products')->assertOk();
        $this->actingAs($staff)->get('/suppliers')->assertOk();
        $this->actingAs($staff)->post('/products', [])->assertForbidden();
        $this->actingAs($staff)->get('/categories')->assertForbidden();
    }

    public function test_stock_in_and_admin_cancel_keep_stock_consistent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Watches']);
        $supplier = Supplier::create(['name' => 'Geneva Test']);
        $product = Product::create(['category_id' => $category->id, 'sku' => 'STOCK-01', 'name' => 'Test Watch', 'purchase_price' => 100, 'selling_price' => 250, 'stock' => 2, 'minimum_stock' => 1]);

        $this->actingAs($admin)->post('/purchases', ['submission_token' => (string) Str::uuid(), 'supplier_id' => $supplier->id, 'items' => [['product_id' => $product->id, 'quantity' => 3]]])->assertRedirect('/purchases');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);

        $purchase = Purchase::first();
        $this->actingAs($admin)->patch("/purchases/{$purchase->id}/cancel", ['cancellation_reason' => 'Koreksi transaksi pengujian'])->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => 'cancelled']);
    }

    public function test_sale_cancel_returns_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Archive']);
        $product = Product::create(['category_id' => $category->id, 'sku' => 'SALE-01', 'name' => 'Test Tee', 'purchase_price' => 100, 'selling_price' => 250, 'stock' => 5, 'minimum_stock' => 1]);
        $sale = Sale::create(['user_id' => $admin->id, 'sale_number' => 'SAL-TEST', 'total' => 500, 'status' => 'completed', 'sold_at' => now()]);
        $sale->items()->create(['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 250, 'subtotal' => 500]);

        $this->actingAs($admin)->patch("/sales/{$sale->id}/cancel", ['cancellation_reason' => 'Koreksi transaksi pengujian'])->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'cancelled']);
    }
}
