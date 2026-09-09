<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Services;

use App\Events\NewNotification;
use App\Models\Notification;

class NotificationService
{
    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
            'created_at' => now(),
        ]);

        broadcast(new NewNotification($notification));

        return $notification;
    }

    public function markAllRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
