<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Test data only. The real catalog comes from ProductSeeder.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'slug' => fake()->unique()->slug(3),
            'platform' => 'Steam',
            'region' => 'GLOBAL',
            'original_price_cents' => null,
        ];
    }

    /**
     * Product::factory()->withOffer(1999, keys: 3)->create(): sold by one (new) seller
     * at 19.99 with 3 keys. Chain it to add more sellers.
     */
    public function withOffer(int $priceCents = 1999, int $keys = 0): static
    {
        return $this->has(Offer::factory()->state(['price_cents' => $priceCents])->withKeys($keys), 'offers');
    }
}
