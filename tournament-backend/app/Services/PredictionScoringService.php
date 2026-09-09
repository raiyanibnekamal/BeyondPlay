<?php

namespace App\Services;

use App\Models\BracketPrediction;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\User;

class PredictionScoringService
{
    public function __construct(
        protected AchievementService $achievements
    ) {
    }

    /**
     * Score all predictions for a tournament (1 point per correct match winner).
     *
     * @return int Number of prediction records scored
     */
    public function scoreTournament(Tournament $tournament): int
    {
        $results = GameMatch::where('tournament_id', $tournament->id)
            ->where('status', 'completed')
            ->whereNotNull('winner_id')
            ->get(['id', 'winner_id']);

        if ($results->isEmpty()) {
            return 0;
        }

        $count = 0;

        BracketPrediction::where('tournament_id', $tournament->id)
            ->with('user')
            ->each(function (BracketPrediction $prediction) use ($results, &$count) {
                $points = 0;

                foreach ($results as $match) {
                    $key = 'match_'.$match->id;
                    $picked = $prediction->predictions[$key] ?? null;

                    if ($picked !== null && (int) $picked === (int) $match->winner_id) {
                        $points++;
                    }
                }

                $prediction->update([
                    'score' => $points,
                    'scored_at' => now(),
                ]);

                if ($prediction->user instanceof User) {
                    $this->achievements->checkAndAward($prediction->user);
                }

                $count++;
            });

        return $count;
    }
}
