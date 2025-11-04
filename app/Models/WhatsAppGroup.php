<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppGroup extends Model
{
    protected $table = 'whatsapp_groups';

    protected $fillable = [
        'user_id',
        'session_id',
        'group_jid',
        'group_name',
        'group_subject',
        'group_owner_jid',
        'participant_count',
        'admin_count',
        'is_announcement',
        'is_locked',
        'is_active',
        'is_member',
        'is_admin',
        'created_time',
        'joined_at',
        'left_at',
        'group_settings',
        'invite_code',
        'group_picture_url',
        'message_count',
        'last_activity_at',
    ];

    protected $casts = [
        'participant_count' => 'integer',
        'admin_count' => 'integer',
        'is_announcement' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'is_member' => 'boolean',
        'is_admin' => 'boolean',
        'created_time' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'group_settings' => 'array',
        'message_count' => 'integer',
        'last_activity_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(WhatsAppSession::class, 'session_id');
    }

    public function contacts()
    {
        return $this->hasMany(WhatsAppContact::class, 'group_id');
    }

    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'group_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_member', true);
    }

    public function scopeLargeGroups($query, $minParticipants = 100)
    {
        return $query->where('participant_count', '>=', $minParticipants);
    }

    public function scopeAdminGroups($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeRecentlyActive($query, $days = 7)
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function addParticipant(): void
    {
        $this->increment('participant_count');
        $this->update(['last_activity_at' => now()]);
    }

    public function removeParticipant(): void
    {
        $this->decrement('participant_count');
        $this->update(['last_activity_at' => now()]);
    }

    public function leave(): void
    {
        $this->update([
            'is_member' => false,
            'is_admin' => false,
            'left_at' => now(),
        ]);
    }
}
