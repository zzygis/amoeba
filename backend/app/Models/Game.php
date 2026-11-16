<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'description',
        'developer',
        'publisher',
        'release_date',
        'cover_url',
        'cover_portrait',
        'steam_reviews',
        'genres',
        'modes',
        'languages',
        'screenshots',
        'requirements',
        'trailer',
    ];

    // Kinds of product, used by the store's "Product type" filter.
    public const TYPES = ['game', 'dlc', 'software'];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'steam_reviews' => 'integer',
            'cover_portrait' => 'boolean',
            'genres' => 'array',
            'modes' => 'array',
            'languages' => 'array',
            'screenshots' => 'array',
            'requirements' => 'array',
            'trailer' => 'array',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
