<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Mail\OrderConfirmation;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductKey;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    /**
     * POST /api/checkout  {payment_method: "card" | "paypal", coupon_code?: "WELCOME10"}
     *
     * Turns the user's cart into an order and hands out keys, all in one database
     * transaction: either everything happens or nothing does.
     *
     * Two customers buying the last key at the same moment must not both get it.
     * `lockForUpdate()` (SELECT ... FOR UPDATE) locks the key rows we pick, so a
     * second checkout for the same product waits until the first one commits, and
     * then sees those keys as sold. The coupon row is locked the same way, so a
     * code with limited uses can't be used more times than allowed.
     *
     * No real payment is taken: this is a demo, the payment method is only recorded.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(Order::PAYMENT_METHODS)],
            'coupon_code' => ['nullable', 'string', 'max:32'],
        ]);
        $user = $request->user();

        $order = DB::transaction(function () use ($user, $validated) {
            $coupon = null;
            if (! empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', Coupon::normalize($validated['coupon_code']))->lockForUpdate()->first();
                if (! $coupon || ! $coupon->isUsable()) {
                    throw ValidationException::withMessages(['coupon_code' => 'This discount code is invalid or has expired.']);
                }
            }

            // Always lock offers in the same order (by id) so two checkouts can't deadlock.
            $cartItems = $user->cartItems()->with(['offer.seller', 'offer.product.game'])->orderBy('offer_id')->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $order = $user->orders()->create([
                'subtotal_cents' => 0, // filled in below
                'total_cents' => 0,
                'payment_method' => $validated['payment_method'],
            ]);

            $subtotal = 0;

            foreach ($cartItems as $cartItem) {
                $offer = $cartItem->offer;
                $product = $offer->product;
                $seller = $offer->seller->name;

                // Keys come from the seller the buyer chose, never from another seller.
                $keys = $offer->availableKeys()
                    ->orderBy('id')
                    ->limit($cartItem->quantity)
                    ->lockForUpdate()
                    ->get();

                if ($keys->count() < $cartItem->quantity) {
                    // Throwing inside DB::transaction rolls back everything done so far.
                    $left = $keys->count();
                    throw ValidationException::withMessages(['cart' => $left === 0
                        ? "{$seller} has sold out of {$product->name()}. Please remove it or pick another seller."
                        : "{$seller} has only {$left} ".Str::plural('key', $left)." left for {$product->name()}. Please lower the quantity."]);
                }

                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'offer_id' => $offer->id,
                    'product_name' => $product->name(),
                    'seller_name' => $seller,
                    'price_cents' => $offer->price_cents,
                    'quantity' => $cartItem->quantity,
                ]);

                ProductKey::whereKey($keys->modelKeys())->update([
                    'order_item_id' => $orderItem->id,
                    'sold_at' => now(),
                ]);

                $subtotal += $offer->price_cents * $cartItem->quantity;
            }

            $discount = $coupon?->discountFor($subtotal) ?? 0;
            $coupon?->increment('times_used');

            $order->update([
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'total_cents' => $subtotal - $discount,
                'coupon_code' => $coupon?->code,
            ]);
            $user->cartItems()->delete();

            return $order;
        });

        $order->load(['items.product.game', 'items.keys']);

        // Stock changed: a product on the homepage may have just sold out.
        CatalogCache::flush();

        // Email the keys. Sent after the transaction has committed, and a mail problem
        // must not undo a completed order: the keys are on the order page anyway.
        try {
            Mail::to($user)->send(new OrderConfirmation($order));
        } catch (Throwable $e) {
            report($e);
        }

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}
