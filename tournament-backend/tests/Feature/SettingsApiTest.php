<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_and_update_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/settings')
            ->assertOk()
            ->assertJsonPath('settings.site_name', 'BeyondPlay');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/settings', [
                'site_name' => 'BeyondPlay Pro',
                'contact_email' => 'hello@BeyondPlay.gg',
            ])
            ->assertOk()
            ->assertJsonPath('settings.site_name', 'BeyondPlay Pro');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'site_name',
            'value' => 'BeyondPlay Pro',
        ]);
    }

    public function test_player_cannot_update_settings(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player, 'sanctum')
            ->putJson('/api/v1/admin/settings', ['site_name' => 'Hacked'])
            ->assertForbidden();
    }
}
