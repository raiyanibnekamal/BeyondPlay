<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bracket_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments');
            $table->foreignId('user_id')->constrained('users');
            $table->json('predictions');
            $table->unsignedSmallInteger('score')->default(0);
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();
            $table->unique(['tournament_id', 'user_id'], 'unique_prediction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bracket_predictions');
    }
};
