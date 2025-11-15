<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subscription model tracking user subscriptions to plans.
 *
 * @property int $id
 * @property int $user_id
 * @property int $plan_id
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $ends_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property string $status
 * @property string|null $payment_method
 * @property string|null $stripe_subscription_id
 * @property float $price_paid
 * @property bool $auto_renew
 * @property string $billing_cycle
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'started_at',
        'ends_at',
        'cancelled_at',
        'status',
        'payment_method',
        'stripe_subscription_id',
        'price_paid',
        'auto_renew',
        'billing_cycle',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'price_paid' => 'float',
        'auto_renew' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this subscription.
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan for this subscription.
     *
     * @return BelongsTo<Plan>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if subscription is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Check if subscription can be renewed.
     *
     * @return bool
     */
    public function canRenew(): bool
    {
        return $this->auto_renew && $this->status !== 'cancelled';
    }
}
