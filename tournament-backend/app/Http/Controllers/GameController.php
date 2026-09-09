<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Http\Requests\GameSuggestionRequest;
use App\Models\Game;
use App\Models\GameSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Game::where('status', 'active')->withCount('tournaments')->get()
        );
    }

    public function show(string $slug): JsonResponse
    {
        $game = Game::with(['tournaments' => function ($q) {
            $q->whereIn('status', ['open', 'ongoing'])->latest('start_date');
        }])->where('slug', $slug)->firstOrFail();

        return response()->json($game);
    }

    public function suggest(GameSuggestionRequest $request): JsonResponse
    {
        $suggestion = GameSuggestion::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'genre' => $request->genre,
            'description' => $request->description,
            'cover_image_url' => $request->cover_image_url,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Game suggestion submitted.',
            'suggestion' => $suggestion,
        ], 201);
    }

    public function mySuggestions(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->gameSuggestions()->latest()->get()
        );
    }
}
