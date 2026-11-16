<?php

namespace Database\Seeders;

use App\Models\HomeBlock;
use Illuminate\Database\Seeder;

/**
 * The default homepage. Uses firstOrCreate (not updateOrCreate): this runs on every
 * deploy, and must not undo changes an admin made to titles, order or visibility.
 */
class HomeBlockSeeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            ['key' => 'deal-of-the-day', 'type' => 'banner', 'title' => 'Deal of the day', 'settings' => ['sort' => 'discount']],
            ['key' => 'platforms', 'type' => 'platforms', 'title' => 'Shop by platform', 'settings' => []],
            ['key' => 'top-games', 'type' => 'product_row', 'title' => 'Top games', 'settings' => ['sort' => 'popular', 'limit' => 6]],
            ['key' => 'biggest-discounts', 'type' => 'product_row', 'title' => 'Biggest discounts', 'settings' => ['sort' => 'discount', 'limit' => 6]],
            ['key' => 'new-releases', 'type' => 'product_row', 'title' => 'New releases', 'settings' => ['sort' => 'newest', 'limit' => 6]],
            ['key' => 'dlc', 'type' => 'product_row', 'title' => 'Expand your games: DLC', 'settings' => ['sort' => 'popular', 'type' => 'dlc', 'limit' => 6]],
            ['key' => 'under-10', 'type' => 'product_row', 'title' => 'Under €10', 'settings' => ['sort' => 'price_asc', 'max_price' => 10, 'limit' => 6]],
        ];

        foreach ($blocks as $position => $block) {
            HomeBlock::firstOrCreate(['key' => $block['key']], [...$block, 'position' => $position * 10, 'enabled' => true]);
        }
    }
}
