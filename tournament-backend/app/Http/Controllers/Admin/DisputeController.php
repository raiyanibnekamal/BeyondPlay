<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\GameMatch;
use App\Services\BracketService;
use App\Services\NotificationService;
use App\Services\PointTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisputeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Dispute::with(['match:id,tournament_id,round,match_number', 'reporter:id,username']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function resolve(
        Request $request,
        int $dispute,
        NotificationService $notifications,
        BracketService $brackets,
        PointTableService $pointTable
    ): JsonResponse {
        $model = Dispute::with('match')->findOrFail($dispute);
        $match = $model->match;

        $data = $request->validate([
            'status' => ['required', Rule::in(['under_review', 'resolved'])],
            'resolution_note' => ['required_if:status,resolved', 'nullable', 'string'],
            'winner_id' => ['nullable', 'integer'],
            'team1_score' => ['nullable', 'integer', 'min:0'],
            'team2_score' => ['nullable', 'integer', 'min:0'],
        ]);

        $model->status = $data['status'];
        $model->resolution_note = $data['resolution_note'] ?? $model->resolution_note;

        if ($data['status'] === 'resolved') {
            $model->resolved_by = $request->user()->id;
            $model->resolved_at = now();

            if ($match && isset($data['team1_score'], $data['team2_score'])) {
                $match->team1_score = $data['team1_score'];
                $match->team2_score = $data['team2_score'];
                $winnerId = $data['winner_id'] ?? null;
                if (! $winnerId) {
                    if ($match->team1_score > $match->team2_score) {
                        $winnerId = $match->team1_id;
                    } elseif ($match->team2_score > $match->team1_score) {
                        $winnerId = $match->team2_id;
                    }
                }
                if ($winnerId && in_array($winnerId, [$match->team1_id, $match->team2_id], true)) {
                    $match->winner_id = $winnerId;
                    $match->status = 'completed';
                    $match->completed_at = now();
                    $match->save();
                    $pointTable->updateStandings($match);
                    $brackets->advanceWinner($match, $winnerId);
                } else {
                    $match->save();
                }
            }
        }

        $model->save();

        if ($data['status'] === 'resolved') {
            $notifications->create(
                $model->reported_by,
                'dispute_resolved',
                'Your dispute has been resolved',
                $model->resolution_note ?? 'An admin has reviewed your dispute.',
                'dispute.html'
            );
        }

        return response()->json(['message' => 'Dispute updated.', 'dispute' => $model->fresh(['match'])]);
    }
}
