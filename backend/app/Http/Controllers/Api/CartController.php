<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The cart holds offers (a product from a specific seller), not just products,
 * because the same product costs different amounts from different sellers.
 */
class CartController extends Controller
{
    /**
     * GET /api/cart: the logged-in user's cart.
     */
    public function index(Request $request): CartResource
    {
        return $this->cartOf($request->user());
    }

    /**
     * POST /api/cart/preview (no login needed)
     * Guests keep [{offer_id, quantity}] in localStorage. The frontend sends it here
     * so prices, names and stock always come from the server, never from the browser.
     */
    public function preview(Request $request): CartResource
    {
        $quantities = $this->validatedItems($request);
        $offers = $this->offersWithStock($quantities->keys());

        $items = $quantities
            ->filter(fn (int $quantity, int $offerId) => $offers->has($offerId)) // ignore deleted offers
            ->map(fn (int $quantity, int $offerId) => (new CartItem(['quantity' => $quantity]))
                ->setRelation('offer', $offers[$offerId]));

        return new CartResource($items->values());
    }

    /**
     * PUT /api/cart/items/{offer}  {quantity}
     * Sets (not adds) the quantity of one offer in the cart.
     */
    public function update(Request $request, Offer $offer): CartResource
    {
        $quantity = (int) $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartItem::MAX_QUANTITY],
        ])['quantity'];

        $stock = $offer->availableKeys()->count();
        if ($quantity > $stock) {
            throw ValidationException::withMessages(['quantity' => $stock === 0
                ? 'This offer is out of stock.'
                : "Only {$stock} ".Str::plural('key', $stock).' left from this seller.']);
        }

        $request->user()->cartItems()->updateOrCreate(
            ['offer_id' => $offer->id],
            ['quantity' => $quantity],
        );

        return $this->cartOf($request->user());
    }

    /**
     * DELETE /api/cart/items/{offer}
     */
    public function destroy(Request $request, Offer $offer): CartResource
    {
        $request->user()->cartItems()->where('offer_id', $offer->id)->delete();

        return $this->cartOf($request->user());
    }

    /**
     * POST /api/cart/merge  {items: [{offer_id, quantity}]}
     * Called right after login/registration with the guest's localStorage cart.
     * Quantities are added to what's already in the account's cart, capped at
     * the stock and the per-offer maximum; sold-out offers are skipped.
     */
    public function merge(Request $request): CartResource
    {
        $user = $request->user();
        $incoming = $this->validatedItems($request);
        $offers = $this->offersWithStock($incoming->keys());

        DB::transaction(function () use ($user, $incoming, $offers) {
            $existing = $user->cartItems()->get()->keyBy('offer_id');

            foreach ($incoming as $offerId => $quantity) {
                $offer = $offers->get($offerId);
                if (! $offer) {
                    continue;
                }

                $wanted = ($existing->get($offerId)?->quantity ?? 0) + $quantity;
                $allowed = min($wanted, $offer->stock, CartItem::MAX_QUANTITY);

                if ($allowed > 0) {
                    $user->cartItems()->updateOrCreate(['offer_id' => $offerId], ['quantity' => $allowed]);
                }
            }
        });

        return $this->cartOf($user);
    }

    private function cartOf(User $user): CartResource
    {
        $items = $user->cartItems()
            ->with(['offer' => fn ($query) => $query->with(['seller', 'product.game'])->withStock()])
            ->oldest('id')
            ->get();

        return new CartResource($items);
    }

    /**
     * Validates {items: [{offer_id, quantity}]} and returns [offer_id => quantity].
     *
     * @return Collection<int, int>
     */
    private function validatedItems(Request $request): Collection
    {
        $validated = $request->validate([
            'items' => ['present', 'array', 'max:50'],
            'items.*.offer_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.CartItem::MAX_QUANTITY],
        ]);

        return collect($validated['items'])->mapWithKeys(
            fn (array $item) => [(int) $item['offer_id'] => (int) $item['quantity']]
        );
    }

    /**
     * @return Collection<int, Offer> keyed by id
     */
    private function offersWithStock(Collection $ids): Collection
    {
        return Offer::with(['seller', 'product.game'])->withStock()->findMany($ids)->keyBy('id');
    }
}
