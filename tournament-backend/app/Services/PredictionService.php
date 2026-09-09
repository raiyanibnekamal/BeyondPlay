<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Services;

use App\Models\BracketPrediction;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\User;

class PredictionService
{
    public function __construct(
        protected AchievementService $achievements
    ) {
    }

    public function submit(User $user, Tournament $tournament, array $predictions): BracketPrediction
    {
        if (! in_array($tournament->status, ['open', 'draft'], true)) {
            throw new \InvalidArgumentException('Predictions are only allowed before the tournament starts.');
        }

        if ($tournament->start_date && $tournament->start_date->isPast()) {
            throw new \InvalidArgumentException('This tournament has already started.');
        }

        if ($tournament->matches()->count() === 0) {
            throw new \InvalidArgumentException('Bracket must be generated before submitting predictions.');
        }

        $validated = $this->validatePredictions($tournament, $predictions);

        $record = BracketPrediction::updateOrCreate(
            ['tournament_id' => $tournament->id, 'user_id' => $user->id],
            ['predictions' => $validated, 'score' => 0, 'scored_at' => null]
        );

        $this->achievements->checkAndAward($user);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $predictions
     * @return array<string, int>
     */
    private function validatePredictions(Tournament $tournament, array $predictions): array
    {
        if ($predictions === []) {
            throw new \InvalidArgumentException('At least one match prediction is required.');
        }

        $validated = [];

        foreach ($predictions as $key => $teamId) {
            if (! preg_match('/^match_(\d+)$/', (string) $key, $matches)) {
                throw new \InvalidArgumentException("Invalid prediction key: {$key}");
            }

            $match = GameMatch::where('tournament_id', $tournament->id)
                ->find((int) $matches[1]);

            if (! $match) {
                throw new \InvalidArgumentException("Match {$matches[1]} does not belong to this tournament.");
            }

            $teamId = (int) $teamId;
            $validTeams = array_filter([(int) $match->team1_id, (int) $match->team2_id]);

            if (! in_array($teamId, $validTeams, true)) {
                throw new \InvalidArgumentException("Team {$teamId} is not a participant in match {$match->id}.");
            }

            $validated['match_'.$match->id] = $teamId;
        }

        return $validated;
    }
}
