<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TournamentRequest;
use App\Models\Tournament;
use App\Services\BracketService;
use App\Services\PredictionScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Tournament::with('game:id,name')->withCount('registrations')->latest()->paginate(15)
        );
    }

    public function store(TournamentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['created_by'] = $request->user()->id;

        $tournament = Tournament::create($data);

        return response()->json(['message' => 'Tournament created.', 'tournament' => $tournament], 201);
    }

    public function update(
        TournamentRequest $request,
        int $tournament,
        PredictionScoringService $predictionScoring
    ): JsonResponse {
        $model = Tournament::findOrFail($tournament);
        $previousStatus = $model->status;
        $model->update($request->validated());

        if ($previousStatus !== 'completed' && $model->status === 'completed') {
            $scored = $predictionScoring->scoreTournament($model);
            $model->setAttribute('predictions_scored', $scored);
        }

        return response()->json(['message' => 'Tournament updated.', 'tournament' => $model]);
    }

    public function destroy(int $tournament): JsonResponse
    {
        Tournament::findOrFail($tournament)->delete();

        return response()->json(['message' => 'Tournament deleted.']);
    }

    public function saveRules(Request $request, int $tournament): JsonResponse
    {
        $model = Tournament::findOrFail($tournament);

        $data = $request->validate([
            'format_details' => ['nullable', 'string'],
            'schedule_info' => ['nullable', 'string'],
            'scoring_rules' => ['nullable', 'string'],
            'code_of_conduct' => ['nullable', 'string'],
            'dispute_policy' => ['nullable', 'string'],
        ]);

        $rule = $model->rule()->updateOrCreate(['tournament_id' => $model->id], $data);

        return response()->json(['message' => 'Rules saved.', 'rule' => $rule]);
    }

    public function generateBracket(int $tournament, BracketService $brackets): JsonResponse
    {
        $model = Tournament::findOrFail($tournament);

        try {
            $data = $brackets->generateBracket($model);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Bracket generated.', 'bracket' => $data]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Tournament::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
