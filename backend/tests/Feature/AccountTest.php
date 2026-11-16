<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(User $user): string
    {
        return $user->createToken('web')->plainTextToken;
    }

    public function test_update_name_and_email(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);
        $token = $this->loginAs($user);

        $this->withToken($token)->putJson('/api/user', ['name' => 'New Name', 'email' => 'taken@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->withToken($token)->putJson('/api/user', ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_change_password_needs_current_password_and_logs_out_other_devices(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = $this->loginAs($user);
        $this->loginAs($user); // another device

        $this->withToken($token)->putJson('/api/user/password', [
            'current_password' => 'wrong',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->withToken($token)->putJson('/api/user/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count()); // only this device is still logged in
    }

    public function test_delete_account_needs_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);
        $token = $this->loginAs($user);

        $this->withToken($token)->deleteJson('/api/user', ['password' => 'wrong'])->assertUnprocessable();
        $this->withToken($token)->deleteJson('/api/user', ['password' => 'secret-password'])->assertNoContent();

        $this->assertNull($user->fresh());
    }

    public function test_forgot_password_sends_a_link_to_the_frontend_and_reset_works(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $user->createToken('web');

        $this->postJson('/api/forgot-password', ['email' => 'ada@example.com'])->assertOk();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user, &$token) {
            $token = $notification->token;
            $url = call_user_func(ResetPassword::$createUrlCallback, $user, $notification->token);

            return str_starts_with($url, config('app.frontend_url').'/reset-password?token=');
        });

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ada@example.com',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertOk();

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count()); // logged out everywhere
    }

    public function test_forgot_password_does_not_reveal_whether_an_email_exists(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists for that email, we have sent a link to reset the password.');
    }

    public function test_reset_with_a_bad_token_fails(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'ada@example.com',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
