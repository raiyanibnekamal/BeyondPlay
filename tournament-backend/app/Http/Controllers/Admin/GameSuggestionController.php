<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameSuggestion;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameSuggestionController extends Controller
{
    public function __construct(
        protected NotificationService $notifications
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = GameSuggestion::with('user:id,username,email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $suggestion = GameSuggestion::where('status', 'pending')->findOrFail($id);

        $slug = $this->uniqueSlug($suggestion->name);

        $game = Game::create([
            'name' => $suggestion->name,
            'slug' => $slug,
            'genre' => $suggestion->genre,
            'description' => $suggestion->description,
            'cover_image' => $suggestion->cover_image_url,
            'status' => 'active',
        ]);

        $suggestion->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->notifications->create(
            $suggestion->user_id,
            'game_suggestion_approved',
            'Game suggestion approved',
            'Your suggestion "'.$suggestion->name.'" was approved and added to the games list.',
            'games/'.$game->slug
        );

        return response()->json([
            'message' => 'Suggestion approved and game created.',
            'game' => $game,
            'suggestion' => $suggestion->fresh(),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $suggestion = GameSuggestion::where('status', 'pending')->findOrFail($id);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $suggestion->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->notifications->create(
            $suggestion->user_id,
            'game_suggestion_rejected',
            'Game suggestion rejected',
            'Your suggestion "'.$suggestion->name.'" was rejected: '.$data['rejection_reason'],
            'suggest-game.html'
        );

        return response()->json([
            'message' => 'Suggestion rejected.',
            'suggestion' => $suggestion,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 1;

        while (Game::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
