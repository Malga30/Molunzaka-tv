<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_renditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_file_id')->constrained('video_files')->onDelete('cascade');
            $table->string('name'); // 480p, 720p, 1080p, 4K
            $table->integer('height'); // 480, 720, 1080, 2160
            $table->integer('width');
            $table->integer('bitrate_kbps'); // 1000, 2500, 5000, etc.
            $table->string('codec_video')->default('h264');
            $table->string('codec_audio')->default('aac');
            $table->string('format')->default('mp4'); // mp4, webm, hls, dash
            $table->string('storage_path');
            $table->bigInteger('file_size_bytes')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->float('progress_percent')->default(0);
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamps();
            
            $table->index('video_file_id');
            $table->index('status');
            $table->unique(['video_file_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_renditions');
    }
};
