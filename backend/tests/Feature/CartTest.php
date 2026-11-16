<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_quantity_adds_offer_and_returns_totals(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $offer = $this->offer(1000, keys: 5);

        $this->putJson("/api/cart/items/{$offer->id}", ['quantity' => 2])
            ->assertOk()
            ->assertJsonPath('data.count', 2)
            ->assertJsonPath('data.total_cents', 2000)
            ->assertJsonPath('data.items.0.offer.id', $offer->id)
            ->assertJsonPath('data.items.0.offer.seller.name', $offer->seller->name)
            ->assertJsonPath('data.items.0.product.id', $offer->product_id)
            ->assertJsonPath('data.items.0.line_total_cents', 2000);

        // Setting again replaces the quantity, it doesn't add.
        $this->putJson("/api/cart/items/{$offer->id}", ['quantity' => 1])->assertJsonPath('data.count', 1);
    }

    public function test_the_same_product_from_two_sellers_is_two_lines_at_their_own_prices(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $cheap = $this->offer(1000, keys: 1);
        $pricey = $this->offer(1500, keys: 1, product: $cheap->product);

        $this->putJson("/api/cart/items/{$cheap->id}", ['quantity' => 1]);
        $this->putJson("/api/cart/items/{$pricey->id}", ['quantity' => 1])
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.total_cents', 2500);
    }

    public function test_cannot_add_more_than_the_sellers_stock(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $offer = $this->offer(keys: 2);
        $this->offer(keys: 50, product: $offer->product); // another seller's keys don't count

        $this->putJson("/api/cart/items/{$offer->id}", ['quantity' => 3])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'Only 2 keys left from this seller.');
    }

    public function test_cannot_add_sold_out_offer(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $offer = $this->offer(keys: 0);

        $this->putJson("/api/cart/items/{$offer->id}", ['quantity' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', 'This offer is out of stock.');
    }

    public function test_remove_item(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $offer = $this->offer();
        $user->cartItems()->create(['offer_id' => $offer->id, 'quantity' => 1]);

        $this->deleteJson("/api/cart/items/{$offer->id}")
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_carts_are_private_to_each_user(): void
    {
        $other = User::factory()->create();
        $other->cartItems()->create(['offer_id' => $this->offer()->id, 'quantity' => 1]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/cart')->assertOk()->assertJsonPath('data.count', 0);
    }

    public function test_guest_preview_prices_items_on_the_server_and_skips_unknown_offers(): void
    {
        $offer = $this->offer(1500, keys: 3);

        $this->postJson('/api/cart/preview', ['items' => [
            ['offer_id' => $offer->id, 'quantity' => 2],
            ['offer_id' => 999999, 'quantity' => 1],
        ]])
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.total_cents', 3000)
            ->assertJsonPath('data.items.0.offer.stock', 3);
    }

    public function test_guest_preview_accepts_an_empty_cart(): void
    {
        $this->postJson('/api/cart/preview', ['items' => []])
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_merge_adds_guest_items_capped_at_stock_and_skips_sold_out(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $inCart = $this->offer(keys: 4);
        $newOne = $this->offer(keys: 5);
        $soldOut = $this->offer(keys: 0);
        $user->cartItems()->create(['offer_id' => $inCart->id, 'quantity' => 3]);

        $this->postJson('/api/cart/merge', ['items' => [
            ['offer_id' => $inCart->id, 'quantity' => 2], // 3 + 2 = 5, but only 4 in stock
            ['offer_id' => $newOne->id, 'quantity' => 2],
            ['offer_id' => $soldOut->id, 'quantity' => 1],
        ]])->assertOk()->assertJsonCount(2, 'data.items');

        $this->assertSame(4, $user->cartItems()->where('offer_id', $inCart->id)->value('quantity'));
        $this->assertSame(2, $user->cartItems()->where('offer_id', $newOne->id)->value('quantity'));
    }
}
