<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile_number',
        'country_id',
        'industry_id',
        'password',
        'locale',
        'email_verified_at',
        'mobile_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
    ];

    // Existing Relationships
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function subscription(): HasMany
    {
        return $this->hasOne(UserSubscription::class)->latestOfMany();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function usage(): HasMany
    {
        return $this->hasOne(UserUsage::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Existing Accessors

    public function getFormattedMobileAttribute(): string
    {
        if ($this->country) {
            return "+{$this->country->phone_code} {$this->mobile_number}";
        }
        return $this->mobile_number;
    }

    public function getSubscriptionStatusAttribute(): string
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return 'none';
        }

        return $subscription->status;
    }

    // NEW: Subscription Accessors

    public function activeSubscription(): ?UserSubscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->where('ends_at', '>', now())
            ->latest()
            ->first();
    }

    // Existing Scopes

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeVerified($query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    // NEW: Subscription Methods

    public function scopeFromCountry($query, $countryCode): Builder
    {
        return $query->whereHas('country', function ($q) use ($countryCode) {
            $q->where('iso_code', $countryCode);
        });
    }

    public function currentPackage(): ?Package
    {
        $subscription = $this->activeSubscription();
        return $subscription?->package;
    }

    public function isSubscribed(): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->isActive();
    }

    public function onTrial(): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->isTrial();
    }

    public function canAccessFeature(string $feature): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->canUseFeature($feature);
    }

    public function hasReachedLimit(string $limit): bool
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return true;
        }

        $usage = UserUsage::getCurrentPeriodUsage($this);

        $currentUsage = match ($limit) {
            'messages_per_month' => $usage->messages_sent,
            'contacts_validation_per_month' => $usage->contacts_validated,
            'connected_numbers' => $usage->connected_numbers_count,
            'message_templates' => $usage->templates_created,
            default => 0,
        };

        return $subscription->hasReachedLimit($limit, $currentUsage);
    }

    public function getRemainingQuota(string $limit): int|string
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return 0;
        }

        $usage = UserUsage::getCurrentPeriodUsage($this);

        $currentUsage = match ($limit) {
            'messages_per_month' => $usage->messages_sent,
            'contacts_validation_per_month' => $usage->contacts_validated,
            'connected_numbers' => $usage->connected_numbers_count,
            'message_templates' => $usage->templates_created,
            default => 0,
        };

        return $subscription->getRemainingLimit($limit, $currentUsage);
    }
}
