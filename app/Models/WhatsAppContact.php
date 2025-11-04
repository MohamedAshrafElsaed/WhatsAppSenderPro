<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class WhatsAppContact extends Model
{
    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'user_id',
        'session_id',
        'jid',
        'full_name',
        'first_name',
        'last_name',
        'display_name',
        'country_code',
        'mobile_number',
        'contact_type',
        'is_blocked',
        'is_whatsapp_user',
        'is_synced',
        'group_id',
        'is_group_admin',
        'business_name',
        'business_category',
        'profile_picture_url',
        'last_interaction_at',
        'message_count',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'is_whatsapp_user' => 'boolean',
        'is_synced' => 'boolean',
        'is_group_admin' => 'boolean',
        'last_interaction_at' => 'datetime',
        'message_count' => 'integer',
    ];

    // Relationships

    public static function bulkInsertOrUpdate(array $contacts, $userId, $sessionId)
    {
        $chunks = array_chunk($contacts, 500); // Process in chunks for large datasets

        foreach ($chunks as $chunk) {
            $data = [];
            foreach ($chunk as $contact) {
                $data[] = array_merge($contact, [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('whatsapp_contacts')->upsert(
                $data,
                ['user_id', 'jid'], // Unique keys
                ['full_name', 'first_name', 'last_name', 'mobile_number', 'updated_at']
            );
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(WhatsAppSession::class, 'session_id');
    }

    public function group()
    {
        return $this->belongsTo(WhatsAppGroup::class, 'group_id');
    }

    // Scopes for large dataset queries

    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'from_jid', 'jid');
    }

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false)->where('is_whatsapp_user', true);
    }

    public function scopeIndividual($query)
    {
        return $query->where('contact_type', 'individual');
    }

    public function scopeBusiness($query)
    {
        return $query->where('contact_type', 'business');
    }

    public function scopeRecentlyActive($query, $days = 7)
    {
        return $query->where('last_interaction_at', '>=', now()->subDays($days));
    }

    // Optimized batch operations

    public function scopeWithHighEngagement($query, $minMessages = 10)
    {
        return $query->where('message_count', '>=', $minMessages);
    }

    // Helper methods

    public function getFormattedNumber(): string
    {
        if ($this->country_code && $this->mobile_number) {
            return "+{$this->country_code}{$this->mobile_number}";
        }
        return $this->jid;
    }

    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
        $this->update(['last_interaction_at' => now()]);
    }
}

