<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_account_and_returns_a_working_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated()->assertJsonPath('user.email', 'ada@example.com');

        $this->withToken($response->json('token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.name', 'Ada');
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_with_correct_password_returns_token(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'secret-password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_login_with_wrong_password_fails_with_generic_message(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'These credentials do not match our records.');
    }

    public function test_login_is_rate_limited(): void
    {
        $attempt = fn () => $this->postJson('/api/login', ['email' => 'ada@example.com', 'password' => 'guess']);

        for ($i = 0; $i < 5; $i++) {
            $attempt()->assertUnprocessable();
        }

        $attempt()->assertTooManyRequests();
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_protected_routes_answer_401_json_without_a_token(): void
    {
        $this->get('/api/user')->assertUnauthorized()->assertJsonStructure(['message']);
        $this->getJson('/api/cart')->assertUnauthorized();
        $this->postJson('/api/checkout')->assertUnauthorized();
    }
}
