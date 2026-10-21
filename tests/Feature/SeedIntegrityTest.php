<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeedIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_preserves_existing_stock_archives_passwords_and_account_status(): void
    {
        $this->seed();
        $product = Product::where('sku', 'GRL-OW-01')->firstOrFail();
        $product->update(['stock' => 3, 'description' => 'Deskripsi khusus yang ditulis admin.']);
        $product->delete();
        $user = User::where('email', 'staff@grail.co')->firstOrFail();
        $user->update(['password' => 'changed-secret', 'is_active' => false, 'role' => 'customer']);
        $this->seed();
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame('Deskripsi khusus yang ditulis admin.', $product->fresh()->description);
        $this->assertTrue($product->fresh()->trashed());
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertTrue(Hash::check('changed-secret', $user->fresh()->password));
        $this->assertSame(8, Product::withTrashed()->count());
    }
}
