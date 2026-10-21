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
        $data = ['name' => 'New name', 'email' => 'updated@example.test', 'current_password' => 'password'];
        $this->patch('/profile', $data + ['role' => 'admin', 'id' => $other->id])->assertSessionHasNoErrors();
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertSame('updated@example.test', $user->fresh()->email);
        $this->assertNotSame('New name', $other->fresh()->name);
        $this->patch('/profile', array_replace($data, ['email' => $other->email]))->assertSessionHasErrors('email');
        $this->patch('/profile', array_replace($data, ['current_password' => 'wrong']))->assertSessionHasErrors('current_password');
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
