<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property string $slug
 * @property string $gateway
 * @property bool $is_active
 * @property array<array-key, mixed>|null $config
 * @property int $sort_order
 * @property string|null $icon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read mixed $name
 * @property-read Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read bool|null $transactions_exists
 * @method static Builder<static>|PaymentMethod active()
 * @method static Builder<static>|PaymentMethod newModelQuery()
 * @method static Builder<static>|PaymentMethod newQuery()
 * @method static Builder<static>|PaymentMethod onlyTrashed()
 * @method static Builder<static>|PaymentMethod query()
 * @method static Builder<static>|PaymentMethod sorted()
 * @method static Builder<static>|PaymentMethod whereConfig($value)
 * @method static Builder<static>|PaymentMethod whereCreatedAt($value)
 * @method static Builder<static>|PaymentMethod whereDeletedAt($value)
 * @method static Builder<static>|PaymentMethod whereGateway($value)
 * @method static Builder<static>|PaymentMethod whereIcon($value)
 * @method static Builder<static>|PaymentMethod whereId($value)
 * @method static Builder<static>|PaymentMethod whereIsActive($value)
 * @method static Builder<static>|PaymentMethod whereNameAr($value)
 * @method static Builder<static>|PaymentMethod whereNameEn($value)
 * @method static Builder<static>|PaymentMethod whereSlug($value)
 * @method static Builder<static>|PaymentMethod whereSortOrder($value)
 * @method static Builder<static>|PaymentMethod whereUpdatedAt($value)
 * @method static Builder<static>|PaymentMethod withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|PaymentMethod withoutTrashed()
 * @mixin Eloquent
 */
class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'gateway',
        'is_active',
        'config',
        'sort_order',
        'icon',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
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

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }

    // Methods
    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
