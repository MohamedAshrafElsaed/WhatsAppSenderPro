<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $user_id
 * @property string $session_name
 * @property string|null $phone_number
 * @property string|null $j_id
 * @property string $status
 * @property string|null $qr_code
 * @property string|null $qr_code_base64
 * @property Carbon|null $qr_generated_at
 * @property Carbon|null $qr_expires_at
 * @property int $qr_retry_count
 * @property Carbon|null $connected_at
 * @property Carbon|null $disconnected_at
 * @property Carbon|null $last_seen
 * @property array<array-key, mixed>|null $device_info
 * @property string|null $push_name
 * @property string|null $platform
 * @property bool $is_business_account
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, WhatsAppContact> $contacts
 * @property-read int|null $contacts_count
 * @property-read bool|null $contacts_exists
 * @property-read Collection<int, WhatsAppEvent> $events
 * @property-read int|null $events_count
 * @property-read bool|null $events_exists
 * @property-read Collection<int, WhatsAppGroup> $groups
 * @property-read int|null $groups_count
 * @property-read bool|null $groups_exists
 * @property-read Collection<int, WhatsAppMessage> $messages
 * @property-read int|null $messages_count
 * @property-read bool|null $messages_exists
 * @property-read User $user
 * @method static Builder<static>|WhatsAppSession active()
 * @method static Builder<static>|WhatsAppSession connected()
 * @method static Builder<static>|WhatsAppSession disconnected()
 * @method static Builder<static>|WhatsAppSession newModelQuery()
 * @method static Builder<static>|WhatsAppSession newQuery()
 * @method static Builder<static>|WhatsAppSession onlyTrashed()
 * @method static Builder<static>|WhatsAppSession query()
 * @method static Builder<static>|WhatsAppSession recent()
 * @method static Builder<static>|WhatsAppSession whereConnectedAt($value)
 * @method static Builder<static>|WhatsAppSession whereCreatedAt($value)
 * @method static Builder<static>|WhatsAppSession whereDeletedAt($value)
 * @method static Builder<static>|WhatsAppSession whereDeviceInfo($value)
 * @method static Builder<static>|WhatsAppSession whereDisconnectedAt($value)
 * @method static Builder<static>|WhatsAppSession whereId($value)
 * @method static Builder<static>|WhatsAppSession whereIsActive($value)
 * @method static Builder<static>|WhatsAppSession whereIsBusinessAccount($value)
 * @method static Builder<static>|WhatsAppSession whereJId($value)
 * @method static Builder<static>|WhatsAppSession whereLastSeen($value)
 * @method static Builder<static>|WhatsAppSession wherePhoneNumber($value)
 * @method static Builder<static>|WhatsAppSession wherePlatform($value)
 * @method static Builder<static>|WhatsAppSession wherePushName($value)
 * @method static Builder<static>|WhatsAppSession whereQrCode($value)
 * @method static Builder<static>|WhatsAppSession whereQrCodeBase64($value)
 * @method static Builder<static>|WhatsAppSession whereQrExpiresAt($value)
 * @method static Builder<static>|WhatsAppSession whereQrGeneratedAt($value)
 * @method static Builder<static>|WhatsAppSession whereQrRetryCount($value)
 * @method static Builder<static>|WhatsAppSession whereSessionName($value)
 * @method static Builder<static>|WhatsAppSession whereStatus($value)
 * @method static Builder<static>|WhatsAppSession whereUpdatedAt($value)
 * @method static Builder<static>|WhatsAppSession whereUserId($value)
 * @method static Builder<static>|WhatsAppSession withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|WhatsAppSession withoutTrashed()
 * @mixin Eloquent
 */
class WhatsAppSession extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'whatsapp_sessions';

    protected $fillable = [
        'user_id',
        'session_name',
        'phone_number',
        'jid',
        'status',
        'qr_code',
        'qr_generated_at',
        'qr_expires_at',
        'qr_retry_count',
        'connected_at',
        'disconnected_at',
        'last_seen',
        'device_info',
        'push_name',
        'platform',
        'is_business_account',
        'is_active',
    ];

    protected $casts = [
        'qr_generated_at' => 'datetime',
        'qr_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_seen' => 'datetime',
        'device_info' => 'array',
        'is_business_account' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Don't include QR code in arrays/JSON by default
    protected $hidden = ['qr_code'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(WhatsAppContact::class, 'session_id');
    }

    public function groups()
    {
        return $this->hasMany(WhatsAppGroup::class, 'session_id');
    }

    public function events()
    {
        return $this->hasMany(WhatsAppEvent::class, 'session_id');
    }

    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'session_id');
    }

    // Scopes for performance
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'connected');
    }

    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    public function scopeDisconnected($query)
    {
        return $query->whereIn('status', ['disconnected', 'failed', 'banned']);
    }

    public function scopeRecent($query)
    {
        return $query->where('last_seen', '>=', now()->subHours(24));
    }

    // Helper methods
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isQrExpired(): bool
    {
        return $this->qr_expires_at && $this->qr_expires_at->isPast();
    }

    public function canRetryQr(): bool
    {
        return $this->qr_retry_count < 5;
    }

    public function updateLastSeen(): void
    {
        $this->update(['last_seen' => now()]);
    }

    public function disconnect(): void
    {
        $this->update([
            'status' => 'disconnected',
            'disconnected_at' => now(),
            'is_active' => false,
        ]);
    }
}
