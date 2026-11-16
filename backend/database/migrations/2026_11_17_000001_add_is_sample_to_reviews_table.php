<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks the demo reviews written for the seed data (ReviewSeeder), so the site can
     * label them "Sample review" instead of passing them off as real customers'.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('is_sample')->default(false)->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('is_sample');
        });
    }
};
