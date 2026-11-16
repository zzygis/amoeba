<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each row is one game key (activation code), uploaded by a seller for their offer.
     * Stock is not stored anywhere: it is the number of keys not yet sold,
     * so it can never drift out of sync with the keys themselves.
     * (Table is product_keys, not keys: KEYS is a reserved word in MySQL.)
     */
    public function up(): void
    {
        Schema::create('product_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            // Encrypted with APP_KEY by the model cast; ciphertext is long, hence text.
            $table->text('code');
            // Set together at checkout: which order line bought this key, and when.
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sold_at')->nullable(); // null = still available
            $table->timestamps();

            // Speeds up "count/find available keys for this offer".
            $table->index(['offer_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_keys');
    }
};
