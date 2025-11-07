<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Display subscription upgrade page with available packages
     */
    public function upgrade(Request $request): Response
    {
        $user = $request->user();
        $currentSubscription = $user->activeSubscription();

        $packages = Package::active()
            ->sorted()
            ->get()
            ->map(function ($package) use ($currentSubscription) {
                $isCurrent = $currentSubscription &&
                    $currentSubscription->package_id === $package->id;

                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'name_en' => $package->name_en,
                    'name_ar' => $package->name_ar,
                    'slug' => $package->slug,
                    'price' => $package->price,
                    'formatted_price' => number_format($package->price, 0) . ' ' .
                        (app()->getLocale() === 'ar' ? 'جنيه/شهر' : 'EGP/month'),
                    'features' => $package->features,
                    'limits' => $package->limits,
                    'is_popular' => $package->is_popular,
                    'is_best_value' => $package->is_best_value,
                    'is_current' => $isCurrent,
                    'color' => $package->color,
                ];
            });

        $subscriptionSummary = $this->subscriptionService->getSubscriptionSummary($user);

        return Inertia::render('subscription/Upgrade', [
            'packages' => $packages,
            'currentSubscription' => $subscriptionSummary,
        ]);
    }

    /**
     * Display subscription management page
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $summary = $this->subscriptionService->getSubscriptionSummary($user);

        return Inertia::render('subscription/Index', [
            'subscription' => $summary,
        ]);
    }

    /**
     * Cancel active subscription
     */
    public function cancel(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return back()->with('error', trans('subscription.no_active_subscription'));
        }

        $this->subscriptionService->cancelSubscription($subscription);

        return redirect()->route('subscription.index')
            ->with('success', trans('subscription.cancelled_successfully'));
    }
}
