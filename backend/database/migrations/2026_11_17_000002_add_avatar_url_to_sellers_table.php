<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A seller's profile picture. The demo sellers use cover art of a game from the
     * catalog (hotlinked from Steam like the rest of the images); null = initial avatar.
     */
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn('avatar_url');
        });
    }
};
