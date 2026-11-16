<?php

namespace App\Models;

use App\Support\TitleSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * A listing: one game on one platform for one region ("Elden Ring Steam Key GLOBAL").
 * It has no price of its own; sellers make offers. Its "From €X" price is the cheapest
 * offer that still has keys (see scopeCatalog).
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'platform',
        'region',
        'original_price_cents',
    ];

    protected function casts(): array
    {
        return [
            'original_price_cents' => 'integer',
            // Not columns: filled in by scopeCatalog()'s subqueries.
            'price_cents' => 'integer',
            'best_offer_id' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    // All keys of all sellers' offers for this product.
    public function keys(): HasManyThrough
    {
        return $this->hasManyThrough(ProductKey::class, Offer::class);
    }

    public function availableKeys(): HasManyThrough
    {
        return $this->keys()->whereNull('product_keys.sold_at');
    }

    /**
     * Adds a `stock` attribute: unsold keys across all sellers, counted in the same SQL query.
     */
    public function scopeWithStock(Builder $query): void
    {
        $query->withCount('availableKeys as stock');
    }

    /**
     * The offer a buyer gets by default: the cheapest that still has keys
     * (the older offer wins a tie). Used as a correlated subquery.
     */
    private static function bestOffer(): Builder
    {
        return Offer::query()
            ->whereColumn('offers.product_id', 'products.id')
            ->whereHas('availableKeys')
            ->orderBy('offers.price_cents')
            ->orderBy('offers.id')
            ->limit(1);
    }

    /**
     * Everything a product card needs in one query: the game, total stock, and the best
     * offer's id and price (`best_offer_id`, `price_cents`; both null when sold out).
     */
    public function scopeCatalog(Builder $query): void
    {
        $query->with('game')
            ->withStock()
            ->addSelect([
                'best_offer_id' => self::bestOffer()->select('offers.id'),
                'price_cents' => self::bestOffer()->select('offers.price_cents'),
            ]);
    }

    /**
     * Title search that copes with partial words and typos ("eldn rin" finds Elden Ring).
     * See App\Support\TitleSearch.
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (trim((string) $term) === '') {
            return;
        }

        $query->whereIn('game_id', TitleSearch::gameIds($term));
    }

    /**
     * Sidebar filters. Each is optional; empty lists mean "don't filter".
     * The price range matches products that have an in-stock offer within it.
     *
     * @param  list<string>  $types  game, dlc, software
     * @param  list<string>  $platforms
     * @param  list<string>  $regions
     */
    public function scopeFilter(Builder $query, array $types, array $platforms, array $regions, ?int $minCents, ?int $maxCents): void
    {
        $query
            ->when($types, fn (Builder $q) => $q->whereHas('game', fn (Builder $game) => $game->whereIn('type', $types)))
            ->when($platforms, fn (Builder $q) => $q->whereIn('platform', $platforms))
            ->when($regions, fn (Builder $q) => $q->whereIn('region', $regions))
            ->when($minCents !== null || $maxCents !== null, fn (Builder $q) => $q->whereHas('offers', fn (Builder $offer) => $offer
                ->whereHas('availableKeys')
                ->when($minCents !== null, fn (Builder $o) => $o->where('price_cents', '>=', $minCents))
                ->when($maxCents !== null, fn (Builder $o) => $o->where('price_cents', '<=', $maxCents))));
    }

    // The sort options offered in the UI (same as eneba.com, plus "biggest discount").
    // "stock" (most keys first) is the store's default.
    public const SORTS = ['stock', 'popular', 'price_asc', 'price_desc', 'discount', 'newest', 'oldest', 'az'];

    /**
     * Orders by one of SORTS. Needs scopeCatalog() first (price_cents is its alias).
     * Values from the games table are read with subqueries, so no join is needed.
     * Sold-out products (no price) go last in price sorts. Every option ends with
     * title + id, so the order is always fully defined and pages never overlap.
     */
    public function scopeSorted(Builder $query, string $sort): void
    {
        $fromGame = fn (string $column) => Game::select($column)->whereColumn('games.id', 'products.game_id');

        match ($sort) {
            // Most unsold keys first (all sellers together); `stock` is withStock()'s count.
            'stock' => $query->orderByDesc('stock'),
            // Most Steam reviews first: a real-world popularity signal.
            'popular' => $query->orderByDesc($fromGame('steam_reviews')),
            'price_asc' => $query->orderByRaw('price_cents IS NULL')->orderBy('price_cents'),
            'price_desc' => $query->orderByRaw('price_cents IS NULL')->orderByDesc('price_cents'),
            // Biggest saving compared to the list price; full-price and sold-out products last.
            'discount' => $query->orderByRaw('COALESCE(1 - price_cents / original_price_cents, 0) DESC'),
            'newest' => $query->orderByDesc($fromGame('release_date')),
            'oldest' => $query->orderBy($fromGame('release_date')),
            default => null, // "az": the title order below is the whole sort
        };

        $query->orderBy($fromGame('title'))->orderBy('platform')->orderBy('products.id');
    }

    /**
     * Marketplace-style listing name: "Elden Ring Steam Key GLOBAL".
     */
    public function name(): string
    {
        return "{$this->game->title} {$this->platform} Key {$this->region}";
    }

    /**
     * Discount of the best offer against the list price, or null when there's none.
     */
    public function discountPercent(): ?int
    {
        if (! $this->price_cents || ! $this->original_price_cents || $this->original_price_cents <= $this->price_cents) {
            return null;
        }

        return (int) round(100 - $this->price_cents * 100 / $this->original_price_cents);
    }
}
