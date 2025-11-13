<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name_en
 * @property string $name_ar
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read mixed $name
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @property-read bool|null $users_exists
 * @method static Builder<static>|Industry active()
 * @method static Builder<static>|Industry newModelQuery()
 * @method static Builder<static>|Industry newQuery()
 * @method static Builder<static>|Industry query()
 * @method static Builder<static>|Industry sorted()
 * @method static Builder<static>|Industry whereCreatedAt($value)
 * @method static Builder<static>|Industry whereId($value)
 * @method static Builder<static>|Industry whereIsActive($value)
 * @method static Builder<static>|Industry whereNameAr($value)
 * @method static Builder<static>|Industry whereNameEn($value)
 * @method static Builder<static>|Industry whereSlug($value)
 * @method static Builder<static>|Industry whereSortOrder($value)
 * @method static Builder<static>|Industry whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Industry extends Model
{
    protected $fillable = [
        'slug',
        'name_en',
        'name_ar',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
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
}
