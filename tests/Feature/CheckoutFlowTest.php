<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function product(int $stock = 5): Product
    {
        return Product::create(['category_id' => Category::firstOrCreate(['name' => 'Archive'])->id, 'sku' => 'CHECKOUT-'.Product::count(), 'name' => 'Checkout Watch', 'purchase_price' => 100000, 'selling_price' => 750000, 'stock' => $stock, 'minimum_stock' => 1]);
    }

    public function test_checkout_requires_login_nonempty_cart_and_review(): void
    {
        $this->get('/checkout')->assertRedirect('/login');
        $this->post('/checkout/review', [])->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $this->get('/checkout')->assertRedirect('/cart');
        $product = $this->product();
        $this->withSession(['cart' => [$product->id => 1]])->get('/checkout')->assertOk()->assertSee('Lengkapi pesananmu.');
        $this->post('/checkout', ['submission_token' => (string) Str::uuid()])->assertRedirect('/checkout')->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_checkout_prefills_saved_profile_contact_and_street_address(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'phone' => '081234567890', 'street_address' => 'Jalan Merdeka Nomor 10']);
        $product = $this->product();

        $this->actingAs($user)->withSession(['cart' => [$product->id => 1]])->get('/checkout')
            ->assertOk()->assertSee('value="081234567890"', false)->assertSee('Jalan Merdeka Nomor 10');
    }

    public function test_saved_profile_address_is_default_and_server_ignores_forged_manual_address(): void
    {
        $user = User::factory()->create([
            'role' => 'customer', 'phone' => '081234567890',
            'street_address' => 'Jalan Merdeka Nomor 10',
            'address_province' => 'Jawa Barat', 'address_city' => 'Kota Bandung',
            'address_postal_code' => '40111',
        ]);
        $product = $this->product();
        $this->actingAs($user)->withSession(['cart' => [$product->id => 1]])->get('/checkout')
            ->assertOk()->assertSee('Pakai alamat utama')->assertSee('Kirim ke alamat lain');

        $this->post('/checkout/review', $this->checkoutData([
            'delivery_method' => 'standard', 'address_source' => 'profile',
            'address' => 'Jalan Palsu Nomor 99', 'province' => 'Jawa Timur',
            'city' => 'Kota Surabaya', 'postal_code' => '60211',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('Jalan Merdeka Nomor 10', session('checkout.draft.details.address'));
        $this->assertSame('Kota Bandung', session('checkout.draft.details.city'));
        $this->get('/checkout/review')->assertOk()->assertSee('Jalan Merdeka Nomor 10')->assertDontSee('Jalan Palsu');
    }

    public function test_customer_can_choose_a_different_address_without_changing_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'customer', 'street_address' => 'Jalan Merdeka Nomor 10',
            'address_province' => 'Jawa Barat', 'address_city' => 'Kota Bandung',
            'address_postal_code' => '40111',
        ]);
        $product = $this->product();
        $this->actingAs($user);
        $token = $this->prepareCheckout([$product->id => 1], [
            'delivery_method' => 'standard', 'address_source' => 'other',
            'address' => 'Jalan Mawar Nomor 20', 'province' => 'Jawa Timur',
            'city' => 'Kota Surabaya', 'postal_code' => '60211',
        ]);
        $this->post('/checkout', ['submission_token' => $token])->assertSessionHasNoErrors();
        $this->assertSame('Jalan Mawar Nomor 20', Sale::firstOrFail()->delivery_details['address']);
        $this->assertSame('Jalan Merdeka Nomor 10', $user->fresh()->street_address);
    }

    public function test_incomplete_profile_address_cannot_be_selected_as_saved_address(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'street_address' => 'Jalan Merdeka Nomor 10']);
        $product = $this->product();
        $this->actingAs($user)->withSession(['cart' => [$product->id => 1]])->get('/checkout')
            ->assertOk()->assertDontSee('Pakai alamat utama')->assertSee('lengkapi profil');
        $this->post('/checkout/review', $this->checkoutData([
            'delivery_method' => 'standard', 'address_source' => 'profile',
        ]))->assertSessionHasErrors('address_source');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_shipping_address_and_valid_contact_are_required_for_delivery(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $product = $this->product();
        $this->withSession(['cart' => [$product->id => 1]])->post('/checkout/review', $this->checkoutData(['delivery_method' => 'standard', 'phone' => 'abc']))
            ->assertSessionHasErrors(['address', 'city', 'province', 'postal_code', 'phone']);
        $this->post('/checkout/review', $this->checkoutData(['delivery_method' => 'unknown', 'payment_method' => 'paid']))->assertSessionHasErrors(['delivery_method', 'payment_method']);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_delivery_city_must_belong_to_selected_province(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $product = $this->product();
        $this->withSession(['cart' => [$product->id => 1]])->get('/checkout')
            ->assertOk()->assertSee('Pilih provinsi')->assertSee('Pilih kota / kabupaten');

        $details = ['delivery_method' => 'standard', 'address' => 'Jalan Melati Nomor 12', 'province' => 'Jawa Barat', 'city' => 'Kota Surabaya', 'postal_code' => '40111'];
        $this->post('/checkout/review', $this->checkoutData($details))->assertSessionHasErrors('city');
        $this->post('/checkout/review', $this->checkoutData(array_replace($details, ['province' => 'Provinsi palsu'])))->assertSessionHasErrors(['province', 'city']);
        $this->assertDatabaseCount('sales', 0);

        $this->post('/checkout/review', $this->checkoutData(array_replace($details, ['city' => 'Kota Bandung'])))
            ->assertSessionHasNoErrors()->assertRedirect('/checkout/review');
    }

    public function test_review_and_confirmation_use_server_totals_and_store_delivery_details(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer);
        $product = $this->product();
        $details = ['delivery_method' => 'standard', 'payment_method' => 'bank_transfer', 'address' => 'Jalan Demo UTS nomor 12', 'city' => 'Kota Bandung', 'province' => 'Jawa Barat', 'postal_code' => '40111', 'notes' => 'Patokan pagar hitam', 'shipping_cost' => 0, 'total' => 1, 'payment_status' => 'paid'];
        $token = $this->prepareCheckout([$product->id => 2], $details);
        $this->get('/checkout/review')->assertOk()->assertSee('Rp1.525.000')->assertSee('Jalan Demo UTS nomor 12')->assertSee('Buat pesanan');
        $this->assertSame(5, $product->fresh()->stock);
        $this->post('/checkout', ['submission_token' => $token, 'total' => 1, 'shipping_cost' => 0, 'payment_status' => 'paid'])->assertSessionHasNoErrors()->assertRedirect();
        $sale = Sale::firstOrFail();
        $this->assertSame('1525000.00', $sale->total);
        $this->assertSame('25000.00', $sale->shipping_cost);
        $this->assertSame('pending', $sale->payment_status);
        $this->assertSame($customer->email, $sale->delivery_details['email']);
        $this->assertSame('Jalan Demo UTS nomor 12', $sale->delivery_details['address']);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertNull(session('cart'));
        $this->assertNull(session('checkout.draft'));
        $this->get('/checkout/success/'.$sale->id)->assertOk()->assertSee($sale->sale_number)->assertSee('Pembayaran belum dikonfirmasi');
        $this->get('/my-orders/'.$sale->id)->assertOk()->assertSee('Biaya pengiriman')->assertSee('Jalan Demo UTS nomor 12');
        $this->post('/checkout', ['submission_token' => $token])->assertRedirect(route('checkout.success', $sale));
        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_pickup_discards_address_and_has_no_shipping_charge(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'customer', 'street_address' => 'Jalan Merdeka Nomor 10',
            'address_province' => 'Jawa Barat', 'address_city' => 'Kota Bandung',
            'address_postal_code' => '40111',
        ]));
        $product = $this->product();
        $token = $this->prepareCheckout([$product->id => 1], ['address_source' => 'profile', 'address' => 'Stale address should not be retained']);
        $this->post('/checkout', ['submission_token' => $token])->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $this->assertSame('0.00', $sale->shipping_cost);
        $this->assertSame('750000.00', $sale->total);
        $this->assertArrayNotHasKey('address', $sale->delivery_details);
    }

    public function test_price_or_cart_changes_require_review_again_before_stock_changes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $product = $this->product();
        $token = $this->prepareCheckout([$product->id => 1]);
        $product->update(['selling_price' => 800000]);
        $this->post('/checkout', ['submission_token' => $token])->assertRedirect('/checkout/review')->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('transaction_submissions', 0);
        $this->get('/checkout/review')->assertOk()->assertSee('Rp800.000');
        $newToken = session('checkout.draft.token');
        $this->assertNotSame($token, $newToken);
        $this->post('/checkout', ['submission_token' => $token])->assertRedirect('/checkout');
        $this->withSession(['cart' => [$product->id => 2]])->post('/checkout', ['submission_token' => $newToken])->assertRedirect('/checkout')->assertSessionHasErrors('checkout');
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_final_stock_failure_is_atomic_and_keeps_cart_for_correction(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $first = $this->product();
        $second = $this->product();
        $cart = [$first->id => 1, $second->id => 2];
        $token = $this->prepareCheckout($cart);
        $second->update(['stock' => 1]);
        $this->post('/checkout', ['submission_token' => $token])->assertRedirect('/cart')->assertSessionHasErrors('cart');
        $this->assertSame(5, $first->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('transaction_submissions', 0);
        $this->assertSame($cart, session('cart'));
    }

    public function test_confirmation_is_private_and_pending_payment_is_not_revenue(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $product = $this->product();
        $token = $this->prepareCheckout([$product->id => 1]);
        $this->post('/checkout', ['submission_token' => $token])->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/checkout/success/'.$sale->id)->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/dashboard')->assertOk()
            ->assertViewHas('metrics', fn ($metrics) => $metrics[1][1] === 'Rp0');
    }
}
