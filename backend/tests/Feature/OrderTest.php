<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(User $user): int
    {
        $user->cartItems()->create(['offer_id' => $this->offer()->id, 'quantity' => 1]);
        Sanctum::actingAs($user);

        return $this->postJson('/api/checkout', ['payment_method' => 'card'])->assertCreated()->json('data.id');
    }

    public function test_order_list_shows_own_orders_without_key_codes(): void
    {
        $user = User::factory()->create();
        $this->placeOrder($user);
        $this->placeOrder(User::factory()->create()); // someone else's

        Sanctum::actingAs($user);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.items.0.keys');
    }

    public function test_order_page_shows_key_codes_to_the_owner(): void
    {
        $user = User::factory()->create();
        $orderId = $this->placeOrder($user);

        $this->getJson("/api/orders/{$orderId}")
            ->assertOk()
            ->assertJsonCount(1, 'data.items.0.keys');
    }

    public function test_other_users_orders_are_not_found(): void
    {
        $orderId = $this->placeOrder(User::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/orders/{$orderId}")->assertNotFound();
    }
}
