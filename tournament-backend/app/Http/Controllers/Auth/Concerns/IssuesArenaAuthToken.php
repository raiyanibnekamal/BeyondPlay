<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

trait IssuesArenaAuthToken
{
    protected function authJsonResponse(User $user, string $message, int $status = 200): JsonResponse
    {
        $token = $user->createToken('auth_token')->plainTextToken;
        $useCookie = (bool) config('arena.httponly_auth_cookie', false);

        $body = [
            'message' => $message,
            'user' => $user,
            'authenticated' => true,
        ];

        if (! $useCookie) {
            $body['token'] = $token;
        }

        $response = response()->json($body, $status);

        if ($useCookie) {
            $response->cookie($this->arenaAuthCookie($token));
        }

        return $response;
    }

    protected function clearAuthCookie(JsonResponse $response): JsonResponse
    {
        if (config('arena.httponly_auth_cookie', false)) {
            $name = config('arena.auth_cookie_name', 'arena_auth');
            $response->withoutCookie($name, '/', config('session.domain'));
        }

        return $response;
    }

    protected function arenaAuthCookie(string $value, int $minutes = 0): Cookie
    {
        $name = config('arena.auth_cookie_name', 'arena_auth');
        $minutes = $minutes < 0 ? -1 : ($minutes ?: (int) config('sanctum.expiration', 10080));

        return cookie(
            $name,
            $value,
            $minutes,
            '/',
            config('session.domain'),
            (bool) config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }
}
