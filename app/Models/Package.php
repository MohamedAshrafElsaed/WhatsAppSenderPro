<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property string $slug
 * @property numeric $price
 * @property string $billing_cycle
 * @property array<array-key, mixed> $features
 * @property array<array-key, mixed> $limits
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_popular
 * @property bool $is_best_value
 * @property string|null $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read mixed $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read bool|null $transactions_exists
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserSubscription> $userSubscriptions
 * @property-read int|null $user_subscriptions_count
 * @property-read bool|null $user_subscriptions_exists
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package bestValue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package popular()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package sorted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereBillingCycle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereIsBestValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereIsPopular($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereLimits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereNameAr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereNameEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package withoutTrashed()
 * @mixin \Eloquent
 */
class Package extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'price',
        'billing_cycle',
        'features',
        'limits',
        'sort_order',
        'is_active',
        'is_popular',
        'is_best_value',
        'color',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'is_best_value' => 'boolean',
    ];

    // Relationships
    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Accessors
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeBestValue($query)
    {
        return $query->where('is_best_value', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // Methods
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public function getFeature(string $key)
    {
        return $this->features[$key] ?? null;
    }

    public function getLimit(string $key)
    {
        return $this->limits[$key] ?? null;
    }

    public function hasFeature(string $key): bool
    {
        return isset($this->features[$key]) && $this->features[$key];
    }
}
