<?php

namespace Tests\Feature;

use App\Models\ProductKey;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_codes_encrypted_with_another_app_key_are_replaced_and_readable_ones_kept(): void
    {
        $offer = $this->offer(keys: 0);
        $good = $offer->keys()->create(['code' => 'GOOD-CODE-1']);
        $bad = $offer->keys()->create(['code' => 'ANY']);

        // Simulate a code written by a server with a different APP_KEY.
        $otherKey = new Encrypter(random_bytes(32), 'aes-256-cbc');
        DB::table('product_keys')->where('id', $bad->id)->update(['code' => $otherKey->encryptString('OLD-CODE-1')]);

        $this->artisan('app:repair-keys')->expectsOutput('Replaced 1 unreadable key codes.')->assertSuccessful();

        $this->assertSame('GOOD-CODE-1', $good->fresh()->code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $bad->fresh()->code);
        $this->artisan('app:repair-keys')->expectsOutput('All key codes are readable.');
    }

    public function test_fake_codes_look_like_keys(): void
    {
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', ProductKey::fakeCode());
    }
}
