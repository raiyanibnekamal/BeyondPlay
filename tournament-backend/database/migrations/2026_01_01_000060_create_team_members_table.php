<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('role', ['captain', 'member'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->unique(['team_id', 'user_id'], 'unique_member');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
