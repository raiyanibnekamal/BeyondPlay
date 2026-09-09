<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\PlayerStat;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return $this->player($request, $request->user()->id);
    }

    public function player(Request $request, int $userId): JsonResponse
    {
        $user = User::query()->find($userId, ['id', 'username', 'avatar', 'country', 'gaming_id', 'bio']);

        if (! $user) {
            return response()->json(['message' => 'Player not found.'], 404);
        }

        $query = PlayerStat::with('game:id,name,slug')
            ->where('user_id', $userId);

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->integer('game_id'));
        }

        $stats = $query->get()->map(fn (PlayerStat $stat) => $this->formatStat($stat));

        $totals = [
            'matches_played' => $stats->sum('matches_played'),
            'wins' => $stats->sum('wins'),
            'losses' => $stats->sum('losses'),
            'kills' => $stats->sum('kills'),
            'deaths' => $stats->sum('deaths'),
            'total_score' => $stats->sum('total_score'),
            'tournament_wins' => $stats->sum('tournament_wins'),
        ];

        $totals['kd_ratio'] = $totals['deaths'] > 0
            ? round($totals['kills'] / $totals['deaths'], 2)
            : (float) $totals['kills'];
        $totals['win_rate'] = $totals['matches_played'] > 0
            ? round($totals['wins'] / $totals['matches_played'] * 100, 1)
            : 0.0;
        $totals['avg_score'] = $totals['matches_played'] > 0
            ? round($totals['total_score'] / $totals['matches_played'], 1)
            : 0.0;

        return response()->json([
            'user_id' => $userId,
            'user' => $user,
            'by_game' => $stats,
            'totals' => $totals,
        ]);
    }

    public function headToHead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'player1' => ['required', 'integer', 'exists:users,id'],
            'player2' => ['required', 'integer', 'exists:users,id', 'different:player1'],
            'game_id' => ['nullable', 'integer', 'exists:games,id'],
        ]);

        $player1 = User::findOrFail($data['player1']);
        $player2 = User::findOrFail($data['player2']);

        $stats1Query = PlayerStat::where('user_id', $player1->id);
        $stats2Query = PlayerStat::where('user_id', $player2->id);

        if (! empty($data['game_id'])) {
            $stats1Query->where('game_id', $data['game_id']);
            $stats2Query->where('game_id', $data['game_id']);
        }

        $record = ['player1_wins' => 0, 'player2_wins' => 0, 'draws' => 0, 'matches' => []];

        $matches = GameMatch::with(['tournament:id,name,game_id', 'team1:id,name', 'team2:id,name', 'winner:id,name'])
            ->where('status', 'completed')
            ->when(! empty($data['game_id']), fn ($q) => $q->whereHas(
                'tournament',
                fn ($t) => $t->where('game_id', $data['game_id'])
            ))
            ->orderByDesc('completed_at')
            ->get();

        foreach ($matches as $match) {
            $p1OnTeam1 = $this->userOnTeam($player1->id, $match->team1_id);
            $p1OnTeam2 = $this->userOnTeam($player1->id, $match->team2_id);
            $p2OnTeam1 = $this->userOnTeam($player2->id, $match->team1_id);
            $p2OnTeam2 = $this->userOnTeam($player2->id, $match->team2_id);

            $faced = ($p1OnTeam1 && $p2OnTeam2) || ($p1OnTeam2 && $p2OnTeam1);
            if (! $faced) {
                continue;
            }

            $entry = [
                'match_id' => $match->id,
                'tournament' => $match->tournament?->only(['id', 'name']),
                'completed_at' => $match->completed_at,
                'winner_id' => $match->winner_id,
            ];

            if (! $match->winner_id) {
                $record['draws']++;
                $entry['result'] = 'draw';
            } elseif ($this->userOnTeam($player1->id, $match->winner_id)) {
                $record['player1_wins']++;
                $entry['result'] = 'player1';
            } else {
                $record['player2_wins']++;
                $entry['result'] = 'player2';
            }

            $record['matches'][] = $entry;
        }

        return response()->json([
            'player1' => [
                'id' => $player1->id,
                'username' => $player1->username,
                'stats' => $stats1Query->get()->map(fn (PlayerStat $s) => $this->formatStat($s)),
            ],
            'player2' => [
                'id' => $player2->id,
                'username' => $player2->username,
                'stats' => $stats2Query->get()->map(fn (PlayerStat $s) => $this->formatStat($s)),
            ],
            'head_to_head' => $record,
        ]);
    }

    private function userOnTeam(int $userId, ?int $teamId): bool
    {
        if (! $teamId) {
            return false;
        }

        return DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function formatStat(PlayerStat $stat): array
    {
        return [
            'game' => $stat->game,
            'game_id' => $stat->game_id,
            'matches_played' => $stat->matches_played,
            'wins' => $stat->wins,
            'losses' => $stat->losses,
            'kills' => $stat->kills,
            'deaths' => $stat->deaths,
            'total_score' => $stat->total_score,
            'tournament_wins' => $stat->tournament_wins,
            'win_streak' => $stat->win_streak,
            'best_streak' => $stat->best_streak,
            'kd_ratio' => $stat->kd_ratio,
            'win_rate' => $stat->win_rate,
            'avg_score' => $stat->avg_score,
            'updated_at' => $stat->updated_at,
        ];
    }
}
