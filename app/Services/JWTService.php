<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService
{
    private string $secret;
    private string $algo;
    private int $ttl;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->algo = config('jwt.algo');
        $this->ttl = config('jwt.ttl');
    }

    /**
     * Verify and decode JWT token
     */
    public function verifyToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken(User $user): string
    {
        return $this->generateToken($user);
    }

    /**
     * Generate JWT token for a user
     */
    public function generateToken(User $user): string
    {
        $now = Carbon::now();

        $payload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'iat' => $now->timestamp,
            'exp' => $now->addSeconds($this->ttl)->timestamp,
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Get token expiration time
     */
    public function getTokenExpiration(): Carbon
    {
        return Carbon::now()->addSeconds($this->ttl);
    }
}
