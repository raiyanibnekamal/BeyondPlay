<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Services;

use App\Models\GameMatch;
use App\Models\PointTable;

class PointTableService
{
    /**
     * Update the point table for both teams after a match is completed.
     */
    public function updateStandings(GameMatch $match): void
    {
        if (! $match->team1_id || ! $match->team2_id) {
            return;
        }

        $team1 = $this->row($match->tournament_id, $match->team1_id);
        $team2 = $this->row($match->tournament_id, $match->team2_id);

        $team1->matches_played++;
        $team2->matches_played++;

        $team1->goal_difference += ($match->team1_score - $match->team2_score);
        $team2->goal_difference += ($match->team2_score - $match->team1_score);

        if ($match->team1_score > $match->team2_score) {
            $team1->wins++;
            $team1->points += 3;
            $team2->losses++;
        } elseif ($match->team2_score > $match->team1_score) {
            $team2->wins++;
            $team2->points += 3;
            $team1->losses++;
        } else {
            $team1->draws++;
            $team2->draws++;
            $team1->points++;
            $team2->points++;
        }

        $team1->updated_at = now();
        $team2->updated_at = now();
        $team1->save();
        $team2->save();
    }

    private function row(int $tournamentId, int $teamId): PointTable
    {
        return PointTable::firstOrNew([
            'tournament_id' => $tournamentId,
            'team_id' => $teamId,
        ]);
    }
}
