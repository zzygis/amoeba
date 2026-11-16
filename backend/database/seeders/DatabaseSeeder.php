<?php

namespace Database\Seeders;

use App\Support\CatalogCache;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: `php artisan db:seed`.
     */
    public function run(): void
    {
        // All idempotent, so this runs on every deploy (docker/start.sh).
        $this->call([SellerSeeder::class, ProductSeeder::class, ReviewSeeder::class, CouponSeeder::class, HomeBlockSeeder::class]);

        // The catalog may have changed: don't serve a cached homepage from before.
        CatalogCache::flush();
    }
}
