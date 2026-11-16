<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'percent_off', 'expires_at', 'max_uses'];

    protected function casts(): array
    {
        return [
            'percent_off' => 'integer',
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'times_used' => 'integer',
        ];
    }

    /**
     * Codes are case-insensitive for customers: stored and looked up upper-case.
     */
    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function isUsable(): bool
    {
        $expired = $this->expires_at !== null && $this->expires_at->isPast();
        $usedUp = $this->max_uses !== null && $this->times_used >= $this->max_uses;

        return ! $expired && ! $usedUp;
    }

    /**
     * Discount in cents for a subtotal, rounded down so we never give away more than the percentage.
     */
    public function discountFor(int $subtotalCents): int
    {
        return intdiv($subtotalCents * $this->percent_off, 100);
    }
}
