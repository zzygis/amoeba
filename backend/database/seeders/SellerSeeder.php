<?php

namespace Database\Seeders;

use App\Models\Seller;
use Illuminate\Database\Seeder;

/**
 * Demo sellers (made-up names). Idempotent: matched by slug.
 */
class SellerSeeder extends Seeder
{
    public const SELLERS = [
        ['name' => 'KeyCrate', 'rating' => 9.92, 'verified' => true],
        ['name' => 'PixelVault', 'rating' => 9.81, 'verified' => true],
        ['name' => 'GameHarbor', 'rating' => 9.74, 'verified' => false],
        ['name' => 'NovaKeys', 'rating' => 9.56, 'verified' => false],
        ['name' => 'LootLane', 'rating' => 9.12, 'verified' => false],
        ['name' => 'QuickCodes', 'rating' => 8.47, 'verified' => false],
    ];

    public function run(): void
    {
        foreach (self::SELLERS as $seller) {
            Seller::updateOrCreate(['slug' => strtolower($seller['name'])], $seller);
        }
    }
}
