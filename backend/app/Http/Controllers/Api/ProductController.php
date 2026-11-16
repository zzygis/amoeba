<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Game;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * GET /api/products
     *   ?search=elden            title search
     *   &type=game,dlc           comma-separated (game, dlc, software)
     *   &platform=Steam,Xbox     comma-separated
     *   &region=GLOBAL,EUROPE    comma-separated
     *   &min_price=5&max_price=30  in euros
     *   &sort=stock|popular|price_asc|price_desc|discount|newest|oldest|az  (default: stock)
     *   &per_page=24&page=2
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:200'],
            'region' => ['nullable', 'string', 'max:200'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'sort' => ['nullable', Rule::in(Product::SORTS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $products = Product::query()
            ->catalog() // game, stock, best offer (id + price)
            ->search($validated['search'] ?? null)
            ->filter(
                $this->csv($validated['type'] ?? null),
                $this->csv($validated['platform'] ?? null),
                $this->csv($validated['region'] ?? null),
                $this->toCents($validated['min_price'] ?? null),
                $this->toCents($validated['max_price'] ?? null),
            )
            ->sorted($validated['sort'] ?? 'stock') // default: most keys first
            ->paginate($validated['per_page'] ?? 24)
            ->withQueryString(); // keep the filters in the next/prev page links

        return ProductResource::collection($products);
    }

    /**
     * GET /api/products/filters
     * Options for the sidebar: every product type, platform and region with how many
     * products it has, and the cheapest/most expensive offer price.
     */
    public function filters(): JsonResponse
    {
        $countBy = fn (string $column) => Product::query()
            ->selectRaw("{$column} as name, count(*) as count")
            ->groupBy($column)
            ->orderByDesc('count') // most products first (ties alphabetically)
            ->orderBy($column)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count]);

        $types = Product::query()
            ->join('games', 'games.id', '=', 'products.game_id')
            ->selectRaw('games.type as name, count(*) as count')
            ->groupBy('games.type')
            ->get()
            ->sortBy(fn ($row) => array_search($row->name, Game::TYPES)) // game, dlc, software
            ->values()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count]);

        return response()->json(['data' => [
            'types' => $types,
            'platforms' => $countBy('platform'),
            'regions' => $countBy('region'),
            'price' => [
                'min_cents' => (int) Offer::min('price_cents'),
                'max_cents' => (int) Offer::max('price_cents'),
            ],
        ]]);
    }

    /**
     * GET /api/products/{slug}
     */
    public function show(Product $product): ProductDetailResource
    {
        // Re-load through catalog() to get the best offer's price and the total stock.
        $product = Product::query()->catalog()->findOrFail($product->id);
        $product->game->loadCount('reviews')->loadAvg('reviews', 'rating');

        // Every seller's offer, cheapest first; sold-out offers last.
        $product->setRelation('offers', $product->offers()
            ->with('seller')
            ->withStock()
            ->orderByRaw('stock = 0')
            ->orderBy('price_cents')
            ->get()
            ->each(fn (Offer $offer) => $offer->setRelation('product', $product)));

        // The same game on other platforms/regions, cheapest first.
        $product->setRelation('otherListings', Product::query()
            ->catalog()
            ->where('game_id', $product->game_id)
            ->whereKeyNot($product->id)
            ->sorted('price_asc')
            ->get());

        return new ProductDetailResource($product);
    }

    /**
     * GET /api/products/{slug}/similar: "Gamers also viewed".
     * The base game or DLC of this one first, then other games sharing the most genres
     * (most popular first on ties); one listing each: the cheapest that's in stock.
     *
     * The catalog is small, so this compares genres in PHP. With thousands of games it
     * would become a SQL query (a game_genre table) or a recommendation service.
     */
    public function similar(Product $product): AnonymousResourceCollection
    {
        $genres = $product->game->genres;
        $title = $product->game->title;

        $listings = Game::query()
            ->whereKeyNot($product->game_id)
            ->with(['products' => fn ($q) => $q->catalog()->sorted('price_asc')])
            ->get()
            ->map(fn (Game $game) => [
                // Base game <-> its DLC ("Elden Ring" / "Elden Ring Shadow of the Erdtree") always comes first.
                'related' => str_starts_with($game->title, $title) || str_starts_with($title, $game->title),
                'shared' => count(array_intersect($genres, $game->genres)),
                'popularity' => $game->steam_reviews,
                'listing' => $game->products->firstWhere('stock', '>', 0) ?? $game->products->first(),
            ])
            ->filter(fn (array $row) => ($row['related'] || $row['shared'] > 0) && $row['listing'] !== null)
            ->sortBy([['related', 'desc'], ['shared', 'desc'], ['popularity', 'desc']])
            ->take(6)
            ->pluck('listing');

        return ProductResource::collection($listings);
    }

    /**
     * "Steam,Xbox" -> ["Steam", "Xbox"]
     *
     * @return list<string>
     */
    private function csv(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    private function toCents(int|float|string|null $euros): ?int
    {
        return $euros === null ? null : (int) round((float) $euros * 100);
    }
}
