<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Test data only. The real catalog comes from ProductSeeder.
 *
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'type' => 'game',
            'description' => fake()->paragraph(),
            'developer' => fake()->company(),
            'publisher' => fake()->company(),
            'release_date' => fake()->date(),
            'cover_url' => 'https://example.com/cover.jpg',
            'steam_reviews' => fake()->numberBetween(0, 100000),
            'genres' => ['Action'],
            'modes' => ['Single-player'],
            'languages' => ['English'],
            'screenshots' => [],
            'requirements' => [['OS', 'Windows 10']],
        ];
    }
}
