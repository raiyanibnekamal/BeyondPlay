<?php

namespace App\Http\Controllers\Admin;

use App\Events\MatchScoreUpdated;
use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\BracketService;
use App\Services\PlayerStatsService;
use App\Services\PointTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GameMatch::with(['tournament:id,name', 'team1:id,name', 'team2:id,name']);

        if ($request->filled('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        return response()->json(
            $query->orderBy('round')->orderBy('match_number')->paginate(20)
        );
    }

    public function updateScore(
        Request $request,
        int $match,
        PointTableService $pointTable,
        BracketService $brackets,
        AchievementService $achievements,
        PlayerStatsService $playerStats
    ): JsonResponse {
        $model = GameMatch::findOrFail($match);
        $alreadyCompleted = $model->status === 'completed';

        $data = $request->validate([
            'team1_score' => ['required', 'integer', 'min:0'],
            'team2_score' => ['required', 'integer', 'min:0'],
            'winner_id' => ['nullable', 'integer', Rule::in(array_filter([$model->team1_id, $model->team2_id]))],
            'status' => ['nullable', Rule::in(['scheduled', 'live', 'completed'])],
        ]);

        $model->team1_score = $data['team1_score'];
        $model->team2_score = $data['team2_score'];
        $status = $data['status'] ?? 'completed';
        $model->status = $status;

        if ($status === 'completed') {
            $winnerId = $data['winner_id'] ?? null;
            if (! $winnerId) {
                if ($model->team1_score > $model->team2_score) {
                    $winnerId = $model->team1_id;
                } elseif ($model->team2_score > $model->team1_score) {
                    $winnerId = $model->team2_id;
                }
            }
            $model->winner_id = $winnerId;
            $model->completed_at = now();
            $model->save();

            $pointTable->updateStandings($model);

            if ($winnerId) {
                $brackets->advanceWinner($model, $winnerId);
            }

            if (! $alreadyCompleted) {
                $playerStats->updateFromMatch($model->fresh());
            }

            $playerIds = DB::table('team_members')
                ->whereIn('team_id', array_filter([$model->team1_id, $model->team2_id]))
                ->pluck('user_id')
                ->unique();

            foreach ($playerIds as $playerId) {
                $player = User::find($playerId);
                if ($player) {
                    $achievements->checkAndAward($player);
                }
            }
        } else {
            $model->save();
        }

        broadcast(new MatchScoreUpdated($model->fresh()));

        return response()->json([
            'message' => 'Match score updated.',
            'match' => $model->fresh(['team1:id,name', 'team2:id,name', 'winner:id,name']),
        ]);
    }

    public function addReplay(Request $request, int $match): JsonResponse
    {
        $model = GameMatch::findOrFail($match);

        $data = $request->validate([
            'vod_url' => ['required', 'url', 'max:500'],
            'platform' => ['nullable', 'string', 'max:100'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        $replay = $model->replay()->updateOrCreate(
            ['match_id' => $model->id],
            array_merge($data, ['added_by' => $request->user()->id, 'created_at' => now()])
        );

        return response()->json(['message' => 'Replay saved.', 'replay' => $replay]);
    }
}
