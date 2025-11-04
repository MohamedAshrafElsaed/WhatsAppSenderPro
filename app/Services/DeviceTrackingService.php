<?php

namespace App\Services;

use App\Models\UserDevice;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceTrackingService
{
    protected Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    /**
     * Track or update user device information
     */
    public function track(Request $request, int $userId): UserDevice
    {
        $this->agent->setUserAgent($request->userAgent());

        // Generate device ID based on user agent and some stable factors
        $deviceId = $this->generateDeviceId($request);

        // Get or create device record
        $device = UserDevice::firstOrNew([
            'user_id' => $userId,
            'device_id' => $deviceId
        ]);

        // Update device information
        $device->fill([
            // Device info from agent
            'device_type' => $this->getDeviceType(),
            'platform' => $this->agent->platform(),
            'platform_version' => $this->agent->version($this->agent->platform()),
            'browser' => $this->agent->browser(),
            'browser_version' => $this->agent->version($this->agent->browser()),
            'device_name' => $this->agent->device(),
            'is_robot' => $this->agent->isRobot(),

            // Location from Cloudflare headers
            'ip_address' => $this->getIpAddress($request),
            'country_code' => $request->header('CF-IPCountry'),
            'city' => $request->header('CF-City'),
            'region' => $request->header('CF-Region'),
            'postal_code' => $request->header('CF-PostalCode'),
            'latitude' => $request->header('CF-Latitude'),
            'longitude' => $request->header('CF-Longitude'),
            'timezone' => $request->header('CF-Timezone'),

            // Cloudflare specific
            'cf_ray' => $request->header('CF-Ray'),
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
            'cf_is_tor' => $request->header('CF-IsTor') === 'true',
            'cf_threat_score' => $request->header('CF-ThreatScore'),

            // Other info
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
            'last_seen_at' => now(),
        ]);

        $device->save();

        return $device;
    }

    /**
     * Generate a unique device ID
     */
    protected function generateDeviceId(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept-Language'),
            $this->agent->platform(),
            $this->agent->browser(),
            $this->getDeviceType()
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Get the real IP address
     */
    protected function getIpAddress(Request $request): string
    {
        // Cloudflare real IP
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        // Standard forwarded IP
        if ($request->header('X-Forwarded-For')) {
            return explode(',', $request->header('X-Forwarded-For'))[0];
        }

        return $request->ip();
    }

    /**
     * Get device type
     */
    protected function getDeviceType(): string
    {
        if ($this->agent->isMobile()) {
            return 'mobile';
        }

        if ($this->agent->isTablet()) {
            return 'tablet';
        }

        if ($this->agent->isDesktop()) {
            return 'desktop';
        }

        return 'unknown';
    }

    /**
     * Get user's active devices
     */
    public function getUserDevices(int $userId, int $daysActive = 30): \Illuminate\Database\Eloquent\Collection
    {
        return UserDevice::where('user_id', $userId)
            ->where('last_seen_at', '>=', now()->subDays($daysActive))
            ->where('is_active', true)
            ->orderBy('last_seen_at', 'desc')
            ->get();
    }

    /**
     * Mark suspicious devices
     */
    public function markSuspiciousDevice(int $deviceId, bool $isSuspicious = true): void
    {
        UserDevice::where('id', $deviceId)->update([
            'is_trusted' => !$isSuspicious
        ]);
    }
}
