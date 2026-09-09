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
        Schema::create('pvp_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenger_id')->constrained('users');
            $table->foreignId('challenged_id')->constrained('users');
            $table->foreignId('game_id')->constrained('games');
            $table->string('message', 300)->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pvp_challenges');
    }
};
