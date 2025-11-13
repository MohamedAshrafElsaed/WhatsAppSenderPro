<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $session_id
 * @property int $user_id
 * @property string $event_type
 * @property array<array-key, mixed>|null $event_data
 * @property string|null $related_jid
 * @property string|null $error_code
 * @property string|null $error_message
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property-read WhatsAppSession|null $session
 * @property-read User $user
 * @method static Builder<static>|WhatsAppEvent byType($type)
 * @method static Builder<static>|WhatsAppEvent errors()
 * @method static Builder<static>|WhatsAppEvent forSession($sessionId)
 * @method static Builder<static>|WhatsAppEvent newModelQuery()
 * @method static Builder<static>|WhatsAppEvent newQuery()
 * @method static Builder<static>|WhatsAppEvent query()
 * @method static Builder<static>|WhatsAppEvent recent($hours = 24)
 * @method static Builder<static>|WhatsAppEvent whereCreatedAt($value)
 * @method static Builder<static>|WhatsAppEvent whereErrorCode($value)
 * @method static Builder<static>|WhatsAppEvent whereErrorMessage($value)
 * @method static Builder<static>|WhatsAppEvent whereEventData($value)
 * @method static Builder<static>|WhatsAppEvent whereEventType($value)
 * @method static Builder<static>|WhatsAppEvent whereId($value)
 * @method static Builder<static>|WhatsAppEvent whereIpAddress($value)
 * @method static Builder<static>|WhatsAppEvent whereRelatedJid($value)
 * @method static Builder<static>|WhatsAppEvent whereSessionId($value)
 * @method static Builder<static>|WhatsAppEvent whereUserAgent($value)
 * @method static Builder<static>|WhatsAppEvent whereUserId($value)
 * @mixin Eloquent
 */
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
