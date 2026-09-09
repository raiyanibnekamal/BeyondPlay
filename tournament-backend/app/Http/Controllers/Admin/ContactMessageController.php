<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::query()->orderByDesc('created_at');

        if ($request->filled('unread')) {
            $query->where('is_read', false);
        }

        return response()->json($query->paginate(20));
    }

    public function markRead(int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->update(['is_read' => true]);

        return response()->json(['message' => 'Marked as read.', 'contact_message' => $message]);
    }
}
