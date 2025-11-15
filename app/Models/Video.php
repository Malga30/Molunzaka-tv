<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Video model representing video content.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string $slug
 * @property string|null $thumbnail_url
 * @property string|null $poster_url
 * @property string $content_type
 * @property string|null $rating
 * @property int|null $duration_seconds
 * @property int $views_count
 * @property float|null $rating_score
 * @property int $rating_count
 * @property bool $is_published
 * @property bool $is_featured
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon|null $release_date
 * @property array|null $genres
 * @property array|null $cast
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Video extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'slug',
        'thumbnail_url',
        'poster_url',
        'content_type',
        'rating',
        'duration_seconds',
        'views_count',
        'rating_score',
        'rating_count',
        'is_published',
        'is_featured',
        'published_at',
        'release_date',
        'genres',
        'cast',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'duration_seconds' => 'integer',
        'views_count' => 'integer',
        'rating_score' => 'float',
        'rating_count' => 'integer',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'release_date' => 'date',
        'genres' => 'array',
        'cast' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that uploaded this video.
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get video files for this video.
     *
     * @return HasMany<VideoFile>
     */
    public function videoFiles(): HasMany
    {
        return $this->hasMany(VideoFile::class);
    }

    /**
     * Get subtitles for this video.
     *
     * @return HasMany<Subtitle>
     */
    public function subtitles(): HasMany
    {
        return $this->hasMany(Subtitle::class);
    }

    /**
     * Get watch history for this video.
     *
     * @return HasMany<WatchHistory>
     */
    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    /**
     * Increment view count.
     *
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Add a rating.
     *
     * @param float $score
     * @return void
     */
    public function addRating(float $score): void
    {
        $oldTotal = $this->rating_score * $this->rating_count;
        $this->rating_count++;
        $this->rating_score = ($oldTotal + $score) / $this->rating_count;
        $this->save();
    }
}
