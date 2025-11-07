<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return redirect()->route('subscription.upgrade')
                ->with('error', trans('subscription.no_active_subscription'));
        }

        if ($subscription->isExpired()) {
            return redirect()->route('subscription.upgrade')
                ->with('error', trans('subscription.subscription_expired'));
        }

        return $next($request);
    }
}
