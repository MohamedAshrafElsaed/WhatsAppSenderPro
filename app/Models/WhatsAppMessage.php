<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $session_id
 * @property int $user_id
 * @property string $message_id
 * @property string $from_jid
 * @property string $to_jid
 * @property bool $is_outgoing
 * @property string $message_type
 * @property string|null $content
 * @property array<array-key, mixed>|null $media_data
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property bool $is_forwarded
 * @property int|null $group_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WhatsAppGroup|null $group
 * @property-read \App\Models\WhatsAppContact|null $recipient
 * @property-read \App\Models\WhatsAppContact|null $sender
 * @property-read \App\Models\WhatsAppSession $session
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage conversation($jid1, $jid2)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage incoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage outgoing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereDeliveredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereFromJid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereIsForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereIsOutgoing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereMediaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereMessageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereToJid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppMessage whereUserId($value)
 * @mixin \Eloquent
 */
class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'session_id',
        'user_id',
        'message_id',
        'from_jid',
        'to_jid',
        'is_outgoing',
        'message_type',
        'content',
        'media_data',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'is_forwarded',
        'group_id',
    ];

    protected $casts = [
        'is_outgoing' => 'boolean',
        'is_forwarded' => 'boolean',
        'media_data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WhatsAppSession::class, 'session_id');
    }

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WhatsAppGroup::class, 'group_id');
    }

    public function sender(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'from_jid', 'jid');
    }

    public function recipient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'to_jid', 'jid');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('is_outgoing', true);
    }

    public function scopeIncoming($query)
    {
        return $query->where('is_outgoing', false);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at')->where('is_outgoing', false);
    }

    public function scopeConversation($query, $jid1, $jid2)
    {
        return $query->where(function ($q) use ($jid1, $jid2) {
            $q->where('from_jid', $jid1)->where('to_jid', $jid2);
        })->orWhere(function ($q) use ($jid1, $jid2) {
            $q->where('from_jid', $jid2)->where('to_jid', $jid1);
        });
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }
}
