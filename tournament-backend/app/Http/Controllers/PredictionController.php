<?php

namespace App\Http\Controllers;

use App\Models\BracketPrediction;
use App\Models\Tournament;
use App\Services\PredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    public function store(Request $request, int $id, PredictionService $predictions): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        $data = $request->validate([
            'predictions' => ['required', 'array', 'min:1'],
        ]);

        try {
            $record = $predictions->submit(
                $request->user(),
                $tournament,
                $data['predictions']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Prediction submitted.',
            'prediction' => $record,
        ], 201);
    }

    public function leaderboard(int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        $leaderboard = BracketPrediction::with('user:id,username,avatar')
            ->where('tournament_id', $tournament->id)
            ->orderByDesc('score')
            ->orderBy('created_at')
            ->get()
            ->map(fn (BracketPrediction $p) => [
                'user' => $p->user,
                'score' => $p->score,
                'scored_at' => $p->scored_at,
                'submitted_at' => $p->created_at,
            ]);

        return response()->json([
            'tournament_id' => $tournament->id,
            'leaderboard' => $leaderboard,
        ]);
    }
}
