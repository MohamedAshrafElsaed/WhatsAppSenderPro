<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
