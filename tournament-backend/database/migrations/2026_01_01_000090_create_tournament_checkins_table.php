<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('checked_in_at')->useCurrent();
            $table->unique(['match_id', 'user_id'], 'unique_checkin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_checkins');
    }
};
