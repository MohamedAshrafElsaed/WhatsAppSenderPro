<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
