<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
