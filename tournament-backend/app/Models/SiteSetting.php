<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function getMany(array $keys, array $defaults = []): array
    {
        $stored = Cache::remember('site_settings', 300, function () {
            return self::query()->pluck('value', 'key')->all();
        });

        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $stored[$key] ?? ($defaults[$key] ?? '');
        }

        return $out;
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        Cache::forget('site_settings');
    }
}
