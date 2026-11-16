<?php

namespace App\Home;

/**
 * A source of homepage content. Each block type (banner, platforms, product_row) has
 * its own class, so a block could later come from another service (recommendations,
 * a CMS, ...) without the frontend or the other blocks changing.
 */
interface HomeBlockContent
{
    /**
     * @param  array<string, mixed>  $settings  the block's settings from home_blocks.settings
     * @return array<string, mixed> JSON-ready content for the frontend
     */
    public function content(array $settings): array;
}
