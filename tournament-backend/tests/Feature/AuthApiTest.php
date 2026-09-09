<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login_return_token(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'username' => 'TestPlayer',
            'email' => 'testplayer@arena.test',
            'password' => 'Arena@2026!',
            'password_confirmation' => 'Arena@2026!',
        ]);

        $register->assertCreated()
            ->assertJsonStructure(['token', 'user']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'testplayer@arena.test',
            'password' => 'Arena@2026!',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_authenticated_me_returns_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }
}
