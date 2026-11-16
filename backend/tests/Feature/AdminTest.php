<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Game;
use App\Models\HomeBlock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_customers_get_403(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/offers')->assertForbidden();
        $this->postJson('/api/admin/coupons', ['code' => 'FREE', 'percent_off' => 90])->assertForbidden();
    }

    public function test_cannot_make_yourself_admin_through_the_api(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'is_admin' => true,
        ])->assertCreated()->assertJsonPath('user.is_admin', false);

        $this->withToken($response->json('token'))->putJson('/api/user', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'is_admin' => true,
        ])->assertOk()->assertJsonPath('data.is_admin', false);
    }

    public function test_admin_lists_offers_with_seller_stock_and_sold_counts(): void
    {
        $this->actingAsAdmin();
        $offer = $this->offer(keys: 3);
        $offer->keys()->first()->update(['sold_at' => now()]);

        $this->getJson('/api/admin/offers')
            ->assertOk()
            ->assertJsonPath('data.0.seller', $offer->seller->name)
            ->assertJsonPath('data.0.stock', 2)
            ->assertJsonPath('data.0.sold', 1);
    }

    public function test_admin_offers_are_sorted_by_product_then_price(): void
    {
        $this->actingAsAdmin();
        $zelda = Product::factory()->for(Game::factory()->state(['title' => 'Zelda']))->create();
        $alan = Product::factory()->for(Game::factory()->state(['title' => 'Alan Wake']))->create();
        $this->offer(900, product: $zelda);
        $this->offer(2000, product: $alan);
        $this->offer(1500, product: $alan);

        $rows = collect($this->getJson('/api/admin/offers')->json('data'))
            ->map(fn (array $row) => $row['price_cents'])
            ->all();

        $this->assertSame([1500, 2000, 900], $rows);
    }

    public function test_admin_changes_an_offers_price(): void
    {
        $this->actingAsAdmin();
        $offer = $this->offer(1000);

        $this->putJson("/api/admin/offers/{$offer->id}", ['price_cents' => 0])->assertUnprocessable();
        $this->putJson("/api/admin/offers/{$offer->id}", ['price_cents' => 800])
            ->assertOk()
            ->assertJsonPath('data.price_cents', 800);
    }

    public function test_admin_uploads_keys_one_per_line_skipping_blanks_and_duplicates(): void
    {
        $this->actingAsAdmin();
        $offer = $this->offer(keys: 0);

        $this->postJson("/api/admin/offers/{$offer->id}/keys", ['codes' => "AAA-111\n\n  BBB-222  \nAAA-111\r\nCCC-333"])
            ->assertCreated()
            ->assertJson(['added' => 3, 'stock' => 3]);

        $this->assertNotSame('AAA-111', $offer->keys()->first()->getRawOriginal('code')); // stored encrypted
    }

    public function test_admin_renames_reorders_and_hides_homepage_blocks(): void
    {
        $this->actingAsAdmin();
        $block = HomeBlock::create(['key' => 'deals', 'type' => 'product_row', 'title' => 'Deals', 'position' => 1, 'settings' => []]);

        $this->putJson("/api/admin/home-blocks/{$block->id}", ['title' => 'Hot deals', 'position' => 5, 'enabled' => false])
            ->assertOk();

        $this->getJson('/api/admin/home-blocks')->assertJsonPath('data.0.title', 'Hot deals');
        $this->getJson('/api/home')->assertJsonCount(0, 'data'); // hidden blocks aren't served
    }

    public function test_admin_creates_lists_and_deletes_coupons(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/admin/coupons', ['code' => 'summer15', 'percent_off' => 15, 'max_uses' => 100])
            ->assertCreated()
            ->assertJsonPath('data.code', 'SUMMER15');
        $this->postJson('/api/admin/coupons', ['code' => 'SUMMER15', 'percent_off' => 10])
            ->assertUnprocessable(); // already exists
        $this->postJson('/api/admin/coupons', ['code' => 'TOOMUCH', 'percent_off' => 95])
            ->assertUnprocessable();

        $this->getJson('/api/admin/coupons')->assertJsonPath('data.0.code', 'SUMMER15');

        $this->deleteJson('/api/admin/coupons/'.Coupon::first()->id)->assertNoContent();
        $this->assertSame(0, Coupon::count());
    }

    public function test_admin_sees_recent_orders_from_all_customers(): void
    {
        $customer = User::factory()->create(['email' => 'buyer@example.com']);
        $customer->cartItems()->create(['offer_id' => $this->offer()->id, 'quantity' => 1]);
        Sanctum::actingAs($customer);
        $this->postJson('/api/checkout', ['payment_method' => 'card'])->assertCreated();

        $this->actingAsAdmin();

        $this->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('data.0.customer', 'buyer@example.com');
    }

    public function test_make_admin_command(): void
    {
        $user = User::factory()->create(['email' => 'boss@example.com']);

        $this->artisan('app:make-admin', ['email' => 'boss@example.com'])->assertSuccessful();
        $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])->assertFailed();

        $this->assertTrue($user->fresh()->is_admin);
    }
}
