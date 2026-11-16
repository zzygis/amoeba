<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A game holds everything that's the same whichever platform you buy it for:
     * description, developer, screenshots, requirements. What you actually buy is a
     * product (a listing: this game + platform + region + price), see the products table.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type', 20)->default('game')->index(); // game, dlc or software
            $table->text('description');
            $table->string('developer');
            $table->string('publisher');
            $table->date('release_date')->nullable();
            $table->string('cover_url'); // box art, normally portrait
            $table->boolean('cover_portrait')->default(true); // false: only a wide image exists (e.g. some DLC)
            // Number of Steam user reviews: a real-world popularity signal for "Most popular" sorting.
            $table->unsignedInteger('steam_reviews')->default(0);
            // Simple lists stored as JSON: they're only ever shown, never filtered on in SQL.
            $table->json('genres');       // ["Action", "RPG"]
            $table->json('modes');        // ["Single-player", "Online Co-op"]
            $table->json('languages');    // ["English", "German"]
            $table->json('screenshots');  // image URLs
            $table->json('requirements'); // minimum PC specs: [["OS", "Windows 10"], ...]
            $table->json('trailer')->nullable(); // {title, poster, hls_url} streamed from Steam
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
