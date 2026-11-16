<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * GET /api/products/{slug}/reviews: reviews of this product's game, newest first.
     * Public, but if a valid token is sent it also says whether that user may write one.
     */
    public function index(Request $request, Product $product): AnonymousResourceCollection
    {
        $reviews = $product->game->reviews()->with('user')->latest('id')->paginate(10);

        // auth('sanctum') reads the token if there is one, without requiring it.
        $user = auth('sanctum')->user();

        return ReviewResource::collection($reviews)->additional([
            'can_review' => $user !== null
                && $user->hasBoughtGame($product->game_id)
                && ! $user->reviews()->where('game_id', $product->game_id)->exists(),
        ]);
    }

    /**
     * POST /api/products/{slug}/reviews  {rating: 1-5, body}
     * Only customers who bought the game, once per game.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $user = $request->user();

        if (! $user->hasBoughtGame($product->game_id)) {
            throw ValidationException::withMessages(['body' => 'Only customers who bought this game can review it.']);
        }
        if ($user->reviews()->where('game_id', $product->game_id)->exists()) {
            throw ValidationException::withMessages(['body' => 'You have already reviewed this game.']);
        }

        $review = $user->reviews()->create([...$validated, 'game_id' => $product->game_id]);

        return (new ReviewResource($review->load('user')))->response()->setStatusCode(201);
    }
}
