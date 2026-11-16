<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An order. Key codes are included only when the keys relation was loaded,
 * which happens only on the owner's order page and right after checkout.
 * Mirrors `Order` in frontend/src/types.ts.
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->toIso8601String(),
            'payment_method' => $this->payment_method,
            'subtotal_cents' => $this->subtotal_cents,
            'discount_cents' => $this->discount_cents,
            'coupon_code' => $this->coupon_code,
            'total_cents' => $this->total_cents,
            'items' => $this->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_slug' => $item->product->slug,
                'name' => $item->product_name,
                'seller_name' => $item->seller_name,
                'platform' => $item->product->platform,
                'cover_url' => $item->product->game->cover_url,
                'price_cents' => $item->price_cents,
                'quantity' => $item->quantity,
                'keys' => $this->when(
                    $item->relationLoaded('keys'),
                    fn () => $item->keys->map(fn (ProductKey $key) => $key->code)->values(),
                ),
            ])->all(), // a plain array, so Laravel drops the "keys" field when it's not loaded
        ];
    }
}
