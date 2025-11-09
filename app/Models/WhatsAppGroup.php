<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $session_id
 * @property string $group_jid
 * @property string|null $group_name
 * @property string|null $group_subject
 * @property string|null $group_owner_jid
 * @property int $participant_count
 * @property int $admin_count
 * @property bool $is_announcement
 * @property bool $is_locked
 * @property bool $is_active
 * @property bool $is_member
 * @property bool $is_admin
 * @property \Illuminate\Support\Carbon|null $created_time
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property array<array-key, mixed>|null $group_settings
 * @property string|null $invite_code
 * @property string|null $group_picture_url
 * @property int $message_count
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WhatsAppContact> $contacts
 * @property-read int|null $contacts_count
 * @property-read bool|null $contacts_exists
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WhatsAppMessage> $messages
 * @property-read int|null $messages_count
 * @property-read bool|null $messages_exists
 * @property-read \App\Models\WhatsAppSession|null $session
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup adminGroups()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup largeGroups($minParticipants = 100)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup recentlyActive($days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereAdminCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereCreatedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereGroupJid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereGroupName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereGroupOwnerJid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereGroupPictureUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereGroupSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereGroupSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereInviteCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereIsAnnouncement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereIsMember($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereJoinedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereLeftAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereMessageCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereParticipantCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppGroup whereUserId($value)
 * @mixin \Eloquent
 */
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
