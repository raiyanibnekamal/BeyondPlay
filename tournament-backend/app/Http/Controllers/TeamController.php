<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Team::with('captain:id,username')->withCount('members')->paginate(15)
        );
    }

    public function show(string $slug): JsonResponse
    {
        $team = Team::with(['captain:id,username', 'members:id,username,avatar'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($team);
    }

    public function store(TeamRequest $request): JsonResponse
    {
        $user = $request->user();

        $team = Team::create([
            'name' => $request->name,
            'slug' => $this->uniqueSlug($request->name),
            'logo' => $request->logo,
            'captain_id' => $user->id,
            'status' => 'active',
        ]);

        $team->members()->attach($user->id, ['role' => 'captain', 'joined_at' => now()]);

        return response()->json(['message' => 'Team created.', 'team' => $team], 201);
    }

    public function update(TeamRequest $request, int $id): JsonResponse
    {
        $team = Team::findOrFail($id);

        if ($team->captain_id !== $request->user()->id) {
            return response()->json(['message' => 'Only the team captain can update this team.'], 403);
        }

        $team->update($request->only(['name', 'logo', 'status']));

        return response()->json(['message' => 'Team updated.', 'team' => $team]);
    }

    public function invite(Request $request, int $id, NotificationService $notifications): JsonResponse
    {
        $team = Team::findOrFail($id);

        if ($team->captain_id !== $request->user()->id) {
            return response()->json(['message' => 'Only the team captain can invite members.'], 403);
        }

        $request->validate(['user_id' => ['required', 'exists:users,id']]);

        if ($team->members()->where('user_id', $request->user_id)->exists()) {
            return response()->json(['message' => 'User is already a member.'], 422);
        }

        $invite = TeamInvite::updateOrCreate(
            ['team_id' => $team->id, 'invited_user_id' => $request->user_id],
            ['invited_by' => $request->user()->id, 'status' => 'pending']
        );

        $notifications->create(
            (int) $request->user_id,
            'team_invite',
            'Team invitation',
            'You were invited to join '.$team->name,
            'team-details.html?slug='.$team->slug
        );

        return response()->json(['message' => 'Invitation sent.', 'invite' => $invite], 201);
    }

    public function myInvites(Request $request): JsonResponse
    {
        $invites = TeamInvite::with(['team:id,name,slug', 'inviter:id,username'])
            ->where('invited_user_id', $request->user()->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json(['invites' => $invites]);
    }

    public function acceptInvite(Request $request, int $inviteId): JsonResponse
    {
        $invite = TeamInvite::where('id', $inviteId)
            ->where('invited_user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $invite->update(['status' => 'accepted']);
        $invite->team->members()->syncWithoutDetaching([
            $request->user()->id => ['role' => 'member', 'joined_at' => now()],
        ]);

        return response()->json(['message' => 'You joined the team.', 'team' => $invite->team]);
    }

    public function declineInvite(Request $request, int $inviteId): JsonResponse
    {
        $invite = TeamInvite::where('id', $inviteId)
            ->where('invited_user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $invite->update(['status' => 'declined']);

        return response()->json(['message' => 'Invitation declined.']);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
