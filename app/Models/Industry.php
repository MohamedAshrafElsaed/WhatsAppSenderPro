<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $name_en
 * @property string $name_ar
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property-read bool|null $users_exists
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry sorted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry whereNameAr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry whereNameEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Industry whereUpdatedAt($value)
 * @mixin \Eloquent
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
