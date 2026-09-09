<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Services;

use App\Models\Team;
use App\Models\User;

class SoloTeamService
{
    /**
     * Ensure each solo player has a 1-person team for bracket seeding.
     */
    public function ensureSoloTeam(User $user): Team
    {
        $slug = 'solo-'.$user->id;

        $existing = Team::where('slug', $slug)->first();
        if ($existing) {
            return $existing;
        }

        $team = Team::create([
            'name' => $user->username,
            'slug' => $slug,
            'captain_id' => $user->id,
            'status' => 'active',
        ]);

        $team->members()->attach($user->id, ['role' => 'captain', 'joined_at' => now()]);

        return $team;
    }
}
