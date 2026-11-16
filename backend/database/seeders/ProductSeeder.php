<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\Seller;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * The demo catalog from database/data/games.json: real games with their listings,
 * each sold by 1-4 sellers at different prices.
 *
 * Facts (developer, publisher, release date, genres, languages, PC requirements,
 * screenshot URLs, Steam review count, trailer) come from Steam's public store API;
 * descriptions are our own. Images and trailers are hotlinked from Steam's CDN.
 *
 * Safe to run any number of times (it runs on every deploy, see docker/start.sh):
 * rows are matched by slug/ids and updated, and keys are only generated for new offers.
 * Deterministic (no randomness in prices or sellers), so every environment gets the same
 * catalog. Uses no Faker, because Faker is a dev dependency and isn't installed in production.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = Seller::orderBy('id')->get();
        if ($sellers->isEmpty()) {
            $this->call(SellerSeeder::class);
            $sellers = Seller::orderBy('id')->get();
        }

        $games = json_decode(file_get_contents(database_path('data/games.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($games as $data) {
            $game = Game::updateOrCreate(['slug' => Str::slug($data['title'])], [
                'title' => $data['title'],
                'type' => $data['type'],
                'description' => $data['description'],
                'developer' => $data['developer'],
                'publisher' => $data['publisher'],
                'release_date' => $data['release_date'],
                // Portrait box art where Steam has it; some DLC only has a wide header image.
                'cover_url' => $data['landscape_cover_url']
                    ?? "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$data['steam_app_id']}/library_600x900.jpg",
                'cover_portrait' => ! isset($data['landscape_cover_url']),
                'steam_reviews' => $data['steam_reviews'],
                'genres' => $data['genres'],
                'modes' => $data['modes'],
                'languages' => $data['languages'],
                'screenshots' => $data['screenshots'],
                'requirements' => $data['requirements'],
                'trailer' => $data['trailer'],
            ]);

            foreach ($data['listings'] as $listing) {
                $product = $game->products()->updateOrCreate(
                    ['slug' => Str::slug("{$data['title']} {$listing['platform']} {$listing['region']}")],
                    [
                        'platform' => $listing['platform'],
                        'region' => $listing['region'],
                        'original_price_cents' => $listing['original_price_cents'],
                    ],
                );

                $this->seedOffers($product, $listing['price_cents'], $listing['stock'], $sellers);
            }
        }
    }

    /**
     * The listing's price in games.json is the best offer; other sellers ask a bit more.
     * Which sellers and how many (1-4) are derived from the slug, so they're stable.
     */
    private function seedOffers(Product $product, int $bestPrice, int $stock, Collection $sellers): void
    {
        $hash = crc32($product->slug);
        $count = 1 + $hash % 4;

        for ($i = 0; $i < $count; $i++) {
            $seller = $sellers[($hash + $i) % $sellers->count()];
            // Each further seller is 4-9% more expensive than the previous one, ending in .x9.
            $price = (int) round($bestPrice * (1 + $i * (0.04 + ($hash >> $i) % 6 / 100)));
            $price = max(99, intdiv($price + 5, 10) * 10 - 1);
            // The cheapest seller has the listing's stock; others a few keys (sold-out listings stay sold out).
            $keys = $i === 0 ? $stock : ($stock === 0 ? 0 : 1 + ($hash >> $i) % 8);

            $offer = $product->offers()->updateOrCreate(['seller_id' => $seller->id], ['price_cents' => $price]);

            // Only new offers get keys, so re-running never inflates stock or undoes sales.
            if ($offer->wasRecentlyCreated && $keys > 0) {
                $this->insertKeys($offer->id, $keys);
            }
        }
    }

    /**
     * All of an offer's keys in ONE insert query instead of one query per key: against a
     * remote database (Aiven) every query is a network round trip, and the first deploy
     * seeds ~850 keys. A bulk insert skips the model, so the code is encrypted here with
     * Crypt::encryptString, exactly what ProductKey's 'encrypted' cast does.
     */
    private function insertKeys(int $offerId, int $count): void
    {
        $now = now();

        ProductKey::insert(Collection::times($count, fn () => [
            'offer_id' => $offerId,
            'code' => Crypt::encryptString(ProductKey::fakeCode()),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }
}
