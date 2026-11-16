<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\HomeBlock;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    private function block(string $type, string $title, int $position, array $settings = [], bool $enabled = true): HomeBlock
    {
        return HomeBlock::create(['key' => $title, 'type' => $type, 'title' => $title, 'position' => $position, 'enabled' => $enabled, 'settings' => $settings]);
    }

    public function test_home_returns_enabled_blocks_in_position_order_with_their_content(): void
    {
        $this->block('product_row', 'Second', 20, ['sort' => 'popular']);
        $this->block('banner', 'First', 10, ['sort' => 'discount']);
        $this->block('platforms', 'Hidden', 5, enabled: false);
        $this->offer(1000, product: Product::factory()->create(['original_price_cents' => 4000]));

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'banner')
            ->assertJsonPath('data.0.title', 'First')
            ->assertJsonPath('data.0.content.product.price_cents', 1000)
            ->assertJsonPath('data.1.type', 'product_row')
            ->assertJsonPath('data.1.content.see_all', ['sort' => 'popular']);
    }

    public function test_product_rows_show_one_listing_per_game_and_skip_sold_out(): void
    {
        $this->block('product_row', 'Row', 1, ['sort' => 'price_asc', 'limit' => 6]);
        $game = Game::factory()->create();
        $this->offer(1000, product: Product::factory()->for($game)->create(['platform' => 'Steam']));
        $this->offer(2000, product: Product::factory()->for($game)->create(['platform' => 'Xbox']));
        $this->offer(500, keys: 0); // sold out

        $this->getJson('/api/home')
            ->assertJsonCount(1, 'data.0.content.products')
            ->assertJsonPath('data.0.content.products.0.platform', 'Steam');
    }

    public function test_row_settings_filter_by_type_and_price(): void
    {
        $this->block('product_row', 'DLC under 10', 1, ['type' => 'dlc', 'max_price' => 10]);
        $this->offer(900, product: Product::factory()->for(Game::factory()->state(['type' => 'dlc']))->create());
        $this->offer(1900, product: Product::factory()->for(Game::factory()->state(['type' => 'dlc']))->create());
        $this->offer(500, product: Product::factory()->for(Game::factory()->state(['type' => 'game']))->create());

        $this->getJson('/api/home')
            ->assertJsonCount(1, 'data.0.content.products')
            ->assertJsonPath('data.0.content.products.0.price_cents', 900)
            ->assertJsonPath('data.0.content.see_all', ['sort' => 'popular', 'max_price' => 10, 'type' => 'dlc']);
    }

    public function test_platforms_block_counts_products_most_first(): void
    {
        $this->block('platforms', 'Platforms', 1);
        Product::factory()->create(['platform' => 'EA App']);
        Product::factory()->count(3)->create(['platform' => 'Xbox']);
        Product::factory()->count(2)->create(['platform' => 'Steam']);

        // Not alphabetical: most products first.
        $this->getJson('/api/home')->assertJsonPath('data.0.content.platforms', [
            ['name' => 'Xbox', 'count' => 3], ['name' => 'Steam', 'count' => 2], ['name' => 'EA App', 'count' => 1],
        ]);
    }
}
