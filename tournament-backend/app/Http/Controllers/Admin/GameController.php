<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GameController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Game::orderBy('name')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $game = Game::create($data);

        return response()->json(['message' => 'Game created.', 'game' => $game], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $game = Game::findOrFail($id);
        $data = $this->validated($request);

        if (isset($data['name']) && $data['name'] !== $game->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $game->id);
        }

        $game->update($data);

        return response()->json(['message' => 'Game updated.', 'game' => $game]);
    }

    public function destroy(int $id): JsonResponse
    {
        Game::findOrFail($id)->delete();

        return response()->json(['message' => 'Game deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'genre' => ['nullable', 'string', 'max:100'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Game::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
