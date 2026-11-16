<?php

namespace App\Http\Resources;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * A whole cart. Wraps a collection of CartItem models: saved ones for a logged-in
 * user, or unsaved ones built from a guest's localStorage cart (see CartController::preview).
 * Each line is an offer: a product from one seller at that seller's price.
 * Mirrors `Cart` in frontend/src/types.ts.
 *
 * Expects offer.seller, offer.product.game loaded and the offer's stock count.
 *
 * @property Collection<int, CartItem> $resource
 */
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->resource;

        return [
            'items' => $items->map(fn (CartItem $item) => [
                'offer' => new OfferResource($item->offer),
                'product' => [
                    'id' => $item->offer->product->id,
                    'slug' => $item->offer->product->slug,
                    'name' => $item->offer->product->name(),
                    'platform' => $item->offer->product->platform,
                    'cover_url' => $item->offer->product->game->cover_url,
                    'cover_portrait' => $item->offer->product->game->cover_portrait,
                ],
                'quantity' => $item->quantity,
                'line_total_cents' => $item->lineTotalCents(),
            ])->values(),
            'count' => $items->sum('quantity'),
            'total_cents' => $items->sum(fn (CartItem $item) => $item->lineTotalCents()),
        ];
    }
}
