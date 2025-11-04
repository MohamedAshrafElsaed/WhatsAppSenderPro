<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
