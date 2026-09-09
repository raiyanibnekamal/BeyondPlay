<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_replays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->unique()->constrained('matches');
            $table->string('vod_url', 500);
            $table->string('platform', 100)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->foreignId('added_by')->constrained('users');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_replays');
    }
};
