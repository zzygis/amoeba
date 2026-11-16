<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    /**
     * POST /api/coupons/check  {code}
     * Lets the cart show the discount before paying. The code is checked again at
     * checkout (it could expire or run out in between), so this is only a preview.
     */
    public function check(Request $request): JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:32']])['code'];
        $coupon = Coupon::where('code', Coupon::normalize($code))->first();

        if (! $coupon || ! $coupon->isUsable()) {
            throw ValidationException::withMessages(['code' => 'This discount code is invalid or has expired.']);
        }

        return response()->json(['data' => [
            'code' => $coupon->code,
            'percent_off' => $coupon->percent_off,
        ]]);
    }
}
