<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppEvent extends Model
{
    public $timestamps = false;
    protected $table = 'whatsapp_events'; // We only use created_at
    protected $fillable = [
        'session_id',
        'user_id',
        'event_type',
        'event_data',
        'related_jid',
        'error_code',
        'error_message',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships

    public static function logError($userId, $sessionId, $errorMessage, $errorCode = null)
    {
        return static::log($userId, $sessionId, 'error_occurred', [
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }

    public static function log($userId, $sessionId, $eventType, ?array $eventData = null)
    {
        return static::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    // Scopes for analytics

    public static function cleanupOld($days = 30)
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(WhatsAppSession::class, 'session_id');
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Static methods for efficient logging

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('event_type', ['error_occurred', 'rate_limit_hit', 'banned_detected']);
    }

    // Cleanup old events (run in scheduled job)

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
