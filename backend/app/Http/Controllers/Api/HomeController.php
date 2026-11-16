<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeBlock;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /**
     * GET /api/home
     * The homepage, as an ordered list of blocks. Which blocks, their titles, order and
     * settings come from the home_blocks table; each block's content comes from its own
     * class in app/Home. The React page only draws what it gets.
     */
    public function __invoke(): JsonResponse
    {
        // Every visitor gets the same homepage, and building it takes a dozen queries,
        // so it's cached for a minute (and dropped as soon as something on it changes).
        $blocks = CatalogCache::remember('home', fn () => HomeBlock::query()
            ->where('enabled', true)
            ->orderBy('position')
            ->get()
            ->filter(fn (HomeBlock $block) => isset(HomeBlock::TYPES[$block->type])) // skip unknown types
            ->map(fn (HomeBlock $block) => [
                'id' => $block->id,
                'type' => $block->type,
                'title' => $block->title,
                'content' => $block->content(),
            ])
            ->values()
            ->all());

        return response()->json(['data' => $blocks]);
    }
}
