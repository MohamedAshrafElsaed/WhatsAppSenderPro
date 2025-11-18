<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class JWTService
{
    private string $secret;
    private string $algo;
    private int $ttl;
    private string $issuer;
    private int $refreshWindow;
    private bool $cacheTokens;
    private int $cacheRefreshBuffer;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->algo = config('jwt.algo', 'HS256');
        $this->ttl = config('jwt.ttl', 86400);
        $this->issuer = config('jwt.issuer', 'convertedIn');
        $this->refreshWindow = config('jwt.refresh_window', 3600);
        $this->cacheTokens = config('jwt.cache_tokens', false);
        $this->cacheRefreshBuffer = config('jwt.cache_refresh_buffer', 300);

        // Validate that JWT secret is set
        if (empty($this->secret)) {
            throw new RuntimeException('JWT_SECRET is not configured. Please set JWT_SECRET in your .env file.');
        }
    }

    /**
     * Refresh JWT token for a user
     *
     * @param User $user
     * @return string
     */
    public function refreshToken(User $user): string
    {
        // Invalidate cached token
        $this->invalidateToken($user->id);

        // Generate new token
        return $this->generateCachedToken($user);
    }

    /**
     * Invalidate cached token for a user
     *
     * @param int $userId
     * @return void
     */
    public function invalidateToken(int $userId): void
    {
        $cacheKey = $this->getCacheKey($userId);
        Cache::forget($cacheKey);

        Log::debug('Invalidated cached JWT token', ['user_id' => $userId]);
    }

    /**
     * Get cache key for user's JWT token
     *
     * @param int $userId
     * @return string
     */
    private function getCacheKey(int $userId): string
    {
        return "jwt_token_user_{$userId}";
    }

    /**
     * Generate cached JWT token for a user
     * Reduces overhead by caching tokens until near expiration
     *
     * @param User $user
     * @return string
     */
    public function generateCachedToken(User $user): string
    {
        if (!$this->cacheTokens) {
            return $this->generateToken($user);
        }

        $cacheKey = $this->getCacheKey($user->id);

        // Try to get cached token
        $cachedData = Cache::get($cacheKey);

        if ($cachedData && $this->isTokenStillValid($cachedData)) {
            Log::debug('Using cached JWT token', ['user_id' => $user->id]);
            return $cachedData['token'];
        }

        // Generate new token
        $token = $this->generateToken($user);
        $expiresAt = Carbon::now()->addSeconds($this->ttl)->timestamp;

        // Cache the token
        $ttlSeconds = $this->ttl - $this->cacheRefreshBuffer;
        Cache::put($cacheKey, [
            'token' => $token,
            'expires_at' => $expiresAt,
            'user_id' => $user->id,
        ], $ttlSeconds);

        Log::debug('Generated and cached new JWT token', [
            'user_id' => $user->id,
            'ttl' => $ttlSeconds,
        ]);

        return $token;
    }

    /**
     * Generate JWT token for a user
     *
     * @param User $user
     * @return string
     */
    public function generateToken(User $user): string
    {
        $now = Carbon::now();

        $payload = [
            'iss' => $this->issuer,
            'sub' => $user->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->full_name,
            'iat' => $now->timestamp,
            'exp' => $now->copy()->addSeconds($this->ttl)->timestamp,
            'nbf' => $now->timestamp,
        ];

        try {
            return JWT::encode($payload, $this->secret, $this->algo);
        } catch (Exception $e) {
            Log::error('JWT generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to generate JWT token: ' . $e->getMessage());
        }
    }

    /**
     * Check if cached token is still valid
     *
     * @param array $cachedData
     * @return bool
     */
    private function isTokenStillValid(array $cachedData): bool
    {
        if (!isset($cachedData['expires_at']) || !isset($cachedData['token'])) {
            return false;
        }

        // Check if token expires in more than the refresh buffer
        $expiresAt = $cachedData['expires_at'];
        $now = time();

        return ($expiresAt - $now) > $this->cacheRefreshBuffer;
    }

    /**
     * Get user ID from token
     *
     * @param string $token
     * @return int|null
     */
    public function getUserIdFromToken(string $token): ?int
    {
        $decoded = $this->verifyToken($token);

        if (!$decoded || !isset($decoded->user_id)) {
            return null;
        }

        return (int)$decoded->user_id;
    }

    /**
     * Verify and decode JWT token
     *
     * @param string $token
     * @return object|null
     */
    public function verifyToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));

            // Validate issuer
            if (isset($decoded->iss) && $decoded->iss !== $this->issuer) {
                Log::warning('JWT token issuer mismatch', [
                    'expected' => $this->issuer,
                    'received' => $decoded->iss,
                ]);
                return null;
            }

            return $decoded;
        } catch (ExpiredException $e) {
            Log::info('JWT token expired', [
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (Exception $e) {
            dd($e);

            Log::warning('JWT verification failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if token is expired
     *
     * @param string $token
     * @return bool
     */
    public function isTokenExpired(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            return $decoded->exp < time();
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Check if token can be refreshed (within refresh window)
     *
     * @param string $token
     * @return bool
     */
    public function canRefreshToken(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            $expiresAt = $decoded->exp;
            $now = time();

            // Token can be refreshed if it's within the refresh window
            return ($expiresAt - $now) <= $this->refreshWindow;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get time remaining until token expires
     *
     * @param string $token
     * @return int|null Seconds remaining, null if invalid
     */
    public function getTimeRemaining(string $token): ?int
    {
        $expiration = $this->getTokenExpiration($token);

        if (!$expiration) {
            return null;
        }

        $remaining = $expiration->diffInSeconds(Carbon::now(), false);

        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Get token expiration time
     *
     * @param string $token
     * @return Carbon|null
     */
    public function getTokenExpiration(string $token): ?Carbon
    {
        $decoded = $this->verifyToken($token);

        if (!$decoded || !isset($decoded->exp)) {
            return null;
        }

        return Carbon::createFromTimestamp($decoded->exp);
    }

    /**
     * Validate JWT configuration
     *
     * @throws RuntimeException
     */
    public function validateConfiguration(): void
    {
        if (empty($this->secret)) {
            throw new RuntimeException('JWT_SECRET is not configured');
        }

        if (strlen($this->secret) < 32) {
            throw new RuntimeException('JWT_SECRET must be at least 32 characters long');
        }

        if (!in_array($this->algo, ['HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512'])) {
            throw new RuntimeException('Invalid JWT algorithm: ' . $this->algo);
        }

        if ($this->ttl < 60) {
            throw new RuntimeException('JWT_TTL must be at least 60 seconds');
        }
    }

    /**
     * Get JWT configuration info (for debugging)
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'issuer' => $this->issuer,
            'algorithm' => $this->algo,
            'ttl' => $this->ttl,
            'ttl_hours' => $this->ttl / 3600,
            'refresh_window' => $this->refreshWindow,
            'cache_enabled' => $this->cacheTokens,
            'cache_refresh_buffer' => $this->cacheRefreshBuffer,
            'secret_length' => strlen($this->secret),
        ];
    }
}
