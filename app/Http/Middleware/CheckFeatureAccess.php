<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  The feature key to check
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->canAccessFeature($feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => trans('subscription.feature_not_available'),
                    'feature' => $feature,
                    'current_package' => $user->currentPackage()?->name,
                ], 403);
            }

            return redirect()->route('subscription.upgrade')
                ->with('error', trans('subscription.feature_not_available'));
        }

        return $next($request);
    }
}
