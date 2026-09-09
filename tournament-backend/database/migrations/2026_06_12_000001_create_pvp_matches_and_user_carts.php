<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pvp_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->unique()->constrained('pvp_challenges')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games');
            $table->foreignId('team1_id')->constrained('teams');
            $table->foreignId('team2_id')->constrained('teams');
            $table->unsignedSmallInteger('team1_score')->default(0);
            $table->unsignedSmallInteger('team2_score')->default(0);
            $table->foreignId('winner_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->enum('status', ['scheduled', 'live', 'completed'])->default('scheduled');
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_carts', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('items')->nullable();
            $table->string('coupon_code', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_carts');
        Schema::dropIfExists('pvp_matches');
    }
};
