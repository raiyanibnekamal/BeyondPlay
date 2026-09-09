<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\AchievementService;
use App\Services\BracketService;
use App\Services\PaymentService;
use App\Services\SoloTeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    public function __construct(
        protected AchievementService $achievements
    ) {
    }
    public function index(Request $request): JsonResponse
    {
        $query = Tournament::with('game:id,name,slug')->withCount('registrations');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        return response()->json($query->latest('start_date')->paginate(12));
    }

    public function show(string $slug): JsonResponse
    {
        $tournament = Tournament::with(['game', 'creator:id,username', 'rule'])
            ->withCount('registrations')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($tournament);
    }

    public function rules(int $id): JsonResponse
    {
        $tournament = Tournament::with('rule')->findOrFail($id);

        return response()->json($tournament->rule);
    }

    public function register(Request $request, int $id, PaymentService $payments, SoloTeamService $soloTeams): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'payment_id' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $registration = DB::transaction(function () use ($request, $user, $id, $payments, $soloTeams) {
                $tournament = Tournament::lockForUpdate()->findOrFail($id);

                if ($tournament->status !== 'open') {
                    throw new \RuntimeException('Registration is not open for this tournament.', 422);
                }

                if ($tournament->registration_end && now()->gt($tournament->registration_end)) {
                    throw new \RuntimeException('Registration period has ended.', 422);
                }

                if ($tournament->registration_start && now()->lt($tournament->registration_start)) {
                    throw new \RuntimeException('Registration has not started yet.', 422);
                }

                if ($tournament->registrations()->where('user_id', $user->id)->exists()) {
                    throw new \RuntimeException('You are already registered.', 422);
                }

                if ($tournament->max_participants
                    && $tournament->current_participants >= $tournament->max_participants) {
                    throw new \RuntimeException('This tournament is full.', 422);
                }

                $entryFee = (float) ($tournament->entry_fee ?? 0);
                $paymentId = $payments->assertEntryFeePaid($entryFee, $request->input('payment_id'), $user->id);

                $teamId = $request->input('team_id');
                if (! $teamId) {
                    $teamId = $soloTeams->ensureSoloTeam($user)->id;
                }

                $reg = $tournament->registrations()->create([
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                    'status' => 'confirmed',
                    'payment_id' => $paymentId,
                    'registered_at' => now(),
                ]);

                $tournament->increment('current_participants');

                return $reg;
            });
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                $code >= 400 && $code < 600 ? $code : 422
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->achievements->checkAndAward($user);

        return response()->json([
            'message' => 'Registered successfully.',
            'registration' => $registration,
        ], 201);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'game_id' => ['required', 'exists:games,id'],
            'max_participants' => ['required', 'integer', 'min:2', 'max:1024'],
            'prize_pool' => ['nullable', 'numeric', 'min:0'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'format' => ['nullable', 'in:single_elimination,double_elimination,round_robin'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'draft';
        $validated['current_participants'] = 0;
        $validated['slug'] = $this->uniqueSlug($validated['name']);
        $validated['format'] = $validated['format'] ?? 'single_elimination';

        $tournament = Tournament::create($validated);

        return response()->json([
            'message' => 'Tournament created.',
            'data' => $tournament,
        ], 201);
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

    public function withdraw(Request $request, int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $deleted = $tournament->registrations()
            ->where('user_id', $request->user()->id)
            ->delete();

        if ($deleted) {
            $tournament->decrement('current_participants');
        }

        return response()->json([
            'message' => $deleted ? 'Registration withdrawn.' : 'You were not registered for this tournament.',
        ], $deleted ? 200 : 404);
    }

    public function bracket(int $id, BracketService $brackets): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        return response()->json($brackets->getBracketData($tournament));
    }

    public function matches(int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $matches = $tournament->matches()
            ->with(['team1:id,name', 'team2:id,name', 'winner:id,name'])
            ->orderBy('round')
            ->orderBy('match_number')
            ->get();

        return response()->json($matches);
    }

    public function standings(int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $standings = $tournament->pointTable()
            ->with('team:id,name,logo')
            ->orderByDesc('points')
            ->orderByDesc('goal_difference')
            ->get();

        return response()->json($standings);
    }

    public function history(): JsonResponse
    {
        $tournaments = Tournament::with('game:id,name')
            ->where('status', 'completed')
            ->latest('end_date')
            ->paginate(12);

        return response()->json($tournaments);
    }
}
