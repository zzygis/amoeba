<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A logged-in user's cart: one row per offer (a product from a specific seller).
     * No separate "carts" table, because each user has exactly one cart. (Guests keep
     * their cart in the browser's localStorage; it's merged in here when they log in.)
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();

            $table->unique(['user_id', 'offer_id']); // an offer appears once; quantity says how many
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
