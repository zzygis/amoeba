<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_list_and_remove(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = $this->offer(keys: 2)->product;

        $this->putJson("/api/wishlist/{$product->id}")->assertNoContent();
        $this->putJson("/api/wishlist/{$product->id}")->assertNoContent(); // twice is harmless

        $this->getJson('/api/wishlist')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.stock', 2);

        $this->deleteJson("/api/wishlist/{$product->id}")->assertNoContent();
        $this->getJson('/api/wishlist')->assertJsonCount(0, 'data');
    }

    public function test_wishlists_are_private(): void
    {
        $other = User::factory()->create();
        $other->wishlist()->attach(Product::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/wishlist')->assertJsonCount(0, 'data');
    }

    public function test_requires_login(): void
    {
        $this->getJson('/api/wishlist')->assertUnauthorized();
    }
}
