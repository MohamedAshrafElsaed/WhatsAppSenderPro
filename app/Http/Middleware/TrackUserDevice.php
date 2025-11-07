<?php

namespace App\Http\Middleware;

use App\Services\DeviceTrackingService;
use Closure;
use Illuminate\Http\Request;

class TrackUserDevice
{
    protected DeviceTrackingService $deviceService;

    public function __construct(DeviceTrackingService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            // Track device synchronously - no need to queue this lightweight operation
            // The previous dispatch() with closure was causing serialization errors
            // because Request objects contain PDO connections that can't be serialized
            try {
                $this->deviceService->track($request, $request->user()->id);
            } catch (\Exception $e) {
                // Silently fail - device tracking shouldn't break the app
                // You can log this if needed: logger()->warning('Device tracking failed', ['error' => $e->getMessage()]);
            }
        }

        return $next($request);
    }
}
