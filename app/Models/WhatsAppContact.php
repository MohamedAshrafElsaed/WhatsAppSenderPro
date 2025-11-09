<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $session_id
 * @property string $jid
 * @property string|null $full_name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $display_name
 * @property string|null $country_code
 * @property string|null $mobile_number
 * @property string $contact_type
 * @property bool $is_blocked
 * @property bool $is_whatsapp_user
 * @property bool $is_synced
 * @property int|null $group_id
 * @property bool $is_group_admin
 * @property int $is_group_member
 * @property string|null $business_name
 * @property string|null $business_category
 * @property string|null $profile_picture_url
 * @property \Illuminate\Support\Carbon|null $last_interaction_at
 * @property int $message_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WhatsAppGroup|null $group
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WhatsAppMessage> $messages
 * @property-read int|null $messages_count
 * @property-read bool|null $messages_exists
 * @property-read \App\Models\WhatsAppSession|null $session
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact business()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact individual()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact recentlyActive($days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereBusinessCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereContactType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereIsBlocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereIsGroupAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereIsGroupMember($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereIsSynced($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereIsWhatsappUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereJid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereLastInteractionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereMessageCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereMobileNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereProfilePictureUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppContact withHighEngagement($minMessages = 10)
 * @mixin \Eloquent
 */
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

