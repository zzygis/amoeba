<?php

namespace App\Support;

use App\Models\Game;
use Illuminate\Support\Str;

/**
 * Search that copes with partial names and typos.
 *
 * Every word the user typed must match some word of the title, where "match" means:
 *   - the title word starts with or contains it   ("eld" -> "elden", "ring" -> "ring")
 *   - or it's within a small edit distance (typos) ("eldn" -> "elden", "cyberpnk" -> "cyberpunk")
 * Allowed typos grow with word length: none up to 3 letters, 1 up to 6, 2 above.
 * Words can be in any order, and punctuation/accents/case are ignored.
 *
 * The catalog is small, so titles are compared in PHP. For thousands of products this
 * would move to a search engine (Meilisearch/Elasticsearch) or MySQL full-text + trigrams.
 */
class TitleSearch
{
    /**
     * @return list<int> ids of the games whose title matches the search
     */
    public static function gameIds(string $term): array
    {
        $queryWords = self::words($term);
        if ($queryWords === []) {
            return []; // only punctuation, e.g. "%": nothing can match
        }

        return Game::query()
            ->pluck('title', 'id')
            ->filter(fn (string $title) => self::matches($queryWords, self::words($title)))
            ->keys()
            ->all();
    }

    /**
     * @param  list<string>  $queryWords
     * @param  list<string>  $titleWords
     */
    public static function matches(array $queryWords, array $titleWords): bool
    {
        foreach ($queryWords as $queryWord) {
            $found = false;
            foreach ($titleWords as $titleWord) {
                if (str_contains($titleWord, $queryWord) || self::isTypoOf($queryWord, $titleWord)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                return false;
            }
        }

        return true;
    }

    /**
     * True if $typed is a misspelling of $word, or of the beginning of it
     * (so "cyberpnk" matches "cyberpunk" and the half-typed "witche" matches "witcher").
     */
    private static function isTypoOf(string $typed, string $word): bool
    {
        $length = strlen($typed);
        $allowed = match (true) {
            $length <= 3 => 0,
            $length <= 6 => 1,
            default => 2,
        };

        return $allowed > 0 && (
            levenshtein($typed, $word) <= $allowed
            || levenshtein($typed, substr($word, 0, $length)) <= $allowed
        );
    }

    /**
     * "Baldur's Gate 3" -> ["baldurs", "gate", "3"]
     *
     * @return list<string>
     */
    public static function words(string $text): array
    {
        $text = Str::lower(Str::ascii($text));
        $text = str_replace(["'", '’'], '', $text); // "baldur's" -> "baldurs"

        return preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }
}
