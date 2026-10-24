<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMediaArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('product_images');
    }

    private function data(): array
    {
        return ['category_id' => Category::firstOrCreate(['name' => 'Archive'])->id, 'sku' => 'MEDIA-01', 'name' => 'Media Watch', 'description' => 'Watch', 'purchase_price' => 100, 'selling_price' => 250, 'minimum_stock' => 1];
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('watch.webp', file_get_contents(public_path('images/products/grl-ac-01.webp')));
    }

    public function test_admin_can_upload_replace_and_remove_product_photo(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/products', $this->data() + ['image' => $this->photo()])->assertSessionHasNoErrors()->assertRedirect('/products');
        $product = Product::firstOrFail();
        $firstPath = $product->uploaded_image_path;
        $this->assertNotNull($firstPath);
        Storage::disk('product_images')->assertExists($firstPath);
        $this->assertSame('images/products/uploads/'.$firstPath, $product->image_path);

        $this->put('/products/'.$product->id, $this->data() + ['image' => $this->photo()])->assertSessionHasNoErrors();
        $secondPath = $product->fresh()->uploaded_image_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('product_images')->assertMissing($firstPath);
        Storage::disk('product_images')->assertExists($secondPath);

        $this->put('/products/'.$product->id, $this->data() + ['remove_image' => 1])->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->uploaded_image_path);
        Storage::disk('product_images')->assertMissing($secondPath);
    }

    public function test_invalid_photo_is_rejected_and_staff_cannot_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($admin)->post('/products', $this->data() + ['image' => UploadedFile::fake()->createWithContent('not-image.jpg', 'plain text')])
            ->assertSessionHasErrors('image');
        $this->assertDatabaseCount('products', 0);
        $this->actingAs($staff)->post('/products', $this->data() + ['image' => $this->photo()])->assertForbidden();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_only_admin_can_view_archive_and_restore_without_changing_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::create($this->data() + ['stock' => 7]);
        $product->delete();

        $this->actingAs($staff)->get('/products/archive')->assertForbidden();
        $this->actingAs($staff)->patch('/products/'.$product->id.'/restore')->assertForbidden();
        $this->actingAs($customer)->get('/products/archive')->assertForbidden();
        $this->actingAs($admin)->get('/products/archive')->assertOk()->assertSee('Media Watch')->assertSee('Pulihkan produk');
        $this->patch('/products/'.$product->id.'/restore')->assertRedirect(route('products.archive'));
        $this->assertNull($product->fresh()->deleted_at);
        $this->assertSame(7, $product->fresh()->stock);
        $this->get('/products/archive')->assertDontSee('Media Watch');
        $this->patch('/products/'.$product->id.'/restore')->assertNotFound();
    }
}
