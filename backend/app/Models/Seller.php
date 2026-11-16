<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seller extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'rating', 'verified'];

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'verified' => 'boolean',
        ];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * Rating label like eneba.com's ("9.90 Superb rating").
     */
    public function ratingLabel(): string
    {
        return match (true) {
            $this->rating >= 9.5 => 'Superb',
            $this->rating >= 9.0 => 'Excellent',
            $this->rating >= 8.0 => 'Very good',
            default => 'Good',
        };
    }
}
