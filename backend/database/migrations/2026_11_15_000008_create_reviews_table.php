<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer reviews. They belong to the game (not one listing), so a review of
     * the Steam version also shows on the Xbox page. Only buyers can write one.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5 stars
            $table->text('body');
            $table->timestamps();

            $table->unique(['user_id', 'game_id']); // one review per game per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
