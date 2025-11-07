<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->ends_at->isPast();
    }

    public function daysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->ends_at, false);
    }

    public function canUseFeature(string $feature): bool
    {
        if (!$this->isActive() && !$this->isTrial()) {
            return false;
        }

        return $this->package->hasFeature($feature);
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
