<?php

namespace App\Http\Controllers;

use App\Services\UsageTrackingService;
use App\Services\WhatsAppApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppSessionController extends Controller
{
    public function __construct(
        private readonly WhatsAppApiService   $whatsappApi,
        private readonly UsageTrackingService $usageTracking
    )
    {
    }

    /**
     * Display WhatsApp connection page
     * @throws \Exception
     */
    public function index(): Response
    {
        $user = auth()->user();

        $response = $this->whatsappApi->getSessions($user);
        $canConnect = $this->usageTracking->canConnectNumber($user);
        $remainingSlots = $user->getRemainingQuota('connected_numbers');

        return Inertia::render('whatsapp/Connection', [
            'sessions' => $response['data']['sessions'] ?? [],
            'summary' => $response['data']['summary'] ?? [
                    'total_sessions' => 0,
                    'connected' => 0,
                    'pending' => 0,
                    'max_devices' => $remainingSlots === '∞' ? 999 : ($remainingSlots + ($response['data']['summary']['total_sessions'] ?? 0)),
                    'available_slots' => $remainingSlots,
                ],
            'canConnect' => $canConnect,
            'remainingSlots' => $remainingSlots,
            'wsBaseUrl' => config('jwt.whatsapp_api_url'),
        ]);
    }

    /**
     * Create a new WhatsApp session
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$this->usageTracking->canConnectNumber($user)) {
            return response()->json([
                'success' => false,
                'message' => __('whatsapp.limit_reached'),
            ], 403);
        }

        $validated = $request->validate([
            'session_name' => 'required|string|max:255',
        ]);

        try {
            $response = $this->whatsappApi->createSession($user, $validated['session_name']);

            if ($response['success']) {
                $this->usageTracking->trackConnectedNumber($user);

                return response()->json([
                    'success' => true,
                    'data' => $response['data'],
                    'message' => __('whatsapp.session_created'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? __('whatsapp.create_failed'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('whatsapp.error_occurred'),
            ], 500);
        }
    }

    /**
     * Get QR code for a session
     */
    public function getQR(string $sessionId): JsonResponse
    {
        $user = auth()->user();

        try {
            $response = $this->whatsappApi->getSessionQR($user, $sessionId);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('whatsapp.qr_failed'),
            ], 500);
        }
    }

    /**
     * Get session status
     */
    public function status(string $sessionId): JsonResponse
    {
        $user = auth()->user();

        try {
            $response = $this->whatsappApi->getSessionStatus($user, $sessionId);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('whatsapp.status_failed'),
            ], 500);
        }
    }

    /**
     * Refresh/reconnect a session
     */
    public function refresh(string $sessionId): JsonResponse
    {
        $user = auth()->user();

        try {
            $response = $this->whatsappApi->refreshSession($user, $sessionId);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('whatsapp.refresh_failed'),
            ], 500);
        }
    }

    /**
     * Delete a session
     */
    public function destroy(string $sessionId): JsonResponse
    {
        $user = auth()->user();

        try {
            $response = $this->whatsappApi->deleteSession($user, $sessionId);

            if ($response['success']) {
                $this->usageTracking->decrementConnectedNumber($user);

                return response()->json([
                    'success' => true,
                    'message' => __('whatsapp.disconnected'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? __('whatsapp.delete_failed'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('whatsapp.error_occurred'),
            ], 500);
        }
    }

    /**
     * Get WebSocket connection URL
     */
    public function getWebSocketUrl(string $sessionId): JsonResponse
    {
        $user = auth()->user();

        try {
            $wsUrl = $this->whatsappApi->getWebSocketUrl($user, $sessionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'ws_url' => $wsUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('whatsapp.ws_failed'),
            ], 500);
        }
    }
}
