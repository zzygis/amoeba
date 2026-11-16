<?php

namespace App\Http\Resources;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One seller's offer. Mirrors `Offer` in frontend/src/types.ts.
 * Expects `seller` and `product` loaded and the `stock` count (Offer::withStock()).
 *
 * @mixin Offer
 */
class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price_cents' => $this->price_cents,
            'discount_percent' => $this->discountPercent(),
            'stock' => (int) $this->stock,
            'seller' => [
                'name' => $this->seller->name,
                'rating' => $this->seller->rating,
                'rating_label' => $this->seller->ratingLabel(),
                'verified' => $this->seller->verified,
                'avatar_url' => $this->seller->avatar_url, // null: the frontend shows the initial instead
            ],
        ];
    }
}
