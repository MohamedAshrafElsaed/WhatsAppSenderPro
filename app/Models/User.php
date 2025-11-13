<?php

namespace App\Models;

use App\Services\JWTService;
use Database\Factories\UserFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $mobile_number
 * @property int $country_id
 * @property int|null $industry_id
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $mobile_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string $locale
 * @property array<array-key, mixed>|null $onboarding_data
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, ContactImport> $contactImports
 * @property-read int|null $contact_imports_count
 * @property-read bool|null $contact_imports_exists
 * @property-read Collection<int, ContactTag> $contactTags
 * @property-read int|null $contact_tags_count
 * @property-read bool|null $contact_tags_exists
 * @property-read Collection<int, Contact> $contacts
 * @property-read int|null $contacts_count
 * @property-read bool|null $contacts_exists
 * @property-read Country $country
 * @property-read Collection<int, UserDevice> $devices
 * @property-read int|null $devices_count
 * @property-read bool|null $devices_exists
 * @property-read string $formatted_mobile
 * @property-read string $full_name
 * @property-read string $subscription_status
 * @property-read Industry|null $industry
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read bool|null $notifications_exists
 * @property-read UserSubscription|null $subscription
 * @property-read Collection<int, UserSubscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read bool|null $subscriptions_exists
 * @property-read Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read bool|null $transactions_exists
 * @property-read UserUsage|null $usage
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User fromCountry($countryCode)
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User onlyTrashed()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User verified()
 * @method static Builder<static>|User whereCountryId($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereDeletedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereFirstName($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereIndustryId($value)
 * @method static Builder<static>|User whereLastName($value)
 * @method static Builder<static>|User whereLocale($value)
 * @method static Builder<static>|User whereMobileNumber($value)
 * @method static Builder<static>|User whereMobileVerifiedAt($value)
 * @method static Builder<static>|User whereOnboardingData($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static Builder<static>|User whereTwoFactorSecret($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|User withoutTrashed()
 * @mixin Eloquent
 */
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
        'onboarding_data',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'onboarding_data' => 'array',
    ];

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

    public function subscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)->latestOfMany();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function usage(): HasOne
    {
        return $this->hasOne(UserUsage::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

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

    public function activeSubscription(): ?UserSubscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->where('ends_at', '>', now())
            ->latest()
            ->first();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeVerified($query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

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

    /**
     * Generate JWT token for WhatsApp API authentication
     */
    public function generateJWT(): string
    {
        $jwtService = new JWTService();
        return $jwtService->generateToken($this);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function contactTags(): HasMany
    {
        return $this->hasMany(ContactTag::class);
    }

    public function contactImports(): HasMany
    {
        return $this->hasMany(ContactImport::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
