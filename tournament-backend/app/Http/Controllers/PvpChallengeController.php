<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Models\PvpChallenge;
use App\Models\PvpMatch;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SoloTeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PvpChallengeController extends Controller
{
    public function __construct(
        protected NotificationService $notifications,
        protected SoloTeamService $soloTeams
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'challenged_id' => ['required', 'integer', 'exists:users,id'],
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        if ((int) $data['challenged_id'] === $user->id) {
            return response()->json(['message' => 'You cannot challenge yourself.'], 422);
        }

        $pending = PvpChallenge::where('challenger_id', $user->id)
            ->where('challenged_id', $data['challenged_id'])
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return response()->json(['message' => 'You already have a pending challenge to this player.'], 422);
        }

        $challenge = PvpChallenge::create([
            'challenger_id' => $user->id,
            'challenged_id' => $data['challenged_id'],
            'game_id' => $data['game_id'],
            'message' => $data['message'] ?? null,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $challenged = User::find($data['challenged_id']);
        $this->notifications->create(
            $challenged->id,
            'pvp_challenge',
            'New PvP challenge',
            $user->username.' challenged you to a match.',
            'friends.html'
        );

        return response()->json([
            'message' => 'Challenge sent.',
            'challenge' => $challenge->load(['challenger:id,username', 'game:id,name']),
        ], 201);
    }

    public function incoming(Request $request): JsonResponse
    {
        $challenges = PvpChallenge::with(['challenger:id,username,avatar', 'game:id,name,slug'])
            ->where('challenged_id', $request->user()->id)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['challenges' => $challenges]);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $challenge = PvpChallenge::where('id', $id)
            ->where('challenged_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $challenge->update(['status' => 'accepted']);

        $challenger = User::findOrFail($challenge->challenger_id);
        $challenged = $request->user();
        $team1 = $this->soloTeams->ensureSoloTeam($challenger);
        $team2 = $this->soloTeams->ensureSoloTeam($challenged);

        $pvpMatch = PvpMatch::create([
            'challenge_id' => $challenge->id,
            'game_id' => $challenge->game_id,
            'team1_id' => $team1->id,
            'team2_id' => $team2->id,
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        $this->notifications->create(
            $challenge->challenger_id,
            'pvp_challenge_accepted',
            'Challenge accepted',
            $request->user()->username.' accepted your PvP challenge.',
            'friends.html'
        );

        return response()->json([
            'message' => 'Challenge accepted. Match scheduled.',
            'challenge' => $challenge->fresh(['challenger:id,username', 'game:id,name']),
            'match' => $pvpMatch->load(['team1:id,name', 'team2:id,name', 'game:id,name']),
        ]);
    }

    public function decline(Request $request, int $id): JsonResponse
    {
        $challenge = PvpChallenge::where('id', $id)
            ->where('challenged_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $challenge->update(['status' => 'declined']);

        $this->notifications->create(
            $challenge->challenger_id,
            'pvp_challenge_declined',
            'Challenge declined',
            $request->user()->username.' declined your PvP challenge.',
            'friends.html'
        );

        return response()->json([
            'message' => 'Challenge declined.',
            'challenge' => $challenge,
        ]);
    }

    public function sent(Request $request): JsonResponse
    {
        $challenges = PvpChallenge::with(['challenged:id,username,avatar', 'game:id,name,slug'])
            ->where('challenger_id', $request->user()->id)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['challenges' => $challenges]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $challenge = PvpChallenge::where('id', $id)
            ->where('challenger_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $challenge->update(['status' => 'declined']);

        return response()->json(['message' => 'Challenge cancelled.', 'challenge' => $challenge]);
    }

    public function statusWithUser(Request $request, int $userId): JsonResponse
    {
        $me = $request->user()->id;

        if ($userId === $me) {
            return response()->json(['status' => 'self']);
        }

        $challenge = PvpChallenge::where('status', 'pending')
            ->where(function ($q) use ($me, $userId) {
                $q->where(function ($inner) use ($me, $userId) {
                    $inner->where('challenger_id', $me)->where('challenged_id', $userId);
                })->orWhere(function ($inner) use ($me, $userId) {
                    $inner->where('challenger_id', $userId)->where('challenged_id', $me);
                });
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        if (! $challenge) {
            return response()->json(['status' => 'none']);
        }

        if ($challenge->challenger_id === $me) {
            return response()->json([
                'status' => 'pending_sent',
                'challenge_id' => $challenge->id,
            ]);
        }

        return response()->json([
            'status' => 'pending_received',
            'challenge_id' => $challenge->id,
        ]);
    }
}
