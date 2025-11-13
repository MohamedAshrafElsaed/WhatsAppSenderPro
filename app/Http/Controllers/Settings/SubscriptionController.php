<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\UserUsage;
use App\Services\PaymobService;
use App\Services\SubscriptionService;
use App\Services\UsageTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService  $subscriptionService,
        private readonly UsageTrackingService $usageService,
        private readonly PaymobService        $paymobService
    ) {}

    /**
     * Show payment page
     */
    public function payment(Request $request, string $packageSlug): Response
    {
        $package = Package::where('slug', $packageSlug)->firstOrFail();

        // Get user's current subscription for comparison
        $user = $request->user();
        $currentSubscription = $user->activeSubscription();

        // Get active payment methods from database
        $paymentMethods = PaymentMethod::active()
            ->sorted()
            ->get()
            ->map(fn($method) => [
                'id' => $method->id,
                'value' => $method->slug,
                'label' => $method->name,
                'label_en' => $method->name_en,
                'label_ar' => $method->name_ar,
                'description' => $method->config['description'] ?? 'Pay with ' . $method->name_en,
                'icon' => $method->icon ?? 'credit-card',
                'gateway' => $method->gateway,
            ]);

        // If no payment methods are active, create default ones
        if ($paymentMethods->isEmpty()) {
            $paymentMethods = collect([
                [
                    'id' => 1,
                    'value' => 'paymob',
                    'label' => 'Paymob',
                    'label_en' => 'Paymob',
                    'label_ar' => 'باي موب',
                    'description' => 'Pay with credit card, debit card, or mobile wallet',
                    'icon' => 'credit-card',
                    'gateway' => 'paymob',
                ],
                [
                    'id' => 2,
                    'value' => 'vodafone_cash',
                    'label' => 'Vodafone Cash',
                    'label_en' => 'Vodafone Cash',
                    'label_ar' => 'فودافون كاش',
                    'description' => 'Pay using your Vodafone Cash wallet',
                    'icon' => 'smartphone',
                    'gateway' => 'vodafone',
                ],
                [
                    'id' => 3,
                    'value' => 'fawry',
                    'label' => 'Fawry',
                    'label_en' => 'Fawry',
                    'label_ar' => 'فوري',
                    'description' => 'Pay at any Fawry outlet',
                    'icon' => 'wallet',
                    'gateway' => 'fawry',
                ],
            ]);
        }

        // Calculate fees
        $baseAmount = $package->price;
        $upgradePrice = null;

        // Calculate upgrade price (if upgrading from a paid plan)
        if ($currentSubscription && !$currentSubscription->isTrial()) {
            $currentPackagePrice = $currentSubscription->package->price;
            if ($package->price > $currentPackagePrice) {
                // Pro-rated upgrade amount
                $upgradePrice = $package->price - $currentPackagePrice;
                $baseAmount = $upgradePrice;
            }
        }

        // Calculate fees using PaymobService or manually
        $fees = $this->calculatePaymentFees($baseAmount);

        return Inertia::render('settings/SubscriptionPayment', [
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'name_en' => $package->name_en,
                'name_ar' => $package->name_ar,
                'slug' => $package->slug,
                'price' => $package->price,
                'features' => $package->features,
                'limits' => $package->limits,
            ],
            'currentSubscription' => $currentSubscription ? [
                'package_name' => $currentSubscription->package->name,
                'price' => $currentSubscription->package->price,
                'is_trial' => $currentSubscription->isTrial(),
            ] : null,
            'paymentMethods' => $paymentMethods,
            'fees' => $fees,
            'upgradePrice' => $upgradePrice,
        ]);
    }

    /**
     * Calculate payment fees
     */
    private function calculatePaymentFees(float $baseAmount): array
    {
        // Try using PaymobService if available
        if (method_exists($this->paymobService, 'calculateFees')) {
            return $this->paymobService->calculateFees($baseAmount);
        }

        // Manual calculation (2% processing fee)
        $feePercentage = 2;
        $feeAmount = round($baseAmount * ($feePercentage / 100), 2);
        $totalAmount = $baseAmount + $feeAmount;

        return [
            'base_amount' => $baseAmount,
            'fee_amount' => $feeAmount,
            'fee_percentage' => $feePercentage,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request): Response|RedirectResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'payment_method_id' => 'required|integer',
        ]);

        $user = $request->user();
        $package = Package::findOrFail($validated['package_id']);

        // Get payment method or use default
        $paymentMethod = PaymentMethod::find($validated['payment_method_id']);

        // Determine payment type based on method
        $paymentType = 'card'; // Default

        if ($paymentMethod) {
            $paymentType = match($paymentMethod->slug) {
                'card', 'credit_card', 'debit_card', 'paymob_card' => 'card',
                'vodafone_cash', 'etisalat_cash', 'orange_cash', 'we_pay', 'paymob_wallet' => 'wallet',
                'instapay' => 'instapay',
                default => 'card'
            };
        }

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_method_id' => $paymentMethod?->id,
            'transaction_id' => 'TRX' . time() . rand(1000, 9999),
            'amount' => $package->price,
            'currency' => 'EGP',
            'status' => 'pending',
            'payment_gateway' => $paymentMethod?->gateway ?? 'paymob',
        ]);

        // Check if PaymobService is properly configured
        if (method_exists($this->paymobService, 'initiatePayment')) {
            try {
                // Initiate Paymob payment using new Intention API
                $paymentData = $this->paymobService->initiatePayment($user, $package, $paymentType);

                if (!$paymentData['success']) {
                    $transaction->update(['status' => 'failed']);
                    return back()->with('error', $paymentData['error'] ?? __('subscription.payment_failed'));
                }

                // Store intention ID in transaction
                $transaction->update([
                    'notes' => json_encode([
                        'intention_id' => $paymentData['intention_id'],
                        'client_secret' => $paymentData['client_secret'],
                    ]),
                ]);

                // Return payment page with checkout URL
                return Inertia::render('settings/PaymobPayment', [
                    'paymentData' => [
                        'checkout_url' => $paymentData['checkout_url'],
                        'public_key' => $paymentData['public_key'],
                        'client_secret' => $paymentData['client_secret'],
                        'intention_id' => $paymentData['intention_id'],
                        'base_amount' => $paymentData['base_amount'],
                        'fee_amount' => $paymentData['fee_amount'],
                        'total_amount' => $paymentData['total_amount'],
                    ],
                    'transaction' => [
                        'id' => $transaction->id,
                        'transaction_id' => $transaction->transaction_id,
                    ],
                    'package' => [
                        'name' => $package->name,
                        'price' => $package->price,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Payment processing error: ' . $e->getMessage());
                $transaction->update(['status' => 'failed']);
                return back()->with('error', __('subscription.payment_initialization_failed'));
            }
        } else {
            // Fallback: Mark as completed for testing (remove in production)
            $transaction->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Upgrade subscription
            $this->subscriptionService->upgradeSubscription($user, $package, $transaction);

            return redirect()->route('dashboard.settings.subscription.index')
                ->with('success', __('subscription.payment_successful'));
        }
    }

    /**
     * Show subscription overview page
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->load(['subscription.package', 'transactions.package']);

        $subscription = $user->activeSubscription();
        $usage = UserUsage::getCurrentPeriodUsage($user);

        // Get subscription data
        $subscriptionData = null;
        if ($subscription) {
            $subscriptionData = [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'package' => [
                    'id' => $subscription->package->id,
                    'name' => $subscription->package->name,
                    'name_en' => $subscription->package->name_en,
                    'name_ar' => $subscription->package->name_ar,
                    'slug' => $subscription->package->slug,
                    'price' => $subscription->package->price,
                    'billing_cycle' => $subscription->package->billing_cycle,
                    'features' => $subscription->package->features,
                    'limits' => $subscription->package->limits,
                    'color' => $subscription->package->color,
                ],
                'starts_at' => $subscription->starts_at->toISOString(),
                'ends_at' => $subscription->ends_at->toISOString(),
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'days_remaining' => $subscription->daysRemaining(),
                'is_trial' => $subscription->isTrial(),
                'is_expired' => $subscription->isExpired(),
                'auto_renew' => $subscription->auto_renew,
            ];
        }

        // Get usage statistics
        $usageStats = $this->usageService->getCurrentUsageStats($user);

        // Get available packages for upgrade
        $packages = Package::active()
            ->sorted()
            ->get()
            ->map(fn($package) => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'price' => $package->price,
                'billing_cycle' => $package->billing_cycle,
                'features' => $package->features,
                'limits' => $package->limits,
                'is_popular' => $package->is_popular,
                'is_best_value' => $package->is_best_value,
                'color' => $package->color ?? '#25D366',
            ]);

        // Get recent transactions
        $transactions = $user->transactions()
            ->with(['package'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($transaction) => [
                'id' => $transaction->id,
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'package_name' => $transaction->package->name ?? 'N/A',
                'payment_gateway' => $transaction->payment_gateway ?? 'N/A',
                'paid_at' => $transaction->paid_at?->toISOString(),
                'created_at' => $transaction->created_at->toISOString(),
            ]);

        return Inertia::render('settings/Subscription', [
            'subscription' => $subscriptionData,
            'usage' => $usageStats,
            'packages' => $packages,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Show upgrade page with package selection
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
                    'formatted_price' => number_format($package->price, 0),
                    'features' => $package->features,
                    'limits' => $package->limits,
                    'is_popular' => $package->is_popular,
                    'is_best_value' => $package->is_best_value,
                    'is_current' => $isCurrent,
                    'color' => $package->color ?? '#25D366',
                ];
            });

        $subscriptionSummary = $this->subscriptionService->getSubscriptionSummary($user);

        return Inertia::render('settings/SubscriptionUpgrade', [
            'packages' => $packages,
            'currentSubscription' => $subscriptionSummary,
        ]);
    }

    /**
     * Process package selection and redirect to payment
     */
    public function selectPackage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $package = Package::findOrFail($validated['package_id']);

        // Store selected package in session
        session(['selected_package' => $package->id]);

        // Redirect to payment page
        return redirect()->route('dashboard.settings.subscription.payment', $package->slug);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $request->user()->activeSubscription();

        if (!$subscription) {
            return back()->with('error', __('subscription.no_active_subscription'));
        }

        $subscription->update([
            'auto_renew' => false,
            'cancelled_at' => now(),
            'status' => 'cancelled',
        ]);

        return back()->with('success', __('subscription.cancelled_successfully'));
    }
}
