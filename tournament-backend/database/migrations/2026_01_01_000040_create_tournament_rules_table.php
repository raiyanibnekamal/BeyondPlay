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
        Schema::create('tournament_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->unique()->constrained('tournaments');
            $table->text('format_details')->nullable();
            $table->text('schedule_info')->nullable();
            $table->text('scoring_rules')->nullable();
            $table->text('code_of_conduct')->nullable();
            $table->text('dispute_policy')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_rules');
    }
};
