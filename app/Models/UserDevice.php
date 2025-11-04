<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_type',
        'platform',
        'platform_version',
        'browser',
        'browser_version',
        'device_name',
        'is_robot',
        'ip_address',
        'country_code',
        'city',
        'region',
        'postal_code',
        'latitude',
        'longitude',
        'timezone',
        'isp',
        'connection_type',
        'cf_ray',
        'cf_connecting_ip',
        'cf_is_tor',
        'cf_threat_score',
        'user_agent',
        'accept_language',
        'last_seen_at',
        'is_trusted',
        'is_active',
    ];

    protected $casts = [
        'is_robot' => 'boolean',
        'cf_is_tor' => 'boolean',
        'is_trusted' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_seen_at', '>=', now()->subDays($days));
    }

    public function scopeMobile($query)
    {
        return $query->where('device_type', 'mobile');
    }

    public function scopeDesktop($query)
    {
        return $query->where('device_type', 'desktop');
    }

    public function isMobile(): bool
    {
        return $this->device_type === 'mobile';
    }

    public function isDesktop(): bool
    {
        return $this->device_type === 'desktop';
    }

    public function isTablet(): bool
    {
        return $this->device_type === 'tablet';
    }

    public function isSuspicious(): bool
    {
        // Check for suspicious indicators
        return $this->cf_is_tor ||
            ($this->cf_threat_score && $this->cf_threat_score > 30) ||
            $this->is_robot;
    }

    public function getLocationString(): string
    {
        $parts = array_filter([
            $this->city,
            $this->region,
            $this->country_code
        ]);

        return implode(', ', $parts) ?: 'Unknown';
    }
}
