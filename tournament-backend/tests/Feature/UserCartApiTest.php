<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cart_requires_authentication(): void
    {
        $this->getJson('/api/v1/user/cart')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_read_and_sync_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/user/cart')
            ->assertOk()
            ->assertJson(['items' => [], 'coupon_code' => null]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/user/cart', [
                'items' => [
                    ['id' => '1', 'quantity' => 2],
                ],
                'coupon_code' => 'SAVE10',
            ])
            ->assertOk()
            ->assertJsonPath('items.0.id', '1')
            ->assertJsonPath('coupon_code', 'SAVE10');
    }
}
