<?php

namespace App\Models;


use App\Services\JWTService;
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

    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function industry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function getFullNameAttribute(): string
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

    /**
     * Generate JWT token for WhatsApp API
     */
    public function generateJWT(): string
    {
        return app(JWTService::class)->generateToken($this);
    }

    /**
     * Get JWT token expiration time
     */
    public function getJWTExpiration(): \Carbon\Carbon
    {
        return app(JWTService::class)->getTokenExpiration();
    }
}
