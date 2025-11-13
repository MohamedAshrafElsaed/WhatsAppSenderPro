<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $iso_code
 * @property string $iso3_code
 * @property string $phone_code
 * @property string $name_en
 * @property string $name_ar
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read mixed $name
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @property-read bool|null $users_exists
 * @method static Builder<static>|Country active()
 * @method static Builder<static>|Country newModelQuery()
 * @method static Builder<static>|Country newQuery()
 * @method static Builder<static>|Country query()
 * @method static Builder<static>|Country whereCreatedAt($value)
 * @method static Builder<static>|Country whereId($value)
 * @method static Builder<static>|Country whereIsActive($value)
 * @method static Builder<static>|Country whereIso3Code($value)
 * @method static Builder<static>|Country whereIsoCode($value)
 * @method static Builder<static>|Country whereNameAr($value)
 * @method static Builder<static>|Country whereNameEn($value)
 * @method static Builder<static>|Country wherePhoneCode($value)
 * @method static Builder<static>|Country whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Country extends Model
{
    protected $fillable = [
        'iso_code',
        'iso3_code',
        'phone_code',
        'name_en',
        'name_ar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
