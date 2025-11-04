<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    // Relationships
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFormattedMobileAttribute()
    {
        if ($this->country) {
            return "+{$this->country->phone_code} {$this->mobile_number}";
        }
        return $this->mobile_number;
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeFromCountry($query, $countryCode)
    {
        return $query->whereHas('country', function ($q) use ($countryCode) {
            $q->where('iso_code', $countryCode);
        });
    }
}
