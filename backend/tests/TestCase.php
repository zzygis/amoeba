<?php

namespace Tests;

use App\Models\Offer;
use App\Models\Product;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * A seller's offer with unsold keys: $this->offer(1999, keys: 3).
     * Pass $product to add another seller to an existing product.
     */
    protected function offer(int $priceCents = 1000, int $keys = 1, ?Product $product = null): Offer
    {
        return Offer::factory()
            ->for($product ?? Product::factory()->create())
            ->withKeys($keys)
            ->create(['price_cents' => $priceCents]);
    }
}
