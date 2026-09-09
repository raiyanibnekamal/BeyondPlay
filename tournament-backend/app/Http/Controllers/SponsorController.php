<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Controllers;

use App\Models\Sponsor;
use Illuminate\Http\JsonResponse;
class SponsorController extends Controller
{
    public function index(): JsonResponse
    {
        $tierOrder = "CASE tier WHEN 'title' THEN 1 WHEN 'gold' THEN 2 WHEN 'silver' THEN 3 WHEN 'bronze' THEN 4 ELSE 5 END";

        $sponsors = Sponsor::where('is_active', true)
            ->orderByRaw($tierOrder)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'logo', 'website_url', 'tier', 'sort_order']);

        return response()->json(['sponsors' => $sponsors]);
    }
}
