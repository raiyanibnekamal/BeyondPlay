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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('commentable_type', 100);
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('parent_id')->nullable()->constrained('comments');
            $table->text('content');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->index(['commentable_type', 'commentable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
