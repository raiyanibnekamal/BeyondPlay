<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
        ]);

        $user = User::query()
            ->where('username', $data['username'])
            ->first(['id', 'username', 'avatar']);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json($user);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'username' => ['sometimes', 'string', 'min:3', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'country' => ['nullable', 'string', 'max:100'],
            'gaming_id' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated.',
            'user' => $user->fresh(),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar && str_starts_with($user->avatar, '/storage/')) {
            $old = ltrim(str_replace('/storage/', '', $user->avatar), '/');
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $url = '/storage/'.$path;
        $user->update(['avatar' => $url]);

        return response()->json([
            'message' => 'Avatar updated.',
            'avatar' => $url,
            'user' => $user->fresh(),
        ]);
    }
}
