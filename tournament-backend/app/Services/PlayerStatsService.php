<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\PlayerStat;
use Illuminate\Support\Facades\DB;

class PlayerStatsService
{
    public function updateFromMatch(GameMatch $match): void
    {
        $match->loadMissing('tournament');
        if (! $match->tournament) {
            return;
        }

        $gameId = $match->tournament->game_id;
        $winnerTeamId = $match->winner_id;

        foreach (
            [
                ['team_id' => $match->team1_id, 'score' => (int) $match->team1_score],
                ['team_id' => $match->team2_id, 'score' => (int) $match->team2_score],
            ] as $side
        ) {
            if (! $side['team_id']) {
                continue;
            }

            $isWinner = $winnerTeamId && (int) $side['team_id'] === (int) $winnerTeamId;
            $isLoser = $winnerTeamId && ! $isWinner;

            $userIds = DB::table('team_members')
                ->where('team_id', $side['team_id'])
                ->pluck('user_id');

            foreach ($userIds as $userId) {
                $stat = PlayerStat::firstOrCreate(
                    ['user_id' => $userId, 'game_id' => $gameId],
                    [
                        'matches_played' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'kills' => 0,
                        'deaths' => 0,
                        'total_score' => 0,
                        'tournament_wins' => 0,
                        'win_streak' => 0,
                        'best_streak' => 0,
                    ]
                );

                $stat->matches_played++;
                $stat->total_score += $side['score'];

                if ($isWinner) {
                    $stat->wins++;
                    $stat->win_streak = (int) $stat->win_streak + 1;
                    if ($stat->win_streak > $stat->best_streak) {
                        $stat->best_streak = $stat->win_streak;
                    }
                } elseif ($isLoser) {
                    $stat->losses++;
                    $stat->win_streak = 0;
                }

                $stat->updated_at = now();
                $stat->save();
            }
        }
    }
}
