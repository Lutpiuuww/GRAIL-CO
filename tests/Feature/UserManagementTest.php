<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_only_admin_can_manage_accounts(): void
    {
        $target = User::factory()->create();
        foreach (['staff', 'customer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get('/users')->assertForbidden();
            $this->get('/users/create')->assertForbidden();
            $this->get('/users/'.$target->id.'/edit')->assertForbidden();
            $this->post('/users', [])->assertForbidden();
            $this->put('/users/'.$target->id, [])->assertForbidden();
        }
    }

    public function test_admin_can_create_filter_and_edit_users_without_changing_password(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = ['name' => 'Warehouse Operator', 'email' => 'warehouse@example.test', 'role' => 'staff', 'is_active' => '1'];
        $this->post('/users', $data + ['password' => 'test-secret-123', 'password_confirmation' => 'test-secret-123'])->assertSessionHasNoErrors()->assertRedirect('/users');
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertTrue(Hash::check('test-secret-123', $user->password));
        $this->get('/users?q=Warehouse&role=staff&active=1')->assertOk()->assertSee($user->email);
        $this->get('/users?active=0')->assertOk()->assertDontSee($user->email);
        $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('Warehouse Operator');
        $this->put('/users/'.$user->id, array_replace($data, ['role' => 'customer', 'password' => 'injected-secret']))->assertSessionHasNoErrors();
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertTrue(Hash::check('test-secret-123', $user->fresh()->password));
        $this->post('/users', $data + ['password' => 'test-secret-123', 'password_confirmation' => 'test-secret-123'])->assertSessionHasErrors('email');
    }

    public function test_admin_cannot_disable_or_demote_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $data = $admin->only(['name', 'email', 'role']) + ['is_active' => '1'];
        $this->put('/users/'.$admin->id, array_replace($data, ['is_active' => '0']))->assertSessionHasErrors('role');
        $this->put('/users/'.$admin->id, array_replace($data, ['role' => 'staff']))->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_deactivation_preserves_history_blocks_login_and_revokes_existing_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'remember_token' => 'old-token']);
        $sale = Sale::create(['user_id' => $customer->id, 'sale_number' => 'SAL-KEEP-HISTORY', 'total' => 0, 'sold_at' => now()]);
        $data = $customer->only(['name', 'email', 'role']) + ['is_active' => '0'];
        $this->actingAs($admin)->put('/users/'.$customer->id, $data)->assertSessionHasNoErrors();
        $this->assertFalse($customer->fresh()->is_active);
        $this->assertNull($customer->fresh()->remember_token);
        $this->assertSame($customer->id, $sale->fresh()->user->id);
        $this->actingAs($customer->fresh())->withSession(['cart' => [1 => 2]])->get('/profile')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertNull(session('cart'));
        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($admin)->put('/users/'.$customer->id, array_replace($data, ['is_active' => '1']))->assertSessionHasNoErrors();
        $this->post('/logout');
        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])->assertRedirect('/catalog');
        $this->assertAuthenticatedAs($customer);
    }
}
