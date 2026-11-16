<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One seller selling one product at their own price, with their own keys.
 * This is what actually goes into the cart and gets bought.
 */
class Offer extends Model
{
    use HasFactory;

    protected $fillable = ['seller_id', 'price_cents'];

    protected function casts(): array
    {
        return ['price_cents' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function keys(): HasMany
    {
        return $this->hasMany(ProductKey::class);
    }

    public function availableKeys(): HasMany
    {
        return $this->keys()->whereNull('sold_at');
    }

    /**
     * Adds a `stock` attribute: this seller's unsold keys, counted in the same SQL query.
     */
    public function scopeWithStock(Builder $query): void
    {
        $query->withCount('availableKeys as stock');
    }

    /**
     * Discount against the product's list price, or null when not cheaper.
     */
    public function discountPercent(): ?int
    {
        $list = $this->product->original_price_cents;
        if (! $list || $list <= $this->price_cents) {
            return null;
        }

        return (int) round(100 - $this->price_cents * 100 / $list);
    }
}
