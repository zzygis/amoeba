<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

/**
 * Demo discount codes (listed in the README). Idempotent: matched by code.
 */
class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(['code' => 'WELCOME10'], ['percent_off' => 10, 'expires_at' => null, 'max_uses' => null]);
        Coupon::updateOrCreate(['code' => 'AMOEBA20'], ['percent_off' => 20, 'expires_at' => null, 'max_uses' => null]);
    }
}
