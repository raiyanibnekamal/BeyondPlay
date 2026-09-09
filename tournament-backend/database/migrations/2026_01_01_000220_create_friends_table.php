<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('receiver_id')->constrained('users');
            $table->enum('status', ['pending', 'accepted', 'declined', 'blocked'])->default('pending');
            $table->timestamps();
            $table->unique(['requester_id', 'receiver_id'], 'unique_friendship');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};
