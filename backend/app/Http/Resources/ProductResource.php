<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A product as shown on cards and in lists. Mirrors `Product` in frontend/src/types.ts.
 * Expects Product::catalog() (game, stock, best offer id and price).
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name(),
            'title' => $this->game->title,
            'platform' => $this->platform,
            'region' => $this->region,
            // The cheapest offer that has keys: "From €X". Both null when sold out.
            'offer_id' => $this->best_offer_id,
            'price_cents' => $this->price_cents,
            'original_price_cents' => $this->original_price_cents,
            'discount_percent' => $this->discountPercent(),
            'type' => $this->game->type,
            'cover_url' => $this->game->cover_url,
            'cover_portrait' => $this->game->cover_portrait,
            // Key codes themselves are never exposed, only how many are left (all sellers together).
            'stock' => (int) $this->stock,
        ];
    }
}
