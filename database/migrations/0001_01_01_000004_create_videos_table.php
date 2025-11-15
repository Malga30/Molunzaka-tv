<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->text('thumbnail_url')->nullable();
            $table->text('poster_url')->nullable();
            $table->string('content_type')->default('video'); // video, series, movie, documentary
            $table->string('rating')->nullable(); // PG, PG-13, R, etc.
            $table->integer('duration_seconds')->nullable();
            $table->integer('views_count')->default(0);
            $table->decimal('rating_score', 3, 2)->nullable(); // 0.00 to 10.00
            $table->integer('rating_count')->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->date('release_date')->nullable();
            $table->json('genres')->nullable(); // ['Action', 'Drama']
            $table->json('cast')->nullable(); // Actor names
            $table->json('metadata')->nullable(); // Custom metadata
            $table->timestamps();
            
            $table->index('slug');
            $table->index('user_id');
            $table->index('is_published');
            $table->index('content_type');
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
