<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * A product is one listing, e.g. "Elden Ring Steam Key GLOBAL": a game on one
         * platform for one region. It has no price of its own: sellers make offers for it.
         */
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique(); // used in URLs: /games/elden-ring-steam-global
            $table->string('platform', 50);   // Steam, Xbox, PlayStation, ...
            $table->string('region', 50);     // GLOBAL, EUROPE, ...
            // The publisher's list price ("was €59.99"), used to show each offer's discount.
            // Money is stored as integer cents (EUR) to avoid floating-point rounding errors.
            $table->unsignedInteger('original_price_cents')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'platform', 'region']);
        });

        /*
         * One seller selling one product at their own price, with their own keys.
         * The product's "From €X" price is its cheapest offer that still has keys.
         */
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_cents');
            $table->timestamps();

            $table->unique(['product_id', 'seller_id']); // one offer per seller per product
            $table->index(['product_id', 'price_cents']); // "cheapest offer" lookups
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
        Schema::dropIfExists('products');
    }
};
