<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $package_id
 * @property string $status
 * @property Carbon|null $trial_ends_at
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $cancelled_at
 * @property bool $auto_renew
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Package $package
 * @property-read User $user
 * @method static Builder<static>|UserSubscription active()
 * @method static Builder<static>|UserSubscription expired()
 * @method static Builder<static>|UserSubscription newModelQuery()
 * @method static Builder<static>|UserSubscription newQuery()
 * @method static Builder<static>|UserSubscription onlyTrashed()
 * @method static Builder<static>|UserSubscription query()
 * @method static Builder<static>|UserSubscription trial()
 * @method static Builder<static>|UserSubscription whereAutoRenew($value)
 * @method static Builder<static>|UserSubscription whereCancelledAt($value)
 * @method static Builder<static>|UserSubscription whereCreatedAt($value)
 * @method static Builder<static>|UserSubscription whereDeletedAt($value)
 * @method static Builder<static>|UserSubscription whereEndsAt($value)
 * @method static Builder<static>|UserSubscription whereId($value)
 * @method static Builder<static>|UserSubscription wherePackageId($value)
 * @method static Builder<static>|UserSubscription whereStartsAt($value)
 * @method static Builder<static>|UserSubscription whereStatus($value)
 * @method static Builder<static>|UserSubscription whereTrialEndsAt($value)
 * @method static Builder<static>|UserSubscription whereUpdatedAt($value)
 * @method static Builder<static>|UserSubscription whereUserId($value)
 * @method static Builder<static>|UserSubscription withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|UserSubscription withoutTrashed()
 * @mixin Eloquent
 */
class UserSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'package_id',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'auto_renew',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '>', now());
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial')
            ->where('trial_ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['active', 'trial'])
                    ->where('ends_at', '<=', now());
            });
    }

    // Methods

    public function daysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->ends_at, false);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->ends_at->isPast();
    }

    public function canUseFeature(string $feature): bool
    {
        if (!$this->isActive() && !$this->isTrial()) {
            return false;
        }

        return $this->package->hasFeature($feature);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' &&
            $this->trial_ends_at &&
            $this->trial_ends_at->isFuture();
    }

    public function hasReachedLimit(string $limit, int $currentUsage): bool
    {
        $maxLimit = $this->package->getLimit($limit);

        if ($maxLimit === null || $maxLimit === 'unlimited') {
            return false;
        }

        return $currentUsage >= $maxLimit;
    }

    public function getRemainingLimit(string $limit, int $currentUsage): int|string
    {
        $maxLimit = $this->package->getLimit($limit);

        if ($maxLimit === null || $maxLimit === 'unlimited') {
            return 'unlimited';
        }

        return max(0, $maxLimit - $currentUsage);
    }
}
