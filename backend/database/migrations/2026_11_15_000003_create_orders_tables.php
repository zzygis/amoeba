<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // total = subtotal - discount. Stored separately so the receipt can show all three.
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->string('coupon_code', 32)->nullable(); // snapshot of the code used, if any
            $table->string('payment_method', 20); // demo only: "card" or "paypal", no real payment is taken
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Products/offers that have been sold can't be deleted (restrict), so order history stays intact.
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('offer_id')->constrained()->restrictOnDelete();
            // Snapshots: what the customer saw and paid at checkout, even if things change later.
            $table->string('product_name');
            $table->string('seller_name');
            $table->unsignedInteger('price_cents');
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
