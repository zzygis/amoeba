<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_order_hands_out_keys_and_empties_cart(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $offer = $this->offer(1250, keys: 3);
        $user->cartItems()->create(['offer_id' => $offer->id, 'quantity' => 2]);

        $response = $this->postJson('/api/checkout', ['payment_method' => 'card'])
            ->assertCreated()
            ->assertJsonPath('data.total_cents', 2500)
            ->assertJsonPath('data.payment_method', 'card')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.seller_name', $offer->seller->name)
            ->assertJsonCount(2, 'data.items.0.keys');

        // The codes returned are real, now-sold keys of this offer.
        $soldCodes = ProductKey::whereNotNull('sold_at')->get()->pluck('code')->sort()->values()->all();
        $this->assertSame($soldCodes, collect($response->json('data.items.0.keys'))->sort()->values()->all());

        $this->assertSame(1, $offer->availableKeys()->count());
        $this->assertSame(0, $user->cartItems()->count());
    }

    public function test_keys_come_from_the_chosen_seller_only(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $cheap = $this->offer(1000, keys: 5);
        $chosen = $this->offer(1400, keys: 2, product: $cheap->product);
        $user->cartItems()->create(['offer_id' => $chosen->id, 'quantity' => 2]);

        $this->postJson('/api/checkout', ['payment_method' => 'card'])
            ->assertCreated()
            ->assertJsonPath('data.total_cents', 2800);

        $this->assertSame(0, $chosen->availableKeys()->count());
        $this->assertSame(5, $cheap->availableKeys()->count());
    }

    public function test_order_keeps_the_price_paid_even_if_the_price_changes_later(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $offer = $this->offer(1000);
        $user->cartItems()->create(['offer_id' => $offer->id, 'quantity' => 1]);

        $orderId = $this->postJson('/api/checkout', ['payment_method' => 'paypal'])->json('data.id');
        $offer->update(['price_cents' => 9999]);

        $this->getJson("/api/orders/{$orderId}")->assertJsonPath('data.items.0.price_cents', 1000);
    }

    public function test_not_enough_stock_rolls_back_the_whole_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $enough = $this->offer(keys: 5);
        $short = $this->offer(keys: 1);
        $user->cartItems()->create(['offer_id' => $enough->id, 'quantity' => 1]);
        $user->cartItems()->create(['offer_id' => $short->id, 'quantity' => 2]);

        $this->postJson('/api/checkout', ['payment_method' => 'card'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        // Nothing happened: no order, no keys sold (not even for the offer that had enough), cart intact.
        $this->assertSame(0, Order::count());
        $this->assertSame(0, ProductKey::whereNotNull('sold_at')->count());
        $this->assertSame(2, $user->cartItems()->count());
    }

    public function test_the_same_key_is_never_sold_twice(): void
    {
        $offer = $this->offer(keys: 1);
        [$first, $second] = User::factory()->count(2)->create();
        $first->cartItems()->create(['offer_id' => $offer->id, 'quantity' => 1]);
        $second->cartItems()->create(['offer_id' => $offer->id, 'quantity' => 1]);

        Sanctum::actingAs($first);
        $this->postJson('/api/checkout', ['payment_method' => 'card'])->assertCreated();

        Sanctum::actingAs($second);
        $this->postJson('/api/checkout', ['payment_method' => 'card'])->assertUnprocessable();
    }

    public function test_empty_cart_cannot_be_checked_out(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/checkout', ['payment_method' => 'card'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cart.0', 'Your cart is empty.');
    }

    public function test_payment_method_must_be_valid(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/checkout', ['payment_method' => 'bitcoin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_method');
    }

    public function test_checkout_emails_the_keys_to_the_customer(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);
        Sanctum::actingAs($user);
        $offer = $this->offer();
        $user->cartItems()->create(['offer_id' => $offer->id, 'quantity' => 1]);

        $this->postJson('/api/checkout', ['payment_method' => 'card'])->assertCreated();

        Mail::assertSent(OrderConfirmation::class, function (OrderConfirmation $mail) use ($offer) {
            $code = $offer->keys()->first()->code;

            return $mail->hasTo('ada@example.com') && str_contains($mail->render(), $code);
        });
    }
}
