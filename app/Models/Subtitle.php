<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subtitle model representing video subtitles/captions.
 *
 * @property int $id
 * @property int $video_id
 * @property string $language_code
 * @property string $language_name
 * @property string $format
 * @property string $storage_path
 * @property int $file_size_bytes
 * @property bool $is_auto_generated
 * @property bool $is_verified
 * @property string $status
 * @property int|null $line_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Subtitle extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'video_id',
        'language_code',
        'language_name',
        'format',
        'storage_path',
        'file_size_bytes',
        'is_auto_generated',
        'is_verified',
        'status',
        'line_count',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'file_size_bytes' => 'integer',
        'is_auto_generated' => 'boolean',
        'is_verified' => 'boolean',
        'line_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the video that owns this subtitle.
     *
     * @return BelongsTo<Video>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
