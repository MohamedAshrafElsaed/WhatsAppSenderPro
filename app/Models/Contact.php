<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \App\Models\Country|null $country
 * @property-read string $full_name
 * @property-read \App\Models\ContactImport|null $import
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContactTag> $tags
 * @property-read int|null $tags_count
 * @property-read bool|null $tags_exists
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact byCountry(?int $countryId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact bySource(?string $source)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact forUser(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact invalidWhatsApp()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact search(?string $search)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact validWhatsApp()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact withTag(?int $tagId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact withoutTrashed()
 * @mixin \Eloquent
 */
class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'country_id',
        'whatsapp_jid',
        'is_whatsapp_valid',
        'validated_at',
        'source',
        'import_id',
        'custom_fields',
        'notes',
        'last_message_at',
    ];

    protected $casts = [
        'is_whatsapp_valid' => 'boolean',
        'validated_at' => 'datetime',
        'last_message_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(ContactImport::class, 'import_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContactTag::class, 'contact_tag')
            ->withTimestamps();
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // Scopes
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function scopeValidWhatsApp($query)
    {
        return $query->where('is_whatsapp_valid', true);
    }

    public function scopeInvalidWhatsApp($query)
    {
        return $query->where('is_whatsapp_valid', false);
    }

    public function scopeBySource($query, ?string $source)
    {
        if (!$source) {
            return $query;
        }

        return $query->where('source', $source);
    }

    public function scopeWithTag($query, ?int $tagId)
    {
        if (!$tagId) {
            return $query;
        }

        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('contact_tags.id', $tagId);
        });
    }

    public function scopeByCountry($query, ?int $countryId)
    {
        if (!$countryId) {
            return $query;
        }

        return $query->where('country_id', $countryId);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}
