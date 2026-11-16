<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\ProductKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductKey>
 */
class ProductKeyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'code' => strtoupper(fake()->unique()->bothify('?????-?????-?????')),
            'sold_at' => null,
        ];
    }

    public function sold(): static
    {
        return $this->state(['sold_at' => now()]);
    }
}
