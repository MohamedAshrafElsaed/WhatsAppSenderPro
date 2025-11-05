<?php

namespace App\Listeners;

use App\Services\DeviceTrackingService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;

readonly class HandleSuccessfulAuthentication
{
    public function __construct(
        private DeviceTrackingService $deviceTrackingService
    ) {}

    /**
     * Handle login event
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;
        $request = request();

        // Update user locale
        $user->update(['locale' => app()->getLocale()]);

        // Track device
        $this->deviceTrackingService->track($request, $user->id);

        // Generate and store JWT token
        $jwtToken = $user->generateJWT();
        session(['jwt_token' => $jwtToken]);
    }

    /**
     * Handle registration event
     */
    public function handleRegistration(Registered $event): void
    {
        $user = $event->user;
        $request = request();

        // Track device
        $this->deviceTrackingService->track($request, $user->id);

        // Generate and store JWT token
        $jwtToken = $user->generateJWT();
        session(['jwt_token' => $jwtToken]);
    }

    /**
     * Register the listeners for the subscriber
     */
    public function subscribe($events): void
    {
        $events->listen(
            Login::class,
            [HandleSuccessfulAuthentication::class, 'handleLogin']
        );

        $events->listen(
            Registered::class,
            [HandleSuccessfulAuthentication::class, 'handleRegistration']
        );
    }
}
