<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\UserAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ActivityFeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $friendIds = $this->friendUserIds($user->id);
        $items = collect();

        if ($friendIds !== []) {
            $matchWins = GameMatch::with(['tournament:id,name,slug', 'winner:id,name'])
                ->where('status', 'completed')
                ->whereNotNull('winner_id')
                ->where('completed_at', '>=', now()->subDays(30))
                ->whereIn('winner_id', function ($q) use ($friendIds) {
                    $q->select('team_id')
                        ->from('team_members')
                        ->whereIn('user_id', $friendIds)
                        ->distinct();
                })
                ->orderByDesc('completed_at')
                ->limit(15)
                ->get();

            foreach ($matchWins as $match) {
                $winnerUserIds = DB::table('team_members')
                    ->where('team_id', $match->winner_id)
                    ->whereIn('user_id', $friendIds)
                    ->pluck('user_id');

                foreach ($winnerUserIds as $friendId) {
                    $items->push([
                        'type' => 'friend_match_win',
                        'user_id' => $friendId,
                        'tournament' => $match->tournament?->only(['id', 'name', 'slug']),
                        'match_id' => $match->id,
                        'occurred_at' => $match->completed_at,
                    ]);
                }
            }

            $registrations = TournamentRegistration::with(['user:id,username,avatar', 'tournament:id,name,slug'])
                ->whereIn('user_id', $friendIds)
                ->where('registered_at', '>=', now()->subDays(30))
                ->orderByDesc('registered_at')
                ->limit(15)
                ->get();

            foreach ($registrations as $reg) {
                $items->push([
                    'type' => 'friend_tournament_join',
                    'user' => $reg->user?->only(['id', 'username', 'avatar']),
                    'tournament' => $reg->tournament?->only(['id', 'name', 'slug']),
                    'occurred_at' => $reg->registered_at,
                ]);
            }

            $badges = UserAchievement::with(['user:id,username,avatar', 'achievement:id,name,icon'])
                ->whereIn('user_id', $friendIds)
                ->where('earned_at', '>=', now()->subDays(30))
                ->orderByDesc('earned_at')
                ->limit(15)
                ->get();

            foreach ($badges as $badge) {
                $items->push([
                    'type' => 'friend_achievement',
                    'user' => $badge->user?->only(['id', 'username', 'avatar']),
                    'achievement' => $badge->achievement?->only(['id', 'name', 'icon']),
                    'occurred_at' => $badge->earned_at,
                ]);
            }
        }

        $newTournaments = Tournament::where('status', 'open')
            ->where('created_at', '>=', now()->subDays(14))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'name', 'slug', 'status', 'start_date', 'created_at']);

        foreach ($newTournaments as $tournament) {
            $items->push([
                'type' => 'new_tournament',
                'tournament' => $tournament,
                'occurred_at' => $tournament->created_at,
            ]);
        }

        $sorted = $items->sortByDesc(fn ($item) => $item['occurred_at'])->values();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $slice = $sorted->forPage($page, $perPage)->values()->all();
        $paginator = new LengthAwarePaginator(
            $slice,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'feed' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * @return int[]
     */
    private function friendUserIds(int $userId): array
    {
        return Friend::where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->get()
            ->map(fn (Friend $f) => $f->requester_id === $userId ? $f->receiver_id : $f->requester_id)
            ->unique()
            ->values()
            ->all();
    }
}
