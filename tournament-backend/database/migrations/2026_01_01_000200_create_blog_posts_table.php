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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users');
            $table->string('title', 300);
            $table->string('slug', 300)->unique();
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('category', 100)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
