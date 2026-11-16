<?php

namespace App\Home;

use App\Models\Product;

/**
 * "Shop by platform" tiles: every platform with how many products it has.
 */
class PlatformsBlock implements HomeBlockContent
{
    public function content(array $settings): array
    {
        $platforms = Product::query()
            ->selectRaw('platform as name, count(*) as count')
            ->groupBy('platform')
            ->orderBy('platform')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count])
            ->all();

        return ['platforms' => $platforms];
    }
}
