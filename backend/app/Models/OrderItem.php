<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = ['product_id', 'offer_id', 'product_name', 'seller_name', 'price_cents', 'quantity'];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // The keys this order line bought.
    public function keys(): HasMany
    {
        return $this->hasMany(ProductKey::class);
    }
}
