<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WatchHistory model tracking user video viewing.
 *
 * @property int $id
 * @property int $user_id
 * @property int $profile_id
 * @property int $video_id
 * @property int $watched_seconds
 * @property int $total_seconds
 * @property float $progress_percent
 * @property string $quality_watched
 * @property string|null $device_type
 * @property string|null $device_os
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon $last_watched_at
 * @property \Carbon\Carbon|null $finished_at
 * @property bool $is_completed
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WatchHistory extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'profile_id',
        'video_id',
        'watched_seconds',
        'total_seconds',
        'progress_percent',
        'quality_watched',
        'device_type',
        'device_os',
        'started_at',
        'last_watched_at',
        'finished_at',
        'is_completed',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'watched_seconds' => 'integer',
        'total_seconds' => 'integer',
        'progress_percent' => 'float',
        'is_completed' => 'boolean',
        'started_at' => 'datetime',
        'last_watched_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that watched this video.
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the profile that watched this video.
     *
     * @return BelongsTo<Profile>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the video that was watched.
     *
     * @return BelongsTo<Video>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Calculate and update progress percentage.
     *
     * @return void
     */
    public function updateProgress(): void
    {
        $this->progress_percent = $this->total_seconds > 0
            ? ($this->watched_seconds / $this->total_seconds) * 100
            : 0;
        $this->is_completed = $this->progress_percent >= 90;
        $this->save();
    }
}
