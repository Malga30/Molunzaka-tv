<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * VideoFile model representing uploaded video files.
 *
 * @property int $id
 * @property int $video_id
 * @property string $filename
 * @property string $storage_path
 * @property string $mime_type
 * @property int $file_size_bytes
 * @property int $duration_seconds
 * @property int $width
 * @property int $height
 * @property float $fps
 * @property string $codec_video
 * @property string $codec_audio
 * @property int|null $bitrate_kbps
 * @property string $status
 * @property string|null $error_message
 * @property float $progress_percent
 * @property \Carbon\Carbon|null $processing_started_at
 * @property \Carbon\Carbon|null $processing_completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VideoFile extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'video_id',
        'filename',
        'storage_path',
        'mime_type',
        'file_size_bytes',
        'duration_seconds',
        'width',
        'height',
        'fps',
        'codec_video',
        'codec_audio',
        'bitrate_kbps',
        'status',
        'error_message',
        'progress_percent',
        'processing_started_at',
        'processing_completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'file_size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'fps' => 'float',
        'bitrate_kbps' => 'integer',
        'progress_percent' => 'float',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the video that owns this file.
     *
     * @return BelongsTo<Video>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get renditions for this video file.
     *
     * @return HasMany<VideoRendition>
     */
    public function renditions(): HasMany
    {
        return $this->hasMany(VideoRendition::class);
    }

    /**
     * Check if file is still processing.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if processing is complete.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if processing failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
