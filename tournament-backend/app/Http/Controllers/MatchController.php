<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Http\Requests\DisputeRequest;
use App\Models\GameMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $match = GameMatch::with(['tournament:id,name,slug', 'team1:id,name,logo', 'team2:id,name,logo', 'winner:id,name', 'replay'])
            ->findOrFail($id);

        return response()->json($match);
    }

    public function replay(int $id): JsonResponse
    {
        $match = GameMatch::with('replay')->findOrFail($id);

        return response()->json($match->replay);
    }

    public function checkin(Request $request, int $id): JsonResponse
    {
        $match = GameMatch::findOrFail($id);
        $user = $request->user();
        $tournament = $match->tournament;

        $isRegistered = $tournament->registrations()
            ->where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->exists();

        if (! $isRegistered) {
            return response()->json(['message' => 'You are not registered for this tournament.'], 403);
        }

        if (! $match->scheduled_at) {
            return response()->json(['message' => 'This match has no scheduled time yet.'], 422);
        }

        $windowOpens = $match->scheduled_at->copy()->subMinutes($tournament->checkin_minutes_before);
        if (now()->lt($windowOpens) || now()->gt($match->scheduled_at)) {
            return response()->json(['message' => 'Check-in is not open at this time.'], 422);
        }

        if ($match->checkins()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already checked in.'], 422);
        }

        $checkin = $match->checkins()->create([
            'user_id' => $user->id,
            'checked_in_at' => now(),
        ]);

        return response()->json([
            'message' => 'Checked in successfully.',
            'checkin' => $checkin,
        ], 201);
    }

    public function dispute(DisputeRequest $request, int $id): JsonResponse
    {
        $match = GameMatch::findOrFail($id);
        $user = $request->user();

        if ($match->disputes()->where('reported_by', $user->id)->exists()) {
            return response()->json(['message' => 'You have already filed a dispute for this match.'], 422);
        }

        $dispute = $match->disputes()->create([
            'reported_by' => $user->id,
            'issue_type' => $request->issue_type,
            'description' => $request->description,
            'evidence_url' => $request->evidence_url,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Dispute submitted.',
            'dispute' => $dispute,
        ], 201);
    }
}
