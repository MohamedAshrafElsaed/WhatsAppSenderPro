<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\PaymobService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymobCallbackController extends Controller
{
    public function __construct(
        private readonly PaymobService $paymobService,
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle Paymob callback (notification URL)
     */
    public function handle(Request $request)
    {
        Log::info('Paymob callback received', $request->all());

        try {
            // Verify the callback
            if (!$this->paymobService->verifyCallback($request->all())) {
                Log::error('Invalid callback signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Process the callback
            $result = $this->paymobService->processCallback($request->all());

            if ($result['success'] && $result['status'] === 'completed') {
                // Upgrade subscription for completed payments
                $transaction = $result['transaction'];
                $user = $transaction->user;
                $package = $transaction->package;

                $this->subscriptionService->upgradeSubscription($user, $package, $transaction);

                Log::info('Subscription upgraded successfully', [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'transaction_id' => $transaction->id,
                ]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Paymob callback processing failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle successful payment redirect (redirection URL)
     */
    public function success(Request $request)
    {
        Log::info('Paymob success redirect', $request->all());

        // Extract transaction details from query parameters
        $success = $request->get('success', 'false') === 'true';
        $transactionId = $request->get('id');

        if ($success) {
            return redirect()->route('dashboard.settings.subscription.index')
                ->with('success', __('subscription.payment_successful', 'Payment completed successfully! Your subscription has been upgraded.'));
        } else {
            return redirect()->route('dashboard.settings.subscription.index')
                ->with('error', __('subscription.payment_processing', 'Payment is being processed. Please check back in a few minutes.'));
        }
    }

    /**
     * Handle failed payment redirect
     */
    public function failed(Request $request)
    {
        Log::info('Paymob failed redirect', $request->all());

        return redirect()->route('dashboard.settings.subscription.index')
            ->with('error', __('subscription.payment_failed', 'Payment failed. Please try again or contact support if the issue persists.'));
    }
}
