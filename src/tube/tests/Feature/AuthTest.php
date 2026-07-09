<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_creates_user_and_redirects_to_login(): void
    {
        $response = $this->post(route('auth.store'), [
            'name' => 'taro',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('auth.login'));
        $this->assertDatabaseHas('users', ['name' => 'taro']);
    }

    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create(['name' => 'hanako', 'password' => bcrypt('secret123')]);

        $response = $this->post(route('auth.authenticate'), [
            'name' => 'hanako',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        User::factory()->create(['name' => 'hanako', 'password' => bcrypt('secret123')]);

        $response = $this->post(route('auth.authenticate'), [
            'name' => 'hanako',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('name');
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        User::factory()->create(['name' => 'hanako', 'password' => bcrypt('secret123')]);

        foreach (range(1, 5) as $i) {
            $this->post(route('auth.authenticate'), ['name' => 'hanako', 'password' => 'bad']);
        }

        $response = $this->post(route('auth.authenticate'), ['name' => 'hanako', 'password' => 'bad']);
        $response->assertStatus(429);
    }

    public function test_logout_requires_post(): void
    {
        $user = User::factory()->create();

        $this->get(route('auth.logout'))->assertStatus(405);

        $this->actingAs($user)->post(route('auth.logout'))->assertRedirect(route('dashboard'));
        $this->assertGuest();
    }
}
