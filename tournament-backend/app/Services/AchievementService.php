<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Friend;
use App\Models\User;
use App\Models\UserAchievement;

class AchievementService
{
    public function __construct(
        protected NotificationService $notifications
    ) {
    }

    /**
     * Evaluate all achievement conditions for a user and award any newly earned badges.
     *
     * @return UserAchievement[]
     */
    public function checkAndAward(User $user): array
    {
        $awarded = [];
        $existing = $user->achievements()->pluck('achievements.id')->all();

        $stats = $user->playerStats()
            ->selectRaw('COALESCE(SUM(matches_played),0) as total_matches')
            ->selectRaw('COALESCE(SUM(wins),0) as total_wins')
            ->selectRaw('COALESCE(SUM(tournament_wins),0) as total_tournament_wins')
            ->first();

        $totalMatches = (int) ($stats->total_matches ?? 0);
        $totalWins = (int) ($stats->total_wins ?? 0);
        $totalTournamentWins = (int) ($stats->total_tournament_wins ?? 0);

        $friendsCount = Friend::where('status', 'accepted')
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->count();

        $correctPredictions = $user->bracketPredictions()->sum('score');

        $metrics = [
            'matches_played' => $totalMatches,
            'wins' => $totalWins,
            'tournament_wins' => $totalTournamentWins,
            'friends_count' => $friendsCount,
            'predictions_correct' => (int) $correctPredictions,
        ];

        $achievements = Achievement::all();

        foreach ($achievements as $achievement) {
            if (in_array($achievement->id, $existing, true)) {
                continue;
            }

            $metric = $metrics[$achievement->condition_type] ?? null;
            if ($metric === null) {
                continue;
            }

            if ($metric >= $achievement->condition_value) {
                $record = UserAchievement::create([
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                    'earned_at' => now(),
                ]);

                $this->notifications->create(
                    $user->id,
                    'achievement_earned',
                    'Achievement unlocked: '.$achievement->name,
                    $achievement->description,
                    'achievements.html'
                );

                $awarded[] = $record;
                $existing[] = $achievement->id;
            }
        }

        return $awarded;
    }
}
