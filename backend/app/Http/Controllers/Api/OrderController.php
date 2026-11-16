<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * GET /api/orders: the user's orders, newest first (without key codes).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->with('items.product.game')
            ->latest('id')
            ->get();

        return OrderResource::collection($orders);
    }

    /**
     * GET /api/orders/{order}: one order with its key codes.
     */
    public function show(Request $request, Order $order): OrderResource
    {
        // Someone else's order answers 404, not 403, so order ids can't be probed.
        abort_if($order->user_id !== $request->user()->id, 404);

        return new OrderResource($order->load(['items.product.game', 'items.keys']));
    }
}
