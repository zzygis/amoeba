<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a logged-in user's cart: an offer (a product from a specific seller) and a quantity.
 */
class CartItem extends Model
{
    // The most keys of one offer a customer can have in the cart.
    public const MAX_QUANTITY = 10;

    protected $fillable = ['offer_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function lineTotalCents(): int
    {
        return $this->offer->price_cents * $this->quantity;
    }
}
