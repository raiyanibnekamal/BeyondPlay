<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Weekly Digest</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5;">
    <h1>Your weekly BeyondPlay digest</h1>
    <p>Hi {{ $user->username }},</p>
    <p>Here is your weekly summary:</p>
    @if (!empty($digest))
        <ul>
            @foreach ($digest as $key => $value)
                <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? json_encode($value) : $value }}</li>
            @endforeach
        </ul>
    @else
        <p>No new activity this week — jump into a tournament!</p>
    @endif
    <p>— BeyondPlay Esports</p>
</body>
</html>
