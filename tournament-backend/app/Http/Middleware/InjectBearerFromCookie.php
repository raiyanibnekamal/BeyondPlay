<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectBearerFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            $name = config('arena.auth_cookie_name', 'arena_auth');
            $token = $request->cookie($name);
            if ($token) {
                $request->headers->set('Authorization', 'Bearer '.$token);
            }
        }

        return $next($request);
    }
}
