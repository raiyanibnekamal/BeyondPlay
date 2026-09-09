<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function achievements(Request $request): JsonResponse
    {
        $achievements = $request->user()
            ->achievements()
            ->orderByDesc('user_achievements.earned_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'description' => $a->description,
                'icon' => $a->icon,
                'earned_at' => $a->pivot->earned_at,
            ]);

        return response()->json(['achievements' => $achievements]);
    }

    public function tournaments(Request $request): JsonResponse
    {
        $registrations = $request->user()
            ->registrations()
            ->with(['tournament.game:id,name,slug', 'team:id,name'])
            ->orderByDesc('registered_at')
            ->get();

        return response()->json(['registrations' => $registrations]);
    }
}
