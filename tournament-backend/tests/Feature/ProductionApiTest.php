<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_password_rejected_on_register(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'username' => 'weakuser',
            'email' => 'weak@arena.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable();
    }

    public function test_user_lookup_by_username(): void
    {
        $user = User::factory()->create(['username' => 'lookupme']);

        $this->getJson('/api/v1/users/lookup?username=lookupme')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_tournament_history_returns_paginated_list(): void
    {
        $this->getJson('/api/v1/tournaments/history')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page']);
    }

    public function test_head_to_head_requires_two_users(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $this->getJson('/api/v1/stats/head-to-head?player1='.$u1->id.'&player2='.$u2->id)
            ->assertOk()
            ->assertJsonStructure(['player1', 'player2', 'head_to_head']);
    }
}
