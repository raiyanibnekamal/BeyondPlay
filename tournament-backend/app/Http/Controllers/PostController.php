<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = BlogPost::with('author:id,username')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(12);

        return response()->json($posts);
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::with([
            'author:id,username,avatar',
            'comments' => fn ($q) => $q->where('status', 'approved')
                ->with('user:id,username,avatar')
                ->latest(),
        ])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json($post);
    }

    public function storeComment(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::where('status', 'published')->findOrFail($id);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'commentable_type' => BlogPost::class,
            'commentable_id' => $post->id,
            'content' => $data['content'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Comment submitted for moderation.',
            'comment' => $comment,
        ], 201);
    }
}
