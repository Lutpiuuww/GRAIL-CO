<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_cannot_access_profile(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->patch('/profile')->assertRedirect('/login');
        $this->put('/profile/password')->assertRedirect('/login');
    }

    public function test_dashboard_metrics_follow_role(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get('/dashboard')->assertOk()->assertSee('Unit tersedia')->assertDontSee('Total valuation')->assertDontSee('Revenue');
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/dashboard')->assertOk()->assertSee('Total valuation')->assertSee('Revenue')->assertDontSee('Unit tersedia');
    }

    public function test_profile_updates_only_authenticated_identity_and_not_role(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Keluar dari akun');
        $data = ['name' => 'New name', 'email' => 'updated@example.test', 'phone' => '081234567890', 'street_address' => 'Jalan Merdeka Nomor 10', 'current_password' => 'password'];
        $this->patch('/profile', $data + ['role' => 'admin', 'id' => $other->id])->assertSessionHasNoErrors();
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertSame('updated@example.test', $user->fresh()->email);
        $this->assertSame('081234567890', $user->fresh()->phone);
        $this->assertSame('Jalan Merdeka Nomor 10', $user->fresh()->street_address);
        $this->assertNotSame('New name', $other->fresh()->name);
        $this->patch('/profile', array_replace($data, ['email' => $other->email]))->assertSessionHasErrors('email');
        $this->patch('/profile', array_replace($data, ['current_password' => 'wrong']))->assertSessionHasErrors('current_password');
        $this->patch('/profile', array_replace($data, ['phone' => 'bukan nomor']))->assertSessionHasErrors('phone');
    }

    public function test_password_change_requires_current_password_and_confirmation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $data = ['current_password' => 'password', 'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123'];
        $this->put('/profile/password', array_replace($data, ['current_password' => 'wrong']))->assertSessionHasErrors('current_password');
        $this->put('/profile/password', array_replace($data, ['password_confirmation' => 'mismatch']))->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->put('/profile/password', $data)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_profile_contact_can_be_saved_without_password_but_email_change_requires_it(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user)->patch('/profile', [
            'name' => 'Luthfi', 'email' => $user->email,
            'phone' => '081234567890', 'street_address' => 'Jalan Merdeka Nomor 10',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Luthfi', $user->fresh()->name);
        $this->assertSame('081234567890', $user->fresh()->phone);

        $this->patch('/profile', ['name' => 'Luthfi', 'email' => 'baru@example.test'])->assertSessionHasErrors('current_password');
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_profile_can_save_a_complete_address_and_reject_wrong_city(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $data = [
            'name' => $user->name, 'email' => $user->email,
            'street_address' => 'Jalan Merdeka Nomor 10',
            'address_province' => 'Jawa Barat', 'address_city' => 'Kota Bandung',
            'address_postal_code' => '40111',
        ];

        $this->actingAs($user)->patch('/profile', $data)->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasSavedDeliveryAddress());
        $this->patch('/profile', array_replace($data, ['address_city' => 'Kota Surabaya']))->assertSessionHasErrors('address_city');
        $this->assertSame('Kota Bandung', $user->fresh()->address_city);
    }

    public function test_login_is_limited_after_five_failures_and_recovers_after_timeout(): void
    {
        $user = User::factory()->create(['email' => 'limited@example.test']);
        $key = 'login:'.hash('sha256', $user->email.'|127.0.0.1');
        RateLimiter::clear($key);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, RateLimiter::attempts($key));
    }
}
