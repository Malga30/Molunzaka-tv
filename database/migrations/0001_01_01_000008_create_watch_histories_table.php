<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('profile_id')->constrained('profiles')->onDelete('cascade');
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->integer('watched_seconds')->default(0);
            $table->integer('total_seconds');
            $table->float('progress_percent')->default(0); // 0-100
            $table->string('quality_watched')->default('720p'); // 480p, 720p, 1080p
            $table->string('device_type')->nullable(); // web, mobile, tablet, tv
            $table->string('device_os')->nullable(); // Windows, iOS, Android
            $table->timestamp('started_at');
            $table->timestamp('last_watched_at');
            $table->timestamp('finished_at')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->json('metadata')->nullable(); // Additional tracking data
            $table->timestamps();
            
            $table->index(['user_id', 'video_id']);
            $table->index('last_watched_at');
            $table->unique(['user_id', 'profile_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_histories');
    }
};
