<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The marketplace's sellers. Like on eneba.com, several sellers can offer the same
     * product at different prices, and the buyer picks one (see the offers table).
     */
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('rating', 4, 2); // 0.00-10.00, shown as e.g. "9.85 Superb"
            $table->boolean('verified')->default(false); // "Verified by Amoeba" badge
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
