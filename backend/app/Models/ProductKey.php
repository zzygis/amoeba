<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One activation code, uploaded by a seller for their offer.
 */
class ProductKey extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'order_item_id', 'sold_at'];

    // Never send the code in JSON by accident; it's revealed only to the buyer, explicitly.
    protected $hidden = ['code'];

    protected function casts(): array
    {
        return [
            // Stored encrypted with APP_KEY; decrypted automatically when read.
            'code' => 'encrypted',
            'sold_at' => 'datetime',
        ];
    }

    /**
     * A key-shaped random code like "7KQ2M-XW9PD-3HZRT" (demo data, not a real activation key).
     */
    public static function fakeCode(): string
    {
        return implode('-', array_map(fn () => strtoupper(Str::random(5)), range(1, 3)));
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
