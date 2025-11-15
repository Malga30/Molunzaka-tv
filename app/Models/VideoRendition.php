<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VideoRendition model representing encoded video quality levels.
 *
 * @property int $id
 * @property int $video_file_id
 * @property string $name
 * @property int $height
 * @property int $width
 * @property int $bitrate_kbps
 * @property string $codec_video
 * @property string $codec_audio
 * @property string $format
 * @property string $storage_path
 * @property int|null $file_size_bytes
 * @property string $status
 * @property string|null $error_message
 * @property float $progress_percent
 * @property \Carbon\Carbon|null $processing_started_at
 * @property \Carbon\Carbon|null $processing_completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VideoRendition extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'video_file_id',
        'name',
        'height',
        'width',
        'bitrate_kbps',
        'codec_video',
        'codec_audio',
        'format',
        'storage_path',
        'file_size_bytes',
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
        'height' => 'integer',
        'width' => 'integer',
        'bitrate_kbps' => 'integer',
        'file_size_bytes' => 'integer',
        'progress_percent' => 'float',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the video file that owns this rendition.
     *
     * @return BelongsTo<VideoFile>
     */
    public function videoFile(): BelongsTo
    {
        return $this->belongsTo(VideoFile::class);
    }

    /**
     * Check if rendition is processing.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if rendition is complete.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }
}
