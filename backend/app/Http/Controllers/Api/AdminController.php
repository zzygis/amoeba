<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\HomeBlock;
use App\Models\Offer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * A small back office: sellers' prices and key uploads, discount codes, homepage
 * blocks and recent orders.
 * Every route here is behind auth:sanctum + admin (see routes/api.php).
 */
class AdminController extends Controller
{
    /**
     * GET /api/admin/offers: every seller's offer, grouped by product, with stock and sales.
     */
    public function offers(): JsonResponse
    {
        $offers = Offer::query()
            ->with(['seller', 'product.game'])
            ->withStock()
            ->withCount(['keys as sold' => fn ($q) => $q->whereNotNull('sold_at')])
            ->get()
            ->sortBy(fn (Offer $o) => [$o->product->name(), $o->price_cents]) // by product, then cheapest seller
            ->values();

        return response()->json(['data' => $offers->map(fn (Offer $offer) => [
            'id' => $offer->id,
            'product' => $offer->product->name(),
            'product_slug' => $offer->product->slug,
            'seller' => $offer->seller->name,
            'seller_avatar_url' => $offer->seller->avatar_url,
            'price_cents' => $offer->price_cents,
            'list_price_cents' => $offer->product->original_price_cents,
            'stock' => (int) $offer->stock,
            'sold' => (int) $offer->sold,
        ])]);
    }

    /**
     * PUT /api/admin/offers/{offer}  {price_cents}
     * Existing orders keep the price they were bought at (order_items snapshot).
     */
    public function updateOffer(Request $request, Offer $offer): JsonResponse
    {
        $offer->update($request->validate([
            'price_cents' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]));

        return response()->json(['data' => ['id' => $offer->id, 'price_cents' => $offer->price_cents]]);
    }

    /**
     * POST /api/admin/offers/{offer}/keys  {codes: "AAAAA-BBBBB-CCCCC\nDDDDD-..."}
     * Adds keys to one seller's offer (one per line). Blank lines and duplicates within
     * the upload are skipped.
     */
    public function addKeys(Request $request, Offer $offer): JsonResponse
    {
        $request->validate(['codes' => ['required', 'string', 'max:50000']]);

        $codes = collect(preg_split('/\R/', $request->input('codes')))
            ->map(fn (string $code) => trim($code))
            ->filter(fn (string $code) => $code !== '' && strlen($code) <= 100)
            ->unique()
            ->values();

        if ($codes->isEmpty() || $codes->count() > 500) {
            return response()->json(['message' => 'Paste between 1 and 500 keys, one per line.', 'errors' => [
                'codes' => ['Paste between 1 and 500 keys, one per line.'],
            ]], 422);
        }

        // createMany goes through the model, so every code is encrypted by its cast.
        $offer->keys()->createMany($codes->map(fn (string $code) => ['code' => $code]));

        return response()->json([
            'added' => $codes->count(),
            'stock' => $offer->availableKeys()->count(),
        ], 201);
    }

    /**
     * GET /api/admin/home-blocks: all homepage blocks, including hidden ones.
     */
    public function homeBlocks(): JsonResponse
    {
        return response()->json(['data' => HomeBlock::orderBy('position')->get()->map(fn (HomeBlock $block) => [
            'id' => $block->id,
            'type' => $block->type,
            'title' => $block->title,
            'position' => $block->position,
            'enabled' => $block->enabled,
            'settings' => $block->settings,
        ])]);
    }

    /**
     * PUT /api/admin/home-blocks/{homeBlock}  {title, position, enabled}
     * Rename, reorder or hide a homepage block; the homepage changes without a deploy.
     */
    public function updateHomeBlock(Request $request, HomeBlock $homeBlock): JsonResponse
    {
        $homeBlock->update($request->validate([
            'title' => ['required', 'string', 'max:100'],
            'position' => ['required', 'integer', 'min:0', 'max:1000'],
            'enabled' => ['required', 'boolean'],
        ]));

        return response()->json(['data' => ['id' => $homeBlock->id]]);
    }

    /**
     * GET /api/admin/coupons
     */
    public function coupons(): JsonResponse
    {
        return response()->json(['data' => Coupon::orderBy('code')->get()->map(fn (Coupon $coupon) => [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'percent_off' => $coupon->percent_off,
            'expires_at' => $coupon->expires_at?->toIso8601String(),
            'max_uses' => $coupon->max_uses,
            'times_used' => $coupon->times_used,
            'usable' => $coupon->isUsable(),
        ])]);
    }

    /**
     * POST /api/admin/coupons  {code, percent_off, expires_at?, max_uses?}
     */
    public function storeCoupon(Request $request): JsonResponse
    {
        $request->merge(['code' => Coupon::normalize((string) $request->input('code'))]);
        $validated = $request->validate([
            'code' => ['required', 'string', 'alpha_num', 'max:32', Rule::unique('coupons')],
            'percent_off' => ['required', 'integer', 'min:1', 'max:90'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ]);

        $coupon = Coupon::create($validated);

        return response()->json(['data' => ['id' => $coupon->id, 'code' => $coupon->code]], 201);
    }

    /**
     * DELETE /api/admin/coupons/{coupon}
     * Past orders keep the code as text (orders.coupon_code), so nothing breaks.
     */
    public function destroyCoupon(Coupon $coupon): Response
    {
        $coupon->delete();

        return response()->noContent();
    }

    /**
     * GET /api/admin/orders: the 50 latest orders from all customers (no key codes).
     */
    public function orders(): JsonResponse
    {
        $orders = Order::with(['user', 'items'])->latest('id')->limit(50)->get();

        return response()->json(['data' => $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'created_at' => $order->created_at->toIso8601String(),
            'customer' => $order->user->email,
            'items' => $order->items->map(fn ($item) => "{$item->quantity} × {$item->product_name}")->all(),
            'coupon_code' => $order->coupon_code,
            'total_cents' => $order->total_cents,
            'payment_method' => $order->payment_method,
        ])]);
    }
}
