<?php

namespace App\Models;

use App\Home\BannerBlock;
use App\Home\HomeBlockContent;
use App\Home\PlatformsBlock;
use App\Home\ProductRowBlock;
use Illuminate\Database\Eloquent\Model;

/**
 * One block of the homepage (see the home_blocks migration).
 */
class HomeBlock extends Model
{
    // Block type => the backend class that produces its content.
    public const TYPES = [
        'banner' => BannerBlock::class,
        'platforms' => PlatformsBlock::class,
        'product_row' => ProductRowBlock::class,
    ];

    protected $fillable = ['key', 'type', 'title', 'position', 'enabled', 'settings'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'enabled' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function content(): array
    {
        /** @var HomeBlockContent $source */
        $source = app(self::TYPES[$this->type]);

        return $source->content($this->settings ?? []);
    }
}
