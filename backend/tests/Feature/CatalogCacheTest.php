<?php

namespace Tests\Feature;

use App\Models\HomeBlock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        Sanctum::actingAs($admin);
    }

    public function test_homepage_is_cached_until_an_admin_changes_a_block(): void
    {
        $block = HomeBlock::create(['key' => 'row', 'type' => 'product_row', 'title' => 'Old title', 'position' => 1, 'settings' => []]);
        $this->getJson('/api/home')->assertJsonPath('data.0.title', 'Old title');

        // A change made directly in the database isn't seen yet: the homepage comes from the cache.
        $block->update(['title' => 'Changed in the database']);
        $this->getJson('/api/home')->assertJsonPath('data.0.title', 'Old title');

        // Admin changes clear the cache, so they show straight away.
        $this->admin();
        $this->putJson("/api/admin/home-blocks/{$block->id}", ['title' => 'New title', 'position' => 1, 'enabled' => true])->assertOk();
        $this->getJson('/api/home')->assertJsonPath('data.0.title', 'New title');
    }

    public function test_checkout_clears_the_cache_so_sold_out_products_disappear_from_the_homepage(): void
    {
        HomeBlock::create(['key' => 'row', 'type' => 'product_row', 'title' => 'Row', 'position' => 1, 'settings' => []]);
        $offer = $this->offer(keys: 1);
        $this->getJson('/api/home')->assertJsonCount(1, 'data.0.content.products');

        $buyer = User::factory()->create();
        $buyer->cartItems()->create(['offer_id' => $offer->id, 'quantity' => 1]);
        Sanctum::actingAs($buyer);
        $this->postJson('/api/checkout', ['payment_method' => 'card'])->assertCreated();

        $this->getJson('/api/home')->assertJsonCount(0, 'data.0.content.products'); // last key sold
    }

    public function test_filter_options_are_cached_and_cleared_when_keys_are_added(): void
    {
        $offer = $this->offer(1000, keys: 0, product: Product::factory()->create(['platform' => 'Steam']));
        $this->getJson('/api/products/filters')->assertJsonPath('data.platforms.0.count', 1);

        Product::factory()->create(['platform' => 'Steam']); // not visible yet: cached
        $this->getJson('/api/products/filters')->assertJsonPath('data.platforms.0.count', 1);

        $this->admin();
        $this->postJson("/api/admin/offers/{$offer->id}/keys", ['codes' => 'AAA-111'])->assertCreated();
        $this->getJson('/api/products/filters')->assertJsonPath('data.platforms.0.count', 2);
    }
}
