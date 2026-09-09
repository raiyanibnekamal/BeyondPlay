<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    private const KEYS = [
        'site_name',
        'site_url',
        'contact_email',
        'twitter_url',
        'discord_url',
        'youtube_url',
        'instagram_url',
        'maintenance_mode',
    ];

    private const DEFAULTS = [
        'site_name' => 'BeyondPlay',
        'site_url' => 'https://arena.gg',
        'contact_email' => 'support@arena.gg',
        'twitter_url' => '',
        'discord_url' => '',
        'youtube_url' => '',
        'instagram_url' => '',
        'maintenance_mode' => '0',
    ];

    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => SiteSetting::getMany(self::KEYS, self::DEFAULTS),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:120'],
            'site_url' => ['nullable', 'url', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:500'],
            'discord_url' => ['nullable', 'url', 'max:500'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'instagram_url' => ['nullable', 'url', 'max:500'],
            'maintenance_mode' => ['nullable', 'boolean'],
        ]);

        $pairs = [];
        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $pairs[$key] = $key === 'maintenance_mode'
                ? ($data[$key] ? '1' : '0')
                : ($data[$key] ?? '');
        }

        if ($pairs !== []) {
            SiteSetting::setMany($pairs);
        }

        return response()->json([
            'message' => 'Settings saved.',
            'settings' => SiteSetting::getMany(self::KEYS, self::DEFAULTS),
        ]);
    }
}
