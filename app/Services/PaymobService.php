<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobService
{
    private string $apiSecret;
    private string $publicKey;
    private array $integrationIds;
    private string $hmacSecret;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiSecret = config('services.paymob.api_secret');
        $this->publicKey = config('services.paymob.public_key');
        $this->integrationIds = config('services.paymob.integration_id', []);
        $this->hmacSecret = config('services.paymob.hmac_secret');
        $this->apiUrl = config('services.paymob.api_url', 'https://accept.paymob.com');
    }

    /**
     * Calculate payment fees
     */
    public function calculateFees(float $baseAmount): array
    {
        // Paymob processing fee: 2% (configurable)
        $feePercentage = config('services.paymob.fee_percentage', 2);
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
     * Initiate payment with Paymob using new Intention API
     */
    public function initiatePayment(User $user, Package $package, string $paymentType = 'card'): array
    {
        try {
            // Calculate fees
            $fees = $this->calculateFees($package->price);

            // Get the appropriate integration ID based on payment type
            $integrationId = $this->getIntegrationId($paymentType);

            if (!$integrationId) {
                return [
                    'success' => false,
                    'error' => 'Invalid payment type or integration not configured',
                ];
            }

            // Create payment intention
            $intention = $this->createIntention(
                $fees['total_amount'],
                $user,
                $package,
                $integrationId
            );

            if (!$intention['success']) {
                return [
                    'success' => false,
                    'error' => $intention['error'] ?? 'Failed to create payment intention',
                ];
            }

            // Generate unified checkout URL
            $checkoutUrl = $this->generateUnifiedCheckoutUrl($intention['client_secret']);

            return [
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'intention_id' => $intention['id'],
                'client_secret' => $intention['client_secret'],
                'public_key' => $this->publicKey,
                'payment_keys' => $intention['payment_keys'],
                'base_amount' => $fees['base_amount'],
                'fee_amount' => $fees['fee_amount'],
                'total_amount' => $fees['total_amount'],
            ];

        } catch (\Exception $e) {
            dd($e);
            Log::error('Paymob payment initiation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Payment initiation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get integration ID based on payment type
     */
    private function getIntegrationId(string $paymentType): ?int
    {
        $integrationId = $this->integrationIds[$paymentType] ?? null;

        if (!$integrationId) {
            // Try to get default card integration if specific type not found
            $integrationId = $this->integrationIds['card'] ?? null;
        }

        return $integrationId ? (int)$integrationId : null;
    }

    /**
     * Create payment intention using new API
     * @throws ConnectionException
     */
    private function createIntention(float $amount, User $user, Package $package, int $integrationId): array
    {
        // Convert amount to cents (Paymob requires amount in cents)
        $amountInCents = (int)($amount * 100);

        // Prepare billing data
        $billingData = [
            'first_name' => $user->name ?: 'N/A',
            'last_name' => '.',
            'email' => $user->email,
            'phone_number' => $this->formatPhoneNumber($user->phone),
            'apartment' => 'NA',
            'floor' => 'NA',
            'street' => 'NA',
            'building' => 'NA',
            'postal_code' => 'NA',
            'city' => 'Cairo',
            'country' => 'EGY',
            'state' => 'Cairo',
        ];

        // Prepare request data
        $requestData = [
            'amount' => $amountInCents,
            'currency' => 'EGP',
            'payment_methods' => [$integrationId, 'card'],
            'items' => [
                [
                    'name' => $package->name,
                    'amount' => $amountInCents,
                    'description' => "Subscription to {$package->name} plan",
                    'quantity' => 1,
                ]
            ],
            'billing_data' => $billingData,
            'customer' => [
                'first_name' => $user->name ?: 'N/A',
                'last_name' => '.',
                'email' => $user->email,
            ],
            'notification_url' => config('services.paymob.callback_url'),
            'redirection_url' => config('services.paymob.success_url'),
            'extras' => [
                'user_id' => $user->id,
                'package_id' => $package->id,
            ],
            'special_reference' => 'USR' . $user->id . '_PKG' . $package->id . '_' . time(),
        ];

        // Make API request to create intention
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $this->apiSecret,
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/v1/intention/", $requestData);

        Log::info('Paymob Intention API Response:', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'id' => $data['id'],
                'client_secret' => $data['client_secret'],
                'payment_keys' => $data['payment_keys'] ?? [],
                'special_reference' => $data['special_reference'] ?? '',
            ];
        }

        Log::error('Paymob intention creation failed:', [
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        return [
            'success' => false,
            'error' => 'Failed to create payment intention',
        ];
    }

    /**
     * Format phone number for Paymob
     */
    private function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return '+201000000000'; // Default Egyptian number format
        }

        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // Add Egyptian country code if not present
        if (!str_starts_with($phone, '20')) {
            $phone = '20' . $phone;
        }

        return '+' . $phone;
    }

    /**
     * Generate unified checkout URL
     */
    private function generateUnifiedCheckoutUrl(string $clientSecret): string
    {
        return "{$this->apiUrl}/unifiedcheckout/?publicKey={$this->publicKey}&clientSecret={$clientSecret}";
    }

    /**
     * Verify callback notification (webhook)
     */
    public function verifyCallback(array $data): bool
    {
        // For new API, check for the intention status
        if (isset($data['obj']['payment_key_claims'])) {
            // Verify using HMAC if provided
            if (isset($data['hmac'])) {
                return $this->verifyHmac($data);
            }
            return true; // Allow if no HMAC provided (configure in Paymob dashboard)
        }

        return false;
    }

    /**
     * Verify HMAC signature
     */
    private function verifyHmac(array $data): bool
    {
        if (!$this->hmacSecret) {
            Log::warning('HMAC secret not configured');
            return true; // Allow for testing, but log warning
        }

        // Build concatenated string according to Paymob documentation
        $obj = $data['obj'] ?? [];
        $concatenatedString =
            ($obj['amount_cents'] ?? '') .
            ($obj['created_at'] ?? '') .
            ($obj['currency'] ?? '') .
            ($obj['error_occured'] ?? 'false') .
            ($obj['has_parent_transaction'] ?? 'false') .
            ($obj['id'] ?? '') .
            ($obj['integration_id'] ?? '') .
            ($obj['is_3d_secure'] ?? 'false') .
            ($obj['is_auth'] ?? 'false') .
            ($obj['is_capture'] ?? 'false') .
            ($obj['is_refunded'] ?? 'false') .
            ($obj['is_standalone_payment'] ?? 'false') .
            ($obj['is_voided'] ?? 'false') .
            ($obj['order']['id'] ?? '') .
            ($obj['owner'] ?? '') .
            ($obj['pending'] ?? 'false') .
            ($obj['source_data']['pan'] ?? '') .
            ($obj['source_data']['sub_type'] ?? '') .
            ($obj['source_data']['type'] ?? '') .
            ($obj['success'] ?? 'false');

        $expectedHmac = hash_hmac('sha512', $concatenatedString, $this->hmacSecret);
        $receivedHmac = $data['hmac'] ?? '';

        return hash_equals($expectedHmac, $receivedHmac);
    }

    /**
     * Process successful payment from callback
     */
    public function processCallback(array $callbackData): array
    {
        try {
            // Extract transaction details from new API format
            $obj = $callbackData['obj'] ?? [];
            $paymentKeyClaims = $obj['payment_key_claims'] ?? [];
            $extras = $paymentKeyClaims['extras'] ?? [];

            $transactionId = $obj['id'] ?? null;
            $success = $obj['success'] ?? false;
            $isPending = $obj['pending'] ?? false;
            $amountCents = $obj['amount_cents'] ?? 0;
            $amount = $amountCents / 100;

            // Find transaction by special reference or extras
            $specialReference = $paymentKeyClaims['special_reference'] ?? null;
            $userId = $extras['user_id'] ?? null;
            $packageId = $extras['package_id'] ?? null;

            // Find the pending transaction
            $transaction = Transaction::where('user_id', $userId)
                ->where('package_id', $packageId)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$transaction) {
                Log::error('Transaction not found for callback:', [
                    'user_id' => $userId,
                    'package_id' => $packageId,
                ]);

                return [
                    'success' => false,
                    'error' => 'Transaction not found',
                ];
            }

            // Update transaction based on status
            if ($success && !$isPending) {
                $transaction->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'gateway_response' => $callbackData,
                    'notes' => json_encode([
                        'paymob_transaction_id' => $transactionId,
                        'special_reference' => $specialReference,
                    ]),
                ]);

                return [
                    'success' => true,
                    'transaction' => $transaction,
                    'status' => 'completed',
                ];
            } elseif ($isPending) {
                $transaction->update([
                    'status' => 'pending',
                    'gateway_response' => $callbackData,
                ]);

                return [
                    'success' => true,
                    'transaction' => $transaction,
                    'status' => 'pending',
                ];
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'gateway_response' => $callbackData,
                ]);

                return [
                    'success' => false,
                    'transaction' => $transaction,
                    'status' => 'failed',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error processing Paymob callback: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
