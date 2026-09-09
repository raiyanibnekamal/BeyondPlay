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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games');
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->decimal('entry_fee', 8, 2)->default(0);
            $table->decimal('prize_pool', 10, 2)->default(0);
            $table->unsignedInteger('max_participants')->default(16);
            $table->unsignedInteger('current_participants')->default(0);
            $table->enum('format', ['single_elimination', 'double_elimination', 'round_robin'])->default('single_elimination');
            $table->enum('status', ['draft', 'open', 'ongoing', 'completed', 'cancelled'])->default('draft');
            $table->dateTime('registration_start')->nullable();
            $table->dateTime('registration_end')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->unsignedSmallInteger('checkin_minutes_before')->default(30);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
