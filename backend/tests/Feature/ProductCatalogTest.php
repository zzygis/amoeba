<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\HomeBlock;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductKey;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_products_with_pagination_meta(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'name', 'title', 'platform', 'region', 'price_cents',
                    'original_price_cents', 'discount_percent', 'cover_url', 'stock']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_sort_az_orders_by_game_title(): void
    {
        Product::factory()->for(Game::factory()->state(['title' => 'Zelda']))->create();
        Product::factory()->for(Game::factory()->state(['title' => 'Alan Wake']))->create();

        $this->getJson('/api/products?sort=az')
            ->assertJsonPath('data.0.title', 'Alan Wake')
            ->assertJsonPath('data.1.title', 'Zelda');
    }

    public function test_default_sort_is_most_popular_first(): void
    {
        Product::factory()->for(Game::factory()->state(['title' => 'Niche', 'steam_reviews' => 10]))->create();
        Product::factory()->for(Game::factory()->state(['title' => 'Hit', 'steam_reviews' => 900000]))->create();

        $this->getJson('/api/products')->assertJsonPath('data.0.title', 'Hit');
    }

    public function test_price_is_the_cheapest_offer_that_still_has_keys(): void
    {
        $product = Product::factory()->create(['original_price_cents' => 4000]);
        $this->offer(500, keys: 0, product: $product);  // cheapest, but sold out
        $best = $this->offer(1000, keys: 2, product: $product);
        $this->offer(1500, keys: 9, product: $product);

        $this->getJson('/api/products')
            ->assertJsonPath('data.0.price_cents', 1000)
            ->assertJsonPath('data.0.offer_id', $best->id)
            ->assertJsonPath('data.0.discount_percent', 75)
            ->assertJsonPath('data.0.stock', 11); // all sellers together
    }

    public function test_sold_out_product_has_no_price(): void
    {
        $this->offer(keys: 0);

        $this->getJson('/api/products')
            ->assertJsonPath('data.0.price_cents', null)
            ->assertJsonPath('data.0.offer_id', null)
            ->assertJsonPath('data.0.stock', 0);
    }

    public function test_sort_by_price_and_discount(): void
    {
        $this->offer(3000, product: Product::factory()->create(['original_price_cents' => null]));
        $this->offer(1000, product: Product::factory()->create(['original_price_cents' => 2000])); // -50%
        $this->offer(2000, product: Product::factory()->create(['original_price_cents' => 8000])); // -75%
        $this->offer(100, keys: 0); // sold out: always last in price sorts

        $prices = fn (string $sort) => collect($this->getJson("/api/products?sort={$sort}")->json('data'))->pluck('price_cents')->all();

        $this->assertSame([1000, 2000, 3000, null], $prices('price_asc'));
        $this->assertSame([3000, 2000, 1000, null], $prices('price_desc'));
        $this->assertSame([2000, 1000], array_slice($prices('discount'), 0, 2));
    }

    public function test_sort_by_release_date(): void
    {
        Product::factory()->for(Game::factory()->state(['title' => 'Old', 'release_date' => '2011-01-01']))->create();
        Product::factory()->for(Game::factory()->state(['title' => 'New', 'release_date' => '2025-01-01']))->create();

        $this->getJson('/api/products?sort=newest')->assertJsonPath('data.0.title', 'New');
        $this->getJson('/api/products?sort=oldest')->assertJsonPath('data.0.title', 'Old');
    }

    public function test_unknown_sort_is_rejected(): void
    {
        $this->getJson('/api/products?sort=random')->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_filters_by_platform_region_and_price_range(): void
    {
        $this->offer(1500, product: Product::factory()->create(['platform' => 'Steam', 'region' => 'GLOBAL']));
        $this->offer(2500, product: Product::factory()->create(['platform' => 'Xbox', 'region' => 'EUROPE']));
        $this->offer(4500, product: Product::factory()->create(['platform' => 'PlayStation', 'region' => 'EUROPE']));

        $this->getJson('/api/products?platform=Steam,Xbox')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/products?region=EUROPE')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/products?min_price=20&max_price=30')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.platform', 'Xbox');
        $this->getJson('/api/products?platform=Steam&region=EUROPE')->assertJsonPath('meta.total', 0);
    }

    public function test_filter_options_list_platforms_regions_and_price_range(): void
    {
        $this->offer(500, product: Product::factory()->create(['platform' => 'Steam', 'region' => 'GLOBAL']));
        $this->offer(4000, product: Product::factory()->create(['platform' => 'Steam', 'region' => 'EUROPE']));
        $this->offer(1000, product: Product::factory()->create(['platform' => 'Xbox', 'region' => 'GLOBAL']));

        $this->getJson('/api/products/filters')
            ->assertOk()
            ->assertJsonPath('data.platforms', [['name' => 'Steam', 'count' => 2], ['name' => 'Xbox', 'count' => 1]])
            ->assertJsonPath('data.regions', [['name' => 'EUROPE', 'count' => 1], ['name' => 'GLOBAL', 'count' => 2]])
            ->assertJsonPath('data.price', ['min_cents' => 500, 'max_cents' => 4000]);
    }

    public function test_per_page_limits_page_size(): void
    {
        Product::factory()->count(5)->create();

        $this->getJson('/api/products?per_page=2')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.last_page', 3);
        $this->getJson('/api/products?per_page=100')->assertUnprocessable();
    }

    public function test_search_is_case_insensitive_and_matches_words_in_any_order(): void
    {
        Product::factory()->for(Game::factory()->state(['title' => 'Red Dead Redemption 2']))->create();
        Product::factory()->for(Game::factory()->state(['title' => 'Split Fiction']))->create();

        $this->getJson('/api/products?search=DEAD red&sort=az')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Red Dead Redemption 2');
    }

    public function test_search_copes_with_typos_and_partial_words(): void
    {
        Product::factory()->for(Game::factory()->state(['title' => 'Elden Ring']))->create();
        Product::factory()->for(Game::factory()->state(['title' => 'Cyberpunk 2077']))->create();
        Product::factory()->for(Game::factory()->state(['title' => 'The Witcher 3: Wild Hunt']))->create();
        Product::factory()->for(Game::factory()->state(['title' => "Baldur's Gate 3"]))->create();

        $titles = fn (string $q) => collect($this->getJson('/api/products?search='.urlencode($q))->json('data'))->pluck('title')->all();

        $this->assertSame(['Elden Ring'], $titles('eldn rin'));        // typo + partial word
        $this->assertSame(['Cyberpunk 2077'], $titles('cyberpnk'));   // missing letter
        $this->assertSame(['The Witcher 3: Wild Hunt'], $titles('witcer')); // missing letter
        $this->assertSame(["Baldur's Gate 3"], $titles('baldurs'));    // punctuation ignored
        $this->assertSame([], $titles('zelda'));                        // unrelated stays empty
    }

    public function test_search_treats_like_wildcards_as_plain_text(): void
    {
        Product::factory()->create();

        // Unescaped, "%" would match every title.
        $this->getJson('/api/products?search=%25')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_longer_than_100_characters_is_rejected(): void
    {
        $this->getJson('/api/products?search='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_stock_counts_only_unsold_keys(): void
    {
        $offer = $this->offer(keys: 3);
        ProductKey::factory()->count(2)->for($offer)->sold()->create();

        $this->getJson('/api/products')->assertJsonPath('data.0.stock', 3);
        $this->getJson("/api/products/{$offer->product->slug}")->assertJsonPath('data.stock', 3);
    }

    public function test_show_returns_game_details_discount_and_other_listings(): void
    {
        $game = Game::factory()->create(['title' => 'Elden Ring', 'developer' => 'FromSoftware']);
        $steam = Product::factory()->for($game)->create(['slug' => 'elden-ring-steam-global', 'original_price_cents' => 6000]);
        $this->offer(3000, product: $steam);
        Product::factory()->for($game)->create(['platform' => 'PlayStation', 'region' => 'EUROPE']);
        Product::factory()->create(); // a different game: must not be listed

        $this->getJson("/api/products/{$steam->slug}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Elden Ring Steam Key GLOBAL')
            ->assertJsonPath('data.discount_percent', 50)
            ->assertJsonPath('data.game.developer', 'FromSoftware')
            ->assertJsonCount(1, 'data.other_listings')
            ->assertJsonPath('data.other_listings.0.platform', 'PlayStation');
    }

    public function test_show_lists_every_sellers_offer_cheapest_first_sold_out_last(): void
    {
        $product = Product::factory()->create(['original_price_cents' => 2000]);
        $this->offer(900, keys: 0, product: $product);
        $this->offer(1500, keys: 4, product: $product);
        $this->offer(1000, keys: 1, product: $product);

        $this->getJson("/api/products/{$product->slug}")
            ->assertJsonCount(3, 'data.offers')
            ->assertJsonPath('data.offers.0.price_cents', 1000)
            ->assertJsonPath('data.offers.0.discount_percent', 50)
            ->assertJsonPath('data.offers.1.price_cents', 1500)
            ->assertJsonPath('data.offers.2.stock', 0)
            ->assertJsonStructure(['data' => ['offers' => [['id', 'price_cents', 'stock', 'seller' => ['name', 'rating', 'rating_label', 'verified']]]]]);
    }

    public function test_show_unknown_slug_returns_404(): void
    {
        $this->getJson('/api/products/does-not-exist')->assertNotFound();
    }

    public function test_key_codes_are_encrypted_at_rest_and_never_returned(): void
    {
        $key = ProductKey::factory()->create(['code' => 'SECRET-CODE-12345']);

        $this->assertNotSame('SECRET-CODE-12345', $key->getRawOriginal('code'));
        $this->assertStringNotContainsString('SECRET-CODE-12345', $this->getJson('/api/products')->getContent());
        $this->assertStringNotContainsString('SECRET-CODE-12345', $this->getJson("/api/products/{$key->offer->product->slug}")->getContent());
    }

    public function test_seeder_can_run_twice_without_duplicating_anything(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [Game::count(), Product::count(), Offer::count(), ProductKey::count(), HomeBlock::count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [Game::count(), Product::count(), Offer::count(), ProductKey::count(), HomeBlock::count()]);
        $this->assertSame(0, Product::where('slug', 'sekiro-shadows-die-twice-steam-global')->first()->keys()->count());
    }

    public function test_filters_by_product_type_and_lists_types(): void
    {
        Product::factory()->for(Game::factory()->state(['type' => 'game']))->create();
        Product::factory()->for(Game::factory()->state(['type' => 'dlc']))->create();
        Product::factory()->for(Game::factory()->state(['type' => 'software']))->create();

        $this->getJson('/api/products?type=dlc,software')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/products/filters')->assertJsonPath('data.types', [
            ['name' => 'game', 'count' => 1], ['name' => 'dlc', 'count' => 1], ['name' => 'software', 'count' => 1],
        ]);
    }

    public function test_similar_returns_other_games_sharing_genres_best_match_first(): void
    {
        $rpg = Product::factory()->for(Game::factory()->state(['genres' => ['Action', 'RPG']]))->create();
        Product::factory()->for($rpg->game)->create(['platform' => 'Xbox']); // same game: excluded
        $this->offer(product: Product::factory()->for(Game::factory()->state(['title' => 'Both', 'genres' => ['RPG', 'Action'], 'steam_reviews' => 1]))->create());
        $this->offer(product: Product::factory()->for(Game::factory()->state(['title' => 'One', 'genres' => ['Action'], 'steam_reviews' => 999999]))->create());
        Product::factory()->for(Game::factory()->state(['title' => 'None', 'genres' => ['Racing']]))->create();

        $this->getJson("/api/products/{$rpg->slug}/similar")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Both')
            ->assertJsonPath('data.1.title', 'One');
    }

    public function test_similar_puts_the_base_game_of_a_dlc_first(): void
    {
        $dlc = Product::factory()->for(Game::factory()->state(['title' => 'Elden Ring Shadow of the Erdtree', 'type' => 'dlc', 'genres' => ['Action']]))->create();
        Product::factory()->for(Game::factory()->state(['title' => 'Popular Action', 'genres' => ['Action'], 'steam_reviews' => 999999]))->create();
        Product::factory()->for(Game::factory()->state(['title' => 'Elden Ring', 'genres' => ['RPG'], 'steam_reviews' => 1]))->create();

        $this->getJson("/api/products/{$dlc->slug}/similar")->assertJsonPath('data.0.title', 'Elden Ring');
    }

    public function test_detail_includes_trailer_and_cover_shape(): void
    {
        $game = Game::factory()->create([
            'trailer' => ['title' => 'Launch', 'poster' => 'p.jpg', 'hls_url' => 'https://x/master.m3u8'],
            'cover_portrait' => false,
        ]);
        $product = Product::factory()->for($game)->create();

        $this->getJson("/api/products/{$product->slug}")
            ->assertJsonPath('data.game.trailer.hls_url', 'https://x/master.m3u8')
            ->assertJsonPath('data.cover_portrait', false);
    }
}
