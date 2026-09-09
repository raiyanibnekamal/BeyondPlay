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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments');
            $table->unsignedTinyInteger('round');
            $table->unsignedSmallInteger('match_number');
            $table->foreignId('team1_id')->nullable()->constrained('teams');
            $table->foreignId('team2_id')->nullable()->constrained('teams');
            $table->unsignedSmallInteger('team1_score')->default(0);
            $table->unsignedSmallInteger('team2_score')->default(0);
            $table->foreignId('winner_id')->nullable()->constrained('teams');
            $table->enum('status', ['scheduled', 'live', 'completed'])->default('scheduled');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('stream_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
