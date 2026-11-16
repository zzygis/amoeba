<?php

namespace Database\Seeders;

use App\Models\Seller;
use Illuminate\Database\Seeder;

/**
 * Demo sellers (made-up names). Each uses cover art of a game from our catalog as its
 * profile picture (Steam app id below), like sellers on eneba.com often do.
 * Idempotent: matched by slug.
 */
class SellerSeeder extends Seeder
{
    public const SELLERS = [
        ['name' => 'KeyCrate', 'rating' => 9.92, 'verified' => true, 'avatar_app' => 1245620],   // Elden Ring
        ['name' => 'PixelVault', 'rating' => 9.81, 'verified' => true, 'avatar_app' => 367520],  // Hollow Knight
        ['name' => 'GameHarbor', 'rating' => 9.74, 'verified' => false, 'avatar_app' => 292030], // The Witcher 3
        ['name' => 'NovaKeys', 'rating' => 9.56, 'verified' => false, 'avatar_app' => 1091500],  // Cyberpunk 2077
        ['name' => 'LootLane', 'rating' => 9.12, 'verified' => false, 'avatar_app' => 1145360],  // Hades
        ['name' => 'QuickCodes', 'rating' => 8.47, 'verified' => false, 'avatar_app' => 504230], // Celeste
    ];

    public function run(): void
    {
        foreach (self::SELLERS as $seller) {
            Seller::updateOrCreate(['slug' => strtolower($seller['name'])], [
                'name' => $seller['name'],
                'rating' => $seller['rating'],
                'verified' => $seller['verified'],
                'avatar_url' => "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$seller['avatar_app']}/library_600x900.jpg",
            ]);
        }
    }
}
