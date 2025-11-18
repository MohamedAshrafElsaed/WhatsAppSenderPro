<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiService
{
    private string $baseUrl;
    private JWTService $jwtService;
    private int $timeout;
    private int $retryTimes;
    private int $retryDelay;

    public function __construct(JWTService $jwtService)
    {
        $this->baseUrl = config('jwt.whatsapp_api_url');
        $this->jwtService = $jwtService;
        $this->timeout = 30; // 30 seconds timeout
        $this->retryTimes = 3; // Retry failed requests 3 times
        $this->retryDelay = 1000; // 1 second delay between retries
    }

    /**
     * Create a new WhatsApp session
     *
     * @param User $user
     * @param string $sessionName
     * @return array
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
     *
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function getSessions(User $user): array
    {
        return $this->makeRequest('get', '/api/v1/sessions', $user);
    }

    /**
     * Get QR code for a session
     *
     * @param User $user
     * @param string $sessionId
     * @param string $format 'json' or 'png'
     * @return array
     * @throws Exception
     */
    public function getSessionQR(User $user, string $sessionId, string $format = 'json'): array
    {
        return $this->makeRequest('get', "/api/v1/sessions/{$sessionId}/qr?format={$format}", $user);
    }

    /**
     * Get session status
     *
     * @param User $user
     * @param string $sessionId
     * @return array
     * @throws Exception
     */
    public function getSessionStatus(User $user, string $sessionId): array
    {
        return $this->makeRequest('get', "/api/v1/sessions/{$sessionId}/status", $user);
    }

    /**
     * Delete a session
     *
     * @param User $user
     * @param string $sessionId
     * @return array
     * @throws Exception
     */
    public function deleteSession(User $user, string $sessionId): array
    {
        return $this->makeRequest('delete', "/api/v1/sessions/{$sessionId}", $user);
    }

    /**
     * Refresh a session (reconnect)
     *
     * @param User $user
     * @param string $sessionId
     * @return array
     * @throws Exception
     */
    public function refreshSession(User $user, string $sessionId): array
    {
        return $this->makeRequest('post', "/api/v1/sessions/{$sessionId}/refresh", $user);
    }

    /**
     * Send a simple text message
     *
     * @param User $user
     * @param string $sessionId
     * @param string $to
     * @param string $message
     * @return array
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
     *
     * @param User $user
     * @param string $sessionId
     * @param array $payload
     * @return array
     * @throws Exception
     */
    public function sendAdvancedMessage(User $user, string $sessionId, array $payload): array
    {
        return $this->makeRequest('post', "/api/v1/sessions/{$sessionId}/send-advanced", $user, $payload);
    }

    /**
     * Validate phone number on WhatsApp
     *
     * @param User $user
     * @param string $phoneNumber
     * @return array
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
     *
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function getDeviceSummary(User $user): array
    {
        return $this->makeRequest('get', '/api/v1/devices/summary', $user);
    }

    /**
     * Get WebSocket connection URL with token
     *
     * @param User $user
     * @param string $sessionId
     * @return string
     */
    public function getWebSocketUrl(User $user, string $sessionId): string
    {
        $token = $this->generateToken($user);
        $wsUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $this->baseUrl);
        return "{$wsUrl}/api/v1/sessions/{$sessionId}/events?token={$token}";
    }

    /**
     * Make authenticated request to WhatsApp Go API
     *
     * @param string $method HTTP method (get, post, put, delete)
     * @param string $endpoint API endpoint
     * @param User $user User making the request
     * @param array $data Request payload
     * @return array Response data
     * @throws Exception
     */
    public function makeRequest(string $method, string $endpoint, User $user, array $data = []): array
    {
        $token = $this->generateToken($user);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay, function ($exception) {
                    // Only retry on connection errors, not on 4xx/5xx responses
                    return $exception instanceof ConnectionException;
                })
                ->$method("{$this->baseUrl}{$endpoint}", $data);

            // Log successful requests (debug level)
            if ($response->successful()) {
                Log::debug('WhatsApp API request successful', [
                    'endpoint' => $endpoint,
                    'method' => strtoupper($method),
                    'status' => $response->status(),
                    'user_id' => $user->id,
                ]);

                return $response->json();
            }

            // Log failed requests (error level)
            Log::error('WhatsApp API request failed', [
                'endpoint' => $endpoint,
                'method' => strtoupper($method),
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => $user->id,
            ]);

            // Handle specific error codes
            $statusCode = $response->status();
            $errorMessage = $this->getErrorMessage($response);

            if ($statusCode === 401) {
                throw new Exception('WhatsApp API authentication failed. Please check JWT configuration.');
            }

            if ($statusCode === 403) {
                throw new Exception('Access denied to WhatsApp API resource.');
            }

            if ($statusCode === 404) {
                throw new Exception('WhatsApp API resource not found: ' . $endpoint);
            }

            if ($statusCode >= 500) {
                throw new Exception('WhatsApp API server error. Please try again later.');
            }

            throw new Exception($errorMessage);

        } catch (ConnectionException $e) {
            Log::error('WhatsApp API connection failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            throw new Exception('Unable to connect to WhatsApp API. Please check if the service is running.');

        } catch (RequestException $e) {
            Log::error('WhatsApp API request exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            throw $e;

        } catch (Exception $e) {
            // Re-throw exception if it's already an Exception
            if ($e instanceof Exception) {
                throw $e;
            }

            Log::error('WhatsApp API unexpected error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            throw new Exception('An unexpected error occurred while communicating with WhatsApp API.');
        }
    }

    /**
     * Generate JWT token for API authentication
     *
     * @param User $user
     * @return string
     */
    private function generateToken(User $user): string
    {
        return $this->jwtService->generateCachedToken($user);
    }

    /**
     * Extract error message from response
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return string
     */
    private function getErrorMessage($response): string
    {
        $json = $response->json();

        // Try to get error message from response
        if (isset($json['error'])) {
            return $json['error'];
        }

        if (isset($json['message'])) {
            return $json['message'];
        }

        // Fallback to status text
        return "WhatsApp API error: HTTP {$response->status()}";
    }

    /**
     * Check if WhatsApp API is reachable
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (Exception $e) {
            Log::warning('WhatsApp API health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get API base URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Set custom timeout for requests
     *
     * @param int $seconds
     * @return self
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set retry configuration
     *
     * @param int $times
     * @param int $delay
     * @return self
     */
    public function setRetry(int $times, int $delay): self
    {
        $this->retryTimes = $times;
        $this->retryDelay = $delay;
        return $this;
    }
}
