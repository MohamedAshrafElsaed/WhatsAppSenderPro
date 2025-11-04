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
            dispatch(function () use ($request) {
                $this->deviceService->track($request, $request->user()->id);
            })->afterResponse();
        }

        return $next($request);
    }
}
