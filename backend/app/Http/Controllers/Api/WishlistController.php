<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WishlistController extends Controller
{
    /**
     * GET /api/wishlist: saved products, most recently saved first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = $request->user()->wishlist()
            ->catalog()
            ->orderByPivot('created_at', 'desc')
            ->orderByPivot('id', 'desc')
            ->get();

        return ProductResource::collection($products);
    }

    /**
     * PUT /api/wishlist/{product}: save (doing it twice is harmless).
     */
    public function store(Request $request, Product $product): Response
    {
        $request->user()->wishlist()->syncWithoutDetaching([$product->id]);

        return response()->noContent();
    }

    /**
     * DELETE /api/wishlist/{product}
     */
    public function destroy(Request $request, Product $product): Response
    {
        $request->user()->wishlist()->detach($product->id);

        return response()->noContent();
    }
}
