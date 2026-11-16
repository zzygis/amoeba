<?php

namespace App\Home;

use App\Http\Resources\ProductResource;
use App\Models\Product;

/**
 * A row of products ("Top games", "Biggest discounts", ...), one listing per game,
 * plus the store query for its "See all" link.
 * Settings: {"sort": "popular", "max_price": 10, "type": "dlc", "limit": 6}
 */
class ProductRowBlock implements HomeBlockContent
{
    public function content(array $settings): array
    {
        $sort = $settings['sort'] ?? 'popular';
        $maxCents = isset($settings['max_price']) ? (int) round($settings['max_price'] * 100) : null;
        $types = isset($settings['type']) ? [$settings['type']] : [];

        $products = Product::query()
            ->catalog()
            ->filter($types, [], [], null, $maxCents)
            ->whereHas('offers', fn ($offer) => $offer->whereHas('availableKeys')) // in stock only
            ->sorted($sort)
            ->limit(40)
            ->get()
            // One listing per game, so a row isn't "FIFA 23" three times (Steam, Xbox, PlayStation).
            ->unique('game_id')
            ->take($settings['limit'] ?? 6)
            ->values();

        return [
            'products' => ProductResource::collection($products)->resolve(),
            // The frontend turns this into /store?sort=...&max_price=... for "See all".
            'see_all' => array_filter([
                'sort' => $sort,
                'max_price' => $settings['max_price'] ?? null,
                'type' => $settings['type'] ?? null,
            ]),
        ];
    }
}
