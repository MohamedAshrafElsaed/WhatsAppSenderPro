<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiService
{
    private string $baseUrl;
    private string $jwtSecret;

    public function __construct()
    {
        $this->baseUrl = config('jwt.whatsapp_api_url');
        $this->jwtSecret = config('jwt.secret');
    }

    /**
     * Send a simple text message
     * @throws Exception
     */
    public function sendMessage(User $user, string $sessionId, string $to, string $message): array
    {
        return $this->makeRequest('post', "/api/v1/sessions/{$sessionId}/send", $user, [
            'to' => $to,
            'message' => $message,
        ]);
    }

    /**
     * Send advanced message (with media support)
     * @throws Exception
     */
    public function sendAdvancedMessage(User $user, string $sessionId, array $payload): array
    {
        return $this->makeRequest('post', "/api/v1/sessions/{$sessionId}/send-advanced", $user, $payload);
    }

    /**
     * Create a new WhatsApp session
     * @throws Exception
     */
    public function createSession(User $user, string $sessionName): array
    {
        return $this->makeRequest('post', '/api/v1/sessions', $user, [
            'session_name' => $sessionName,
        ]);
    }

    /**
     * Get all user sessions
     * @throws Exception
     */
    public function getSessions(User $user): array
    {
        return $this->makeRequest('get', '/api/v1/sessions', $user);
    }

    /**
     * Get QR code for a session
     * @throws Exception
     */
    public function getSessionQR(User $user, string $sessionId, string $format = 'json'): array
    {
        return $this->makeRequest('get', "/api/v1/sessions/{$sessionId}/qr?format={$format}", $user);
    }

    /**
     * Get session status
     * @throws Exception
     */
    public function getSessionStatus(User $user, string $sessionId): array
    {
        return $this->makeRequest('get', "/api/v1/sessions/{$sessionId}/status", $user);
    }

    /**
     * Delete a session
     * @throws Exception
     */
    public function deleteSession(User $user, string $sessionId): array
    {
        return $this->makeRequest('delete', "/api/v1/sessions/{$sessionId}", $user);
    }

    /**
     * Refresh a session (reconnect)
     * @throws Exception
     */
    public function refreshSession(User $user, string $sessionId): array
    {
        return $this->makeRequest('post', "/api/v1/sessions/{$sessionId}/refresh", $user);
    }

    /**
     * Validate phone number on WhatsApp
     * @throws Exception
     */
    public function validatePhoneNumber(User $user, string $phoneNumber): array
    {
        return $this->makeRequest('post', '/api/v1/validate-account', $user, [
            'phone_number' => $phoneNumber,
        ]);
    }

    /**
     * Get device summary
     * @throws Exception
     */
    public function getDeviceSummary(User $user): array
    {
        return $this->makeRequest('get', '/api/v1/devices/summary', $user);
    }

    /**
     * Get WebSocket connection URL with token
     */
    public function getWebSocketUrl(User $user, string $sessionId): string
    {
        $token = $this->generateToken($user);
        $wsUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $this->baseUrl);
        return "{$wsUrl}/api/v1/sessions/{$sessionId}/events?token={$token}";
    }

    /**
     * Make authenticated request to WhatsApp Go API
     * @throws Exception
     */
    public function makeRequest(string $method, string $endpoint, User $user, array $data = [])
    {
        $token = $this->generateToken($user);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->$method("{$this->baseUrl}{$endpoint}", $data);

            if (!$response->successful()) {
                Log::error('WhatsApp API Error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('WhatsApp API Exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate JWT token for API authentication
     */
    private function generateToken(User $user): string
    {
        return $user->generateJWT();
    }
}
