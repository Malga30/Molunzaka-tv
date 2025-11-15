<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan model representing subscription plans.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $price
 * @property int $max_profiles
 * @property int $max_concurrent_streams
 * @property bool $hd_support
 * @property bool $uhd_support
 * @property bool $offline_download
 * @property bool $ad_free
 * @property bool $is_active
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Plan extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'max_profiles',
        'max_concurrent_streams',
        'hd_support',
        'uhd_support',
        'offline_download',
        'ad_free',
        'is_active',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'hd_support' => 'boolean',
        'uhd_support' => 'boolean',
        'offline_download' => 'boolean',
        'ad_free' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get subscriptions for this plan.
     *
     * @return HasMany<Subscription>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
