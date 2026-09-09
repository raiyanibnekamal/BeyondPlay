<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notifications
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notifications->markAllRead($request->user()->id);

        return response()->json([
            'message' => 'All notifications marked as read.',
            'updated' => $count,
        ]);
    }
}
