<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private function userWithCart(int $priceCents, int $quantity = 1): User
    {
        $user = User::factory()->create();
        $offer = $this->offer($priceCents, keys: $quantity);
        $user->cartItems()->create(['offer_id' => $offer->id, 'quantity' => $quantity]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_check_accepts_valid_code_case_insensitively(): void
    {
        Coupon::create(['code' => 'WELCOME10', 'percent_off' => 10]);

        $this->postJson('/api/coupons/check', ['code' => ' welcome10 '])
            ->assertOk()
            ->assertJsonPath('data', ['code' => 'WELCOME10', 'percent_off' => 10]);
    }

    public function test_check_rejects_unknown_expired_and_used_up_codes(): void
    {
        Coupon::create(['code' => 'OLD', 'percent_off' => 10, 'expires_at' => now()->subDay()]);
        $usedUp = Coupon::create(['code' => 'ONCE', 'percent_off' => 10, 'max_uses' => 1]);
        $usedUp->increment('times_used');

        foreach (['NOPE', 'OLD', 'ONCE'] as $code) {
            $this->postJson('/api/coupons/check', ['code' => $code])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('code');
        }
    }

    public function test_checkout_applies_discount_and_records_it(): void
    {
        Coupon::create(['code' => 'AMOEBA20', 'percent_off' => 20]);
        $this->userWithCart(1999, 2); // subtotal 3998

        $this->postJson('/api/checkout', ['payment_method' => 'card', 'coupon_code' => 'amoeba20'])
            ->assertCreated()
            ->assertJsonPath('data.subtotal_cents', 3998)
            ->assertJsonPath('data.discount_cents', 799) // 20% of 3998 = 799.6, rounded down
            ->assertJsonPath('data.total_cents', 3199)
            ->assertJsonPath('data.coupon_code', 'AMOEBA20');

        $this->assertSame(1, Coupon::first()->times_used);
    }

    public function test_checkout_with_invalid_code_fails_without_selling_anything(): void
    {
        $user = $this->userWithCart(1000);

        $this->postJson('/api/checkout', ['payment_method' => 'card', 'coupon_code' => 'FAKE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('coupon_code');

        $this->assertSame(0, $user->orders()->count());
        $this->assertSame(1, $user->cartItems()->count());
    }

    public function test_limited_code_cannot_be_used_more_than_allowed(): void
    {
        Coupon::create(['code' => 'ONCE', 'percent_off' => 50, 'max_uses' => 1]);

        $this->userWithCart(1000);
        $this->postJson('/api/checkout', ['payment_method' => 'card', 'coupon_code' => 'ONCE'])->assertCreated();

        $this->userWithCart(1000);
        $this->postJson('/api/checkout', ['payment_method' => 'card', 'coupon_code' => 'ONCE'])->assertUnprocessable();
    }
}
