<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->header('X-Arena-Page');

        if ($path && is_string($path) && strlen($path) <= 500) {
            PageView::create([
                'path' => $path,
                'referrer' => $request->header('Referer'),
                'viewed_at' => now(),
            ]);
        }

        return $next($request);
    }
}
