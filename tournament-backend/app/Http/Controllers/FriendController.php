<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class FriendController extends Controller
{
    public function __construct(
        protected NotificationService $notifications,
        protected AchievementService $achievements
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $paginator = Friend::with(['requester:id,username,avatar', 'receiver:id,username,avatar'])
            ->where('status', 'accepted')
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->orderByDesc('updated_at')
            ->paginate(20);

        $friends = $paginator->getCollection()->map(function (Friend $friendship) use ($user) {
            $friend = $friendship->requester_id === $user->id
                ? $friendship->receiver
                : $friendship->requester;

            return [
                'id' => $friend->id,
                'username' => $friend->username,
                'avatar' => $friend->avatar,
                'online_status' => $this->resolveOnlineStatus((int) $friend->id),
                'friendship_id' => $friendship->id,
            ];
        });

        return response()->json([
            'friends' => $friends->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function requests(Request $request): JsonResponse
    {
        $paginator = Friend::with('requester:id,username,avatar')
            ->where('receiver_id', $request->user()->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(20);

        $incoming = $paginator->getCollection()->map(fn (Friend $f) => [
                'id' => $f->id,
                'requester' => $f->requester,
                'status' => $f->status,
                'created_at' => $f->created_at,
            ]);

        return response()->json([
            'requests' => $incoming->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function sendRequest(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();

        if ($userId === $user->id) {
            return response()->json(['message' => 'You cannot add yourself as a friend.'], 422);
        }

        if (! User::where('id', $userId)->exists()) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $existing = Friend::where(function ($q) use ($user, $userId) {
            $q->where('requester_id', $user->id)->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($user, $userId) {
            $q->where('requester_id', $userId)->where('receiver_id', $user->id);
        })->first();

        if ($existing) {
            if ($existing->status === 'accepted') {
                return response()->json(['message' => 'You are already friends.'], 422);
            }
            if ($existing->status === 'pending') {
                return response()->json(['message' => 'A friend request already exists.'], 422);
            }
            if ($existing->status === 'blocked') {
                return response()->json(['message' => 'Unable to send friend request.'], 422);
            }
        }

        $friendship = Friend::create([
            'requester_id' => $user->id,
            'receiver_id' => $userId,
            'status' => 'pending',
        ]);

        $this->notifications->create(
            $userId,
            'friend_request',
            'New friend request',
            $user->username.' sent you a friend request.',
            'friends.html'
        );

        return response()->json([
            'message' => 'Friend request sent.',
            'request' => $friendship,
        ], 201);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $friendship = Friend::where('id', $id)
            ->where('receiver_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendship->update(['status' => 'accepted']);

        $this->notifications->create(
            $friendship->requester_id,
            'friend_accepted',
            'Friend request accepted',
            $request->user()->username.' accepted your friend request.',
            'friends.html'
        );

        $this->achievements->checkAndAward($request->user());
        $this->achievements->checkAndAward(User::find($friendship->requester_id));

        return response()->json([
            'message' => 'Friend request accepted.',
            'friendship' => $friendship->fresh(),
        ]);
    }

    public function decline(Request $request, int $id): JsonResponse
    {
        $friendship = Friend::where('id', $id)
            ->where('receiver_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendship->update(['status' => 'declined']);

        return response()->json(['message' => 'Friend request declined.']);
    }

    public function remove(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();

        $deleted = Friend::where('status', 'accepted')
            ->where(function ($q) use ($user, $userId) {
                $q->where(function ($inner) use ($user, $userId) {
                    $inner->where('requester_id', $user->id)->where('receiver_id', $userId);
                })->orWhere(function ($inner) use ($user, $userId) {
                    $inner->where('requester_id', $userId)->where('receiver_id', $user->id);
                });
            })
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Friendship not found.'], 404);
        }

        return response()->json(['message' => 'Friend removed.']);
    }

    public function sentRequests(Request $request): JsonResponse
    {
        $paginator = Friend::with('receiver:id,username,avatar')
            ->where('requester_id', $request->user()->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(20);

        $sent = $paginator->getCollection()->map(fn (Friend $f) => [
            'id' => $f->id,
            'receiver' => $f->receiver,
            'status' => $f->status,
            'created_at' => $f->created_at,
        ]);

        return response()->json([
            'requests' => $sent->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function cancelRequest(Request $request, int $id): JsonResponse
    {
        $friendship = Friend::where('id', $id)
            ->where('requester_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendship->delete();

        return response()->json(['message' => 'Friend request cancelled.']);
    }

    public function relationship(Request $request, int $userId): JsonResponse
    {
        $me = $request->user();

        if ($userId === $me->id) {
            return response()->json(['status' => 'self']);
        }

        $friendship = Friend::where(function ($q) use ($me, $userId) {
            $q->where('requester_id', $me->id)->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($me, $userId) {
            $q->where('requester_id', $userId)->where('receiver_id', $me->id);
        })->first();

        if (! $friendship) {
            return response()->json(['status' => 'none']);
        }

        if ($friendship->status === 'accepted') {
            return response()->json([
                'status' => 'friends',
                'friendship_id' => $friendship->id,
            ]);
        }

        if ($friendship->status === 'pending') {
            if ($friendship->requester_id === $me->id) {
                return response()->json([
                    'status' => 'pending_sent',
                    'request_id' => $friendship->id,
                ]);
            }

            return response()->json([
                'status' => 'pending_received',
                'request_id' => $friendship->id,
            ]);
        }

        return response()->json(['status' => 'none']);
    }

    protected function resolveOnlineStatus(int $userId): string
    {
        $lastUsed = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $userId)
            ->max('last_used_at');

        if (! $lastUsed) {
            return 'offline';
        }

        return \Carbon\Carbon::parse($lastUsed)->gte(now()->subMinutes(15)) ? 'online' : 'offline';
    }
}
