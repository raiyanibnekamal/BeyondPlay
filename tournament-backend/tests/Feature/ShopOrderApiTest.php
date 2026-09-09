<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 29.99,
            'stock' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'manual',
                'shipping_name' => 'Test User',
                'shipping_address' => '1 Arena St',
                'shipping_city' => 'City',
                'shipping_phone' => '555-0100',
            ])
            ->assertCreated()
            ->assertJsonStructure(['order' => ['id'], 'payment_status'])
            ->assertJsonPath('payment_status', 'pending');

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => 'pending']);
    }

    public function test_products_list_is_public(): void
    {
        Product::factory()->create(['status' => 'active']);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
