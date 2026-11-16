<?php

namespace App\Home;

use App\Http\Resources\ProductResource;
use App\Models\Product;

/**
 * The big banner: one featured product, picked by a sort (default: biggest discount).
 * Settings: {"sort": "discount"}
 */
class BannerBlock implements HomeBlockContent
{
    public function content(array $settings): array
    {
        $product = Product::query()
            ->catalog()
            ->whereHas('offers', fn ($offer) => $offer->whereHas('availableKeys')) // must be buyable
            ->sorted($settings['sort'] ?? 'discount')
            ->first();

        return ['product' => $product ? (new ProductResource($product))->resolve() : null];
    }
}
