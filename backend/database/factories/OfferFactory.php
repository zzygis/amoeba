<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'seller_id' => Seller::factory(),
            'price_cents' => fake()->numberBetween(199, 5999),
        ];
    }

    /**
     * Offer::factory()->withKeys(3)->create(): an offer with 3 unsold keys.
     */
    public function withKeys(int $count): static
    {
        return $this->has(ProductKey::factory()->count($count), 'keys');
    }
}
