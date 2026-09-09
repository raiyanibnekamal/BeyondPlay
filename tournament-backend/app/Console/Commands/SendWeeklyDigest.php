<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Console\Commands;

use App\Mail\WeeklyDigestMail;
use App\Models\GameMatch;
use App\Models\PlayerStat;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigest extends Command
{
    protected $signature = 'arena:weekly-digest';

    protected $description = 'Send weekly digest emails to active users';

    public function handle(): int
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $upcoming = Tournament::whereIn('status', ['open', 'ongoing'])
            ->whereBetween('start_date', [$weekStart, $weekEnd])
            ->orderBy('start_date')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'start_date', 'status']);

        $recentResults = GameMatch::with('tournament:id,name')
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(7))
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get(['id', 'tournament_id', 'team1_score', 'team2_score', 'completed_at']);

        $topPlayers = PlayerStat::with('user:id,username')
            ->orderByDesc('wins')
            ->limit(5)
            ->get();

        $digest = [
            'upcoming_tournaments' => $upcoming->toArray(),
            'recent_results' => $recentResults->toArray(),
            'top_players' => $topPlayers->map(fn ($s) => [
                'username' => $s->user?->username,
                'wins' => $s->wins,
                'matches_played' => $s->matches_played,
            ])->all(),
        ];

        $count = 0;

        User::where('status', 'active')->each(function (User $user) use ($digest, &$count) {
            Mail::to($user->email)->send(new WeeklyDigestMail($user, $digest));
            $count++;
        });

        $this->info("Weekly digest sent to {$count} users.");

        return self::SUCCESS;
    }
}
