<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->string('filename');
            $table->string('storage_path'); // S3 or local path
            $table->string('mime_type');
            $table->bigInteger('file_size_bytes');
            $table->integer('duration_seconds');
            $table->integer('width');
            $table->integer('height');
            $table->float('fps')->default(29.97);
            $table->string('codec_video')->default('h264'); // h264, h265, vp9
            $table->string('codec_audio')->default('aac'); // aac, mp3, opus
            $table->integer('bitrate_kbps')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->float('progress_percent')->default(0); // For processing tracking
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamps();
            
            $table->index('video_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_files');
    }
};
