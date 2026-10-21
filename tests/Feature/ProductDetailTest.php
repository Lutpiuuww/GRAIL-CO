<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function product(int $stock = 5): Product
    {
        return Product::create(['category_id' => Category::firstOrCreate(['name' => 'Archive'])->id, 'sku' => 'GRL-OW-01', 'name' => 'Detail Hoodie', 'description' => 'Hoodie potongan boxy dengan warna washed black.', 'purchase_price' => 123456, 'selling_price' => 750000, 'stock' => $stock, 'minimum_stock' => 1]);
    }

    public function test_public_detail_exposes_product_information_but_not_cost_price(): void
    {
        $product = $this->product();
        $this->get('/catalog')->assertOk()->assertSee(route('catalog.show', $product), false);
        $this->get('/catalog/'.$product->id)->assertOk()
            ->assertSee('Detail Hoodie')->assertSee($product->description)
            ->assertSee('Rp750.000')->assertDontSee('123.456')->assertSee('Masuk untuk membeli');
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get('/catalog/'.$product->id)->assertOk()->assertSee('Tambahkan ke cart');
    }

    public function test_sold_out_detail_disables_purchase_and_archived_product_is_not_public(): void
    {
        $product = $this->product(0);
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $this->get('/catalog/'.$product->id)->assertOk()->assertSee('Stok habis')->assertDontSee('Tambahkan ke cart');
        $this->post('/cart/'.$product->id)->assertSessionHasErrors('quantity');
        $product->delete();
        $this->get('/catalog/'.$product->id)->assertNotFound();
        $this->post('/cart/'.$product->id)->assertNotFound();
    }

    public function test_add_quantity_is_validated_against_existing_cart_and_available_stock(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $this->post('/cart/'.$product->id, ['quantity' => 2])->assertSessionHasNoErrors();
        $this->assertSame(2, session('cart')[$product->id]);
        foreach ([0, -1, 1.5, 4] as $quantity) {
            $this->post('/cart/'.$product->id, ['quantity' => $quantity])->assertSessionHasErrors('quantity');
            $this->assertSame(2, session('cart')[$product->id]);
        }
        $this->post('/cart/'.$product->id)->assertSessionHasNoErrors();
        $this->assertSame(3, session('cart')[$product->id]);
        $this->assertSame(5, $product->fresh()->stock);
    }
}
