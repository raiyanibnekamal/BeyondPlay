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
        Schema::create('point_table', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments');
            $table->foreignId('team_id')->constrained('teams');
            $table->unsignedSmallInteger('matches_played')->default(0);
            $table->unsignedSmallInteger('wins')->default(0);
            $table->unsignedSmallInteger('losses')->default(0);
            $table->unsignedSmallInteger('draws')->default(0);
            $table->unsignedSmallInteger('points')->default(0);
            $table->smallInteger('goal_difference')->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->unique(['tournament_id', 'team_id'], 'unique_entry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_table');
    }
};
