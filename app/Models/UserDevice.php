<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $device_id
 * @property string|null $device_type
 * @property string|null $platform
 * @property string|null $platform_version
 * @property string|null $browser
 * @property string|null $browser_version
 * @property string|null $device_name
 * @property bool $is_robot
 * @property string $ip_address
 * @property string|null $country_code
 * @property string|null $city
 * @property string|null $region
 * @property string|null $postal_code
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $timezone
 * @property string|null $isp
 * @property string|null $connection_type
 * @property string|null $cf_ray
 * @property string|null $cf_connecting_ip
 * @property bool $cf_is_tor
 * @property string|null $cf_threat_score
 * @property string $user_agent
 * @property string|null $accept_language
 * @property \Illuminate\Support\Carbon $last_seen_at
 * @property bool $is_trusted
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice desktop()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice mobile()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice recentlyActive($days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice trusted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereAcceptLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereBrowser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereBrowserVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCfConnectingIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCfIsTor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCfRay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCfThreatScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereConnectionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereIsRobot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereIsTrusted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereIsp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice wherePlatformVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUserId($value)
 * @mixin \Eloquent
 */
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
