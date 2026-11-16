<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * The product page: the list fields plus the game's details, every seller's offer,
 * and the other listings of the same game ("also available on Xbox, PlayStation...").
 * Mirrors `ProductDetail` in frontend/src/types.ts.
 *
 * @mixin Product
 */
class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $game = $this->game;

        return [
            ...parent::toArray($request),
            'game' => [
                'description' => $game->description,
                'developer' => $game->developer,
                'publisher' => $game->publisher,
                'release_date' => $game->release_date?->toDateString(),
                'genres' => $game->genres,
                'modes' => $game->modes,
                'languages' => $game->languages,
                'screenshots' => $game->screenshots,
                'requirements' => $game->requirements,
                'trailer' => $game->trailer,
            ],
            // Customer reviews of the game (the list itself: GET /products/{slug}/reviews).
            'rating' => [
                'average' => $game->reviews_avg_rating !== null ? round((float) $game->reviews_avg_rating, 1) : null,
                'count' => (int) $game->reviews_count,
            ],
            // Every seller's offer for this listing, cheapest first, so the buyer can compare and choose.
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
            'other_listings' => ProductResource::collection($this->whenLoaded('otherListings')),
        ];
    }
}
