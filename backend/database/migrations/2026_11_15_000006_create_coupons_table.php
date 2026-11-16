<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Discount codes entered in the cart ("Got discount code?").
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique(); // stored upper-case, e.g. WELCOME10
            $table->unsignedTinyInteger('percent_off'); // 1-90
            $table->timestamp('expires_at')->nullable(); // null = never expires
            $table->unsignedInteger('max_uses')->nullable(); // null = unlimited
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
