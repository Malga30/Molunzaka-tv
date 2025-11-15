<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subtitles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->string('language_code'); // en, es, fr, de, ja, etc.
            $table->string('language_name'); // English, Spanish, French
            $table->string('format')->default('vtt'); // vtt, srt, ass
            $table->string('storage_path');
            $table->bigInteger('file_size_bytes');
            $table->boolean('is_auto_generated')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('status')->default('completed'); // pending, processing, completed, failed
            $table->integer('line_count')->nullable();
            $table->timestamps();
            
            $table->index('video_id');
            $table->index('language_code');
            $table->unique(['video_id', 'language_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subtitles');
    }
};
