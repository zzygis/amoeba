<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample reviews so product pages aren't empty in the demo. They're written for this
 * project (not copied from anywhere), posted by made-up reviewer accounts, and flagged
 * is_sample, so the site labels them "Sample review" instead of passing them off as real.
 *
 * The reviewer accounts get a long random password nobody knows, so they can't be used
 * to log in. Idempotent: matched by email and by (reviewer, game).
 */
class ReviewSeeder extends Seeder
{
    private const REVIEWERS = ['Mantas R.', 'Laura K.', 'Tom W.', 'Ieva S.', 'Daniel P.'];

    // game slug => [[reviewer index, rating, text], ...]
    private const REVIEWS = [
        'elden-ring' => [
            [0, 5, 'Key arrived instantly and activated on Steam without problems. The open world is huge and every area hides something new. Tough, but always fair.'],
            [1, 4, 'Brilliant game, but expect to die a lot early on. It took me two evenings to stop being frustrated and start loving it.'],
        ],
        'red-dead-redemption-2' => [
            [2, 5, "One of the best stories I've played. Slow start, but by chapter three I was hooked. The key redeemed in the Rockstar launcher in a minute."],
            [3, 4, 'Beautiful world and great characters. Some missions feel a bit restrictive, but the atmosphere makes up for it.'],
        ],
        'cyberpunk-2077' => [
            [4, 4, 'After the updates it runs well and the city looks incredible. The main story is strong; side jobs are hit and miss.'],
            [0, 5, 'Came back for the expansion and the base game is so much better now. Great price for what you get.'],
        ],
        'baldurs-gate-3' => [
            [1, 5, 'Easily my game of the year. Choices really matter, and co-op with friends is chaos in the best way.'],
            [2, 5, "Hundreds of hours in and I'm still finding new dialogue. The key worked straight away."],
        ],
        'stardew-valley' => [
            [3, 5, 'The perfect game to relax after work. Played it in co-op with my partner and we lost track of time.'],
            [4, 5, 'Tiny price, endless content. Activation took seconds.'],
        ],
        'hades' => [
            [0, 5, "The best roguelike I've played. Every run feels different and the voice acting is excellent."],
            [3, 4, "Very addictive. A bit repetitive after the first ending, but there's still plenty to do."],
        ],
        'hollow-knight' => [
            [2, 5, 'Gorgeous hand-drawn world and great bosses. Hard, but never unfair.'],
            [1, 4, 'Loved exploring it. The map system takes some getting used to.'],
        ],
        'the-witcher-3-wild-hunt' => [
            [4, 5, 'Still one of the best RPGs years later. The two expansions alone are worth the price.'],
        ],
        'terraria' => [
            [3, 5, 'Bought it for a few euros and played it for a hundred hours. Even better with friends.'],
        ],
        'portal-2' => [
            [0, 5, 'Clever puzzles and genuinely funny. The co-op campaign is a must with a friend.'],
        ],
        'celeste' => [
            [1, 5, 'Tight controls and a touching story. Assist mode is a nice touch if you get stuck.'],
        ],
        'grand-theft-auto-v' => [
            [2, 4, 'Still fun to mess around in after all these years. Online can be grindy.'],
            [4, 3, 'Story mode is great, but setting up the launcher took a while. The key itself worked fine.'],
        ],
        'helldivers-2' => [
            [3, 4, 'The best co-op shooter in ages when you play with friends. Solo is less fun.'],
        ],
        'split-fiction' => [
            [0, 5, 'Every level has a new idea. It needs a second player, so bring a friend.'],
        ],
        'it-takes-two' => [
            [1, 5, 'Played it with my girlfriend and we both loved it. So much variety.'],
        ],
    ];

    public function run(): void
    {
        $reviewers = collect(self::REVIEWERS)->map(fn (string $name, int $i) => User::firstOrCreate(
            ['email' => 'sample-reviewer-'.($i + 1).'@amoeba.invalid'], // .invalid: a domain that can never exist
            ['name' => $name, 'password' => Str::random(40)],
        ));

        $games = Game::whereIn('slug', array_keys(self::REVIEWS))->pluck('id', 'slug');

        foreach (self::REVIEWS as $slug => $reviews) {
            if (! $games->has($slug)) {
                continue;
            }

            foreach ($reviews as [$reviewer, $rating, $body]) {
                $review = Review::firstOrNew(['user_id' => $reviewers[$reviewer]->id, 'game_id' => $games[$slug]]);
                $review->fill(['rating' => $rating, 'body' => $body]);
                $review->is_sample = true;
                $review->save();
            }
        }
    }
}
