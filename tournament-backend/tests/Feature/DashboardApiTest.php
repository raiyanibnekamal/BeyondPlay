<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_stats_returns_dashboard_totals(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonStructure([
                'totals' => [
                    'users',
                    'active_tournaments',
                    'matches_today',
                    'orders',
                    'open_disputes',
                    'pending_suggestions',
                ],
                'recent_registrations',
            ]);
    }
}
