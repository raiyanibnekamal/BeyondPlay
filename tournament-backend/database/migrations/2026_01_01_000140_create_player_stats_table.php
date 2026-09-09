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
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('game_id')->constrained('games');
            $table->unsignedInteger('matches_played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedInteger('deaths')->default(0);
            $table->unsignedBigInteger('total_score')->default(0);
            $table->unsignedInteger('tournament_wins')->default(0);
            $table->unsignedSmallInteger('win_streak')->default(0);
            $table->unsignedSmallInteger('best_streak')->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id', 'game_id'], 'unique_player_game');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_stats');
    }
};
