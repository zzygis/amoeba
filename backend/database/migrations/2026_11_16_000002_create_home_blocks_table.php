<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The homepage is a list of blocks stored here, not in the React code. Each block has
     * a type (which backend class fills it with content, see app/Home/Blocks) and settings.
     * Changing the homepage (order, titles, which deals) is a data change, not a deploy.
     */
    public function up(): void
    {
        Schema::create('home_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // stable name for the seeder, e.g. "top-games"
            $table->string('type', 30);      // banner, platforms, product_row
            $table->string('title');
            $table->unsignedSmallInteger('position');
            $table->boolean('enabled')->default(true);
            $table->json('settings');        // e.g. {"sort": "discount", "limit": 6}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_blocks');
    }
};
