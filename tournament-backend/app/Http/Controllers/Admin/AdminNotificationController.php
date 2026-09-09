<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminNotificationController extends Controller
{
    public function stats(): JsonResponse
    {
        return app(AnalyticsController::class)->dashboard();
    }

    public function send(Request $request, NotificationService $notifications): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:2000'],
            'link' => ['nullable', 'string', 'max:500'],
            'audience' => ['nullable', Rule::in(['all', 'active'])],
        ]);

        $query = User::query()->where('status', 'active');
        if (! empty($data['user_id'])) {
            $query->where('id', $data['user_id']);
        }

        $count = 0;
        foreach ($query->pluck('id') as $userId) {
            $notifications->create(
                $userId,
                'admin_broadcast',
                $data['title'],
                $data['message'],
                $data['link'] ?? null
            );
            $count++;
        }

        return response()->json([
            'message' => 'Notifications sent.',
            'recipient_count' => $count,
        ]);
    }

    public function approveComment(int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->update(['status' => 'approved']);

        return response()->json(['message' => 'Comment approved.', 'comment' => $comment]);
    }

    public function deleteComment(int $id): JsonResponse
    {
        Comment::findOrFail($id)->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
