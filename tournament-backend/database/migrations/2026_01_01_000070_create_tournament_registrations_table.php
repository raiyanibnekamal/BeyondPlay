<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->enum('status', ['pending', 'confirmed', 'disqualified'])->default('pending');
            $table->timestamp('registered_at')->useCurrent();
            $table->unique(['tournament_id', 'user_id'], 'unique_registration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registrations');
    }
};
