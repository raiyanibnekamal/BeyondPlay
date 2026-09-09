<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Models\GameMatch;
use Illuminate\Http\JsonResponse;

class LiveStreamController extends Controller
{
    public function index(): JsonResponse
    {
        $streams = GameMatch::query()
            ->with([
                'tournament:id,name,slug',
                'team1:id,name',
                'team2:id,name',
            ])
            ->whereNotNull('stream_url')
            ->where('stream_url', '!=', '')
            ->whereIn('status', ['live', 'scheduled'])
            ->orderByRaw("CASE WHEN status = 'live' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->limit(24)
            ->get()
            ->map(function (GameMatch $match) {
                return [
                    'id' => $match->id,
                    'status' => $match->status,
                    'stream_url' => $match->stream_url,
                    'scheduled_at' => $match->scheduled_at,
                    'team1' => $match->team1,
                    'team2' => $match->team2,
                    'tournament' => $match->tournament,
                ];
            });

        return response()->json(['streams' => $streams]);
    }
}
