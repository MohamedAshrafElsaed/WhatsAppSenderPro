<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 * @property Carbon $last_seen_at
 * @property bool $is_trusted
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @method static Builder<static>|UserDevice active()
 * @method static Builder<static>|UserDevice desktop()
 * @method static Builder<static>|UserDevice mobile()
 * @method static Builder<static>|UserDevice newModelQuery()
 * @method static Builder<static>|UserDevice newQuery()
 * @method static Builder<static>|UserDevice query()
 * @method static Builder<static>|UserDevice recentlyActive($days = 30)
 * @method static Builder<static>|UserDevice trusted()
 * @method static Builder<static>|UserDevice whereAcceptLanguage($value)
 * @method static Builder<static>|UserDevice whereBrowser($value)
 * @method static Builder<static>|UserDevice whereBrowserVersion($value)
 * @method static Builder<static>|UserDevice whereCfConnectingIp($value)
 * @method static Builder<static>|UserDevice whereCfIsTor($value)
 * @method static Builder<static>|UserDevice whereCfRay($value)
 * @method static Builder<static>|UserDevice whereCfThreatScore($value)
 * @method static Builder<static>|UserDevice whereCity($value)
 * @method static Builder<static>|UserDevice whereConnectionType($value)
 * @method static Builder<static>|UserDevice whereCountryCode($value)
 * @method static Builder<static>|UserDevice whereCreatedAt($value)
 * @method static Builder<static>|UserDevice whereDeviceId($value)
 * @method static Builder<static>|UserDevice whereDeviceName($value)
 * @method static Builder<static>|UserDevice whereDeviceType($value)
 * @method static Builder<static>|UserDevice whereId($value)
 * @method static Builder<static>|UserDevice whereIpAddress($value)
 * @method static Builder<static>|UserDevice whereIsActive($value)
 * @method static Builder<static>|UserDevice whereIsRobot($value)
 * @method static Builder<static>|UserDevice whereIsTrusted($value)
 * @method static Builder<static>|UserDevice whereIsp($value)
 * @method static Builder<static>|UserDevice whereLastSeenAt($value)
 * @method static Builder<static>|UserDevice whereLatitude($value)
 * @method static Builder<static>|UserDevice whereLongitude($value)
 * @method static Builder<static>|UserDevice wherePlatform($value)
 * @method static Builder<static>|UserDevice wherePlatformVersion($value)
 * @method static Builder<static>|UserDevice wherePostalCode($value)
 * @method static Builder<static>|UserDevice whereRegion($value)
 * @method static Builder<static>|UserDevice whereTimezone($value)
 * @method static Builder<static>|UserDevice whereUpdatedAt($value)
 * @method static Builder<static>|UserDevice whereUserAgent($value)
 * @method static Builder<static>|UserDevice whereUserId($value)
 * @mixin Eloquent
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
