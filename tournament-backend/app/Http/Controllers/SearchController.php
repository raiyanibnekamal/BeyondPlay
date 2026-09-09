<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Product;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json([
                'query' => $q,
                'tournaments' => [],
                'games' => [],
                'products' => [],
                'teams' => [],
                'users' => [],
            ]);
        }

        $like = '%'.$q.'%';

        $tournaments = Tournament::query()
            ->where('name', 'like', $like)
            ->orWhere('slug', 'like', $like)
            ->limit(8)
            ->get(['id', 'name', 'slug', 'status']);

        $games = Game::query()
            ->where('name', 'like', $like)
            ->orWhere('slug', 'like', $like)
            ->limit(8)
            ->get(['id', 'name', 'slug']);

        $products = Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->limit(8)
            ->get(['id', 'name', 'slug', 'price']);

        $teams = Team::query()
            ->where('name', 'like', $like)
            ->orWhere('slug', 'like', $like)
            ->limit(8)
            ->get(['id', 'name', 'slug']);

        $users = User::query()
            ->where('username', 'like', $like)
            ->where('status', 'active')
            ->limit(8)
            ->get(['id', 'username', 'avatar']);

        return response()->json([
            'query' => $q,
            'tournaments' => $tournaments,
            'games' => $games,
            'products' => $products,
            'teams' => $teams,
            'users' => $users,
        ]);
    }
}
