<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /** A user who bought the Steam listing of $game. */
    private function buyer(Game $game): User
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        $offer = $this->offer(product: Product::factory()->for($game)->create());
        $user->cartItems()->create(['offer_id' => $offer->id, 'quantity' => 1]);
        Sanctum::actingAs($user);
        $this->postJson('/api/checkout', ['payment_method' => 'card'])->assertCreated();

        return $user;
    }

    public function test_buyer_can_review_and_review_shows_on_every_listing_of_the_game(): void
    {
        $game = Game::factory()->create();
        $this->buyer($game);
        $xbox = Product::factory()->for($game)->create(['platform' => 'Xbox']);

        $this->getJson("/api/products/{$xbox->slug}/reviews")->assertJsonPath('can_review', true);

        $this->postJson("/api/products/{$xbox->slug}/reviews", ['rating' => 5, 'body' => 'Brilliant game, loved it.'])
            ->assertCreated()
            ->assertJsonPath('data.author', 'Ada'); // first name only

        $this->getJson("/api/products/{$xbox->slug}/reviews")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('can_review', false); // already reviewed

        $this->getJson("/api/products/{$xbox->slug}")
            ->assertJsonPath('data.rating', ['average' => 5, 'count' => 1]);
    }

    public function test_non_buyer_cannot_review(): void
    {
        $product = Product::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 4, 'body' => 'Never played it but...'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.body.0', 'Only customers who bought this game can review it.');
    }

    public function test_only_one_review_per_game(): void
    {
        $game = Game::factory()->create();
        $this->buyer($game);
        $slug = $game->products()->first()->slug;

        $this->postJson("/api/products/{$slug}/reviews", ['rating' => 4, 'body' => 'Good game overall.'])->assertCreated();
        $this->postJson("/api/products/{$slug}/reviews", ['rating' => 1, 'body' => 'Changed my mind.'])->assertUnprocessable();
    }

    public function test_review_input_is_validated(): void
    {
        $game = Game::factory()->create();
        $this->buyer($game);
        $slug = $game->products()->first()->slug;

        $this->postJson("/api/products/{$slug}/reviews", ['rating' => 6, 'body' => 'short'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating', 'body']);
    }

    public function test_seeded_sample_reviews_are_labelled_and_their_authors_cannot_log_in(): void
    {
        $this->seed(DatabaseSeeder::class);
        $slug = Game::where('slug', 'elden-ring')->first()->products()->first()->slug;

        $this->getJson("/api/products/{$slug}/reviews")
            ->assertOk()
            ->assertJsonPath('data.0.is_sample', true);

        // The reviewer accounts have long random passwords nobody knows.
        $this->postJson('/api/login', ['email' => 'sample-reviewer-1@amoeba.invalid', 'password' => 'password'])
            ->assertUnprocessable();
    }

    public function test_customer_reviews_are_never_samples(): void
    {
        $game = Game::factory()->create();
        $this->buyer($game);
        $slug = $game->products()->first()->slug;

        // Even if a client sends is_sample, it's ignored (not fillable).
        $this->postJson("/api/products/{$slug}/reviews", ['rating' => 5, 'body' => 'Great game, really enjoyed it.', 'is_sample' => true])
            ->assertCreated()
            ->assertJsonPath('data.is_sample', false);
    }

    public function test_guests_see_reviews_but_cannot_write(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->slug}/reviews")->assertOk()->assertJsonPath('can_review', false);
        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5, 'body' => 'Great game!'])->assertUnauthorized();
    }
}
