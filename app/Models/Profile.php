<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profile model representing user profiles (multiple per user).
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $avatar_url
 * @property bool $is_primary
 * @property bool $is_active
 * @property array|null $preferences
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Profile extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'avatar_url',
        'is_primary',
        'is_active',
        'preferences',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'preferences' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this profile.
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get watch history for this profile.
     *
     * @return HasMany<WatchHistory>
     */
    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }
}
