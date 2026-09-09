<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            BlogPost::with('author:id,username')->latest()->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['title']);

        $post = BlogPost::create($data);

        return response()->json(['message' => 'Post created.', 'post' => $post], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $data = $this->validated($request, $post);

        if (isset($data['title']) && $data['title'] !== $post->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $post->id);
        }

        $post->update($data);

        return response()->json(['message' => 'Post updated.', 'post' => $post]);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $post = BlogPost::findOrFail($id);
        $post->update([
            'status' => 'published',
            'published_at' => $post->published_at ?? now(),
        ]);

        return response()->json(['message' => 'Post published.', 'post' => $post]);
    }

    public function destroy(int $id): JsonResponse
    {
        BlogPost::findOrFail($id)->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    private function validated(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'title' => [$post ? 'sometimes' : 'required', 'string', 'max:300'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
        ]);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (BlogPost::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
