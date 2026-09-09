<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->select([
            'id', 'username', 'email', 'role', 'status', 'country', 'created_at',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        return response()->json($query->orderByDesc('id')->paginate(20));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'banned'])],
        ]);

        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id && $data['status'] === 'banned') {
            return response()->json(['message' => 'You cannot ban your own account.'], 422);
        }

        $user->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'User status updated.',
            'user' => $user->fresh(['id', 'username', 'email', 'role', 'status']),
        ]);
    }

    public function promote(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You are already an admin.'], 422);
        }

        $user->update(['role' => 'admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->syncRoles([$adminRole]);

        return response()->json([
            'message' => 'User promoted to admin.',
            'user' => $user->fresh(['id', 'username', 'email', 'role']),
        ]);
    }
}
